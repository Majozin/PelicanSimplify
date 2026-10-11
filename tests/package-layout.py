from pathlib import Path
import hashlib,json,zipfile
root=Path(__file__).resolve().parent.parent
manifest=json.loads((root/'plugin.json').read_text())
assert manifest['id']=='pelican-simplify'
assert (root/'src'/f"{manifest['class']}.php").is_file()
assert not (root/'pelican-simplify').exists()
update=json.loads((root/'update.json').read_text())[manifest['panel_version']]
archive=root/'dist'/f"pelican-simplify-v{manifest['version']}.zip"
assert update['version']==manifest['version']
assert update['sha256']==hashlib.sha256(archive.read_bytes()).hexdigest()
assert (root/'dist/pelican-simplify-latest.zip').read_bytes()==archive.read_bytes()
with zipfile.ZipFile(archive) as package:
    packaged=json.loads(package.read('pelican-simplify/plugin.json'))
    assert packaged==manifest
    assert 'pelican-simplify/src/'+manifest['class']+'.php' in package.namelist()
    assert 'pelican-simplify/resources/views/dashboard.css' in package.namelist()
    assert 'pelican-simplify/database/Seeders/PelicanSimplifySeeder.php' in package.namelist()
    assert all(n.startswith('pelican-simplify/') for n in package.namelist())
    previous=root/'dist'/'pelican-simplify-v0.2.0-alpha.7.zip'
    with zipfile.ZipFile(previous) as old:
        name='pelican-simplify/resources/views/dashboard.blade.php'
        # Laravel reuses a compiled view if its mtime is newer than the source.
        # A new release must not retain the old source timestamp.
        assert package.getinfo(name).date_time > old.getinfo(name).date_time
print('PASS Hub root manifest, autoload layout, native archive and update checksum')
