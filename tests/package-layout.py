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
update = json.loads((root / 'update.json').read_text())
assert set(update) == {'1.0.0-beta38', '1.0.0-rc.1'}, 'update.json lists every supported Pelican release'
update = update['1.0.0-beta38']
assert update['version'] == manifest['version'], 'update.json announces the current version'
assert update['download_url'].endswith(f"dist/pelican-simplify-v{manifest['version']}.zip")
published = root / 'dist' / f"pelican-simplify-v{manifest['version']}.zip"
assert published.is_file(), 'the published archive for this version is committed'
assert update['sha256'] == hashlib.sha256(published.read_bytes()).hexdigest()
assert (root / 'dist/pelican-simplify-latest.zip').read_bytes() == published.read_bytes()
assert sorted(p.name for p in (root / 'dist').glob('*.zip')) == sorted(['pelican-simplify-latest.zip', published.name]), 'only the current release is kept'

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
print('PASS Hub root manifest, autoload layout, native archive, single published release and update checksum')
