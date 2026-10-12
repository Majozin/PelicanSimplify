from pathlib import Path
import hashlib, json, sys, tempfile, zipfile
root = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(root / 'scripts'))
import importlib.util
spec = importlib.util.spec_from_file_location('build_package', root / 'scripts' / 'build-package.py')
builder = importlib.util.module_from_spec(spec); spec.loader.exec_module(builder)

manifest = json.loads((root / 'plugin.json').read_text())
assert manifest['id'] == 'pelican-simplify'
assert (root / 'src' / f"{manifest['class']}.php").is_file()
assert not (root / 'pelican-simplify').exists()
channels = {name: json.loads((root / name).read_text()) for name in ['update.json', 'update-beta.json']}
stable = '-' not in manifest['version']
beta = channels['update-beta.json'][manifest['panel_version'].lstrip('^')]
assert beta['version'] == manifest['version'], 'the beta channel announces the current version'
assert beta['download_url'].endswith(f"dist/pelican-simplify-v{manifest['version']}.zip")
published = root / 'dist' / f"pelican-simplify-v{manifest['version']}.zip"
assert published.is_file(), 'the published archive for this version is committed'
assert beta['sha256'] == hashlib.sha256(published.read_bytes()).hexdigest()
if stable:
    assert channels['update.json'] == channels['update-beta.json'], 'a stable release is on both channels'
    assert (root / 'dist/pelican-simplify-latest.zip').read_bytes() == published.read_bytes(), 'latest.zip is the stable release'
referenced = {entry['download_url'].rsplit('/', 1)[-1] for data in channels.values() for entry in data.values()}
for entry in [e for data in channels.values() for e in data.values()]:
    assert (root / 'dist' / entry['download_url'].rsplit('/', 1)[-1]).is_file(), 'every announced archive is committed'
assert sorted(p.name for p in (root / 'dist').glob('pelican-simplify-v*.zip')) == sorted(referenced), 'only archives on a channel are kept'

with tempfile.TemporaryDirectory() as temp:
    archive = builder.build(Path(temp), 1_800_000_000)
    with zipfile.ZipFile(archive) as package:
        names = package.namelist()
        assert json.loads(package.read('pelican-simplify/plugin.json')) == manifest
        assert 'pelican-simplify/src/' + manifest['class'] + '.php' in names
        for bundle in ['css/tokens.css', 'css/components.css', 'css/dashboard.css', 'js/core.js', 'js/dashboard.js', 'js/wizard.js']:
            assert 'pelican-simplify/resources/views/' + bundle in names, bundle
        assert 'pelican-simplify/database/Seeders/PelicanSimplifySeeder.php' in names
        assert all(n.startswith('pelican-simplify/') for n in names)
        assert not any(n.startswith('pelican-simplify/docs/') for n in names), 'screenshots are not shipped'
        # Laravel reuses a compiled view if its mtime is newer than the source.
        assert package.getinfo('pelican-simplify/resources/views/dashboard.blade.php').date_time[0] >= 2027
print('PASS Hub root manifest, autoload layout, native archive, stable and beta channels with their archives and checksums')
