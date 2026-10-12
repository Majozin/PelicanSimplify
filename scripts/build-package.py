#!/usr/bin/env python3
"""Build the native plugin package from the standard repository-root layout.

Writes dist/pelican-simplify-v<version>.zip and dist/pelican-simplify-latest.zip (same bytes),
removes older versioned archives, and updates update.json and SHA256SUMS.
Usage: build-package.py [output-dir]   (default: dist; SOURCE_DATE_EPOCH fixes the timestamps)
"""
from pathlib import Path
from datetime import datetime, timezone
import hashlib
import json
import os
import shutil
import sys
import zipfile

root = Path(__file__).resolve().parent.parent
REPOSITORY = 'https://raw.githubusercontent.com/jerajerajerajera/PelicanSimplify/main/'


def build(output: Path, epoch: int | None = None) -> Path:
    manifest = json.loads((root / 'plugin.json').read_text())
    version = manifest['version']
    output.mkdir(parents=True, exist_ok=True)
    archive = output / f'pelican-simplify-v{version}.zip'
    # ZIP extraction preserves mtimes. Each release needs newer timestamps than the last, or
    # Laravel's compiled views and timestamp-based PHP caches look up to date.
    moment = datetime.fromtimestamp(epoch, timezone.utc) if epoch is not None else datetime.now(timezone.utc)
    release_time = moment.timetuple()[:6]
    files = [root / name for name in ['plugin.json', 'README.md', 'LICENSE']]
    for name in ['src', 'resources', 'database', 'lang']:
        if (root / name).is_dir():
            files.extend(p for p in (root / name).rglob('*') if p.is_file())
    with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as package:
        for path in sorted(files):
            info = zipfile.ZipInfo('pelican-simplify/' + path.relative_to(root).as_posix(), release_time)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            package.writestr(info, path.read_bytes())
    shutil.copyfile(archive, output / 'pelican-simplify-latest.zip')
    for old in output.glob('pelican-simplify-v*.zip'):
        if old != archive:
            old.unlink()
    return archive


SUPPORTED_PANELS = ['1.0.0-beta38', '1.0.0-rc.1']


def publish(archive: Path) -> None:
    manifest = json.loads((root / 'plugin.json').read_text())
    checksum = hashlib.sha256(archive.read_bytes()).hexdigest()
    entry = {
        'version': manifest['version'],
        'download_url': REPOSITORY + archive.relative_to(root).as_posix(),
        'sha256': checksum,
    }
    # One entry per Pelican release this build is validated on (Pelican looks up its own version).
    (root / 'update.json').write_text(json.dumps({panel: entry for panel in SUPPORTED_PANELS}, indent=2) + '\n')
    with (root / 'SHA256SUMS').open('w') as sums:
        for path in sorted(root.rglob('*')):
            relative = path.relative_to(root)
            if not path.is_file() or path.name == 'SHA256SUMS' or any(part in ['node_modules', '.git', '__pycache__', 'test-results', 'playwright-report'] for part in relative.parts):
                continue
            sums.write(hashlib.sha256(path.read_bytes()).hexdigest() + '  ' + relative.as_posix() + '\n')


if __name__ == '__main__':
    target = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else root / 'dist'
    epoch = int(os.environ['SOURCE_DATE_EPOCH']) if 'SOURCE_DATE_EPOCH' in os.environ else None
    built = build(target, epoch)
    if target == root / 'dist':
        publish(built)
    print(built)
