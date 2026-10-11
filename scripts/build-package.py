#!/usr/bin/env python3
"""Build the native plugin package from the standard repository-root layout."""
from pathlib import Path
import hashlib
import json
import zipfile
root = Path(__file__).resolve().parent.parent
manifest = json.loads((root / 'plugin.json').read_text())
version = manifest['version']
archive = root / 'dist' / f'pelican-simplify-v{version}.zip'
archive.parent.mkdir(exist_ok=True)
files = [root / name for name in ['plugin.json', 'README.md', 'LICENSE']]
for name in ['src', 'resources', 'database', 'lang', 'docs']:
    if (root / name).is_dir():
        files.extend(p for p in (root / name).rglob('*') if p.is_file())
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as package:
    for path in sorted(files):
        name = 'pelican-simplify/' + path.relative_to(root).as_posix()
        info = zipfile.ZipInfo(name, (2026, 10, 11, 0, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        package.writestr(info, path.read_bytes())
checksum = hashlib.sha256(archive.read_bytes()).hexdigest()
(root / 'update.json').write_text(json.dumps({manifest['panel_version']: {
    'version': version,
    'download_url': 'https://raw.githubusercontent.com/jerajerajerajera/PelicanSimplify/main/' + archive.relative_to(root).as_posix(),
    'sha256': checksum,
}}, indent=2) + '\n')
with (root / 'SHA256SUMS').open('w') as sums:
    for path in sorted(root.rglob('*')):
        relative = path.relative_to(root)
        if not path.is_file() or path.name == 'SHA256SUMS' or any(part in ['node_modules', '.git', '__pycache__'] for part in relative.parts):
            continue
        sums.write(hashlib.sha256(path.read_bytes()).hexdigest() + '  ' + relative.as_posix() + '\n')
print(archive)
