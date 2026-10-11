"""Exercise reset/restore and failed-cache rollback in a disposable fake panel."""
from pathlib import Path
import json,os,subprocess,tempfile
root=Path(__file__).resolve().parent.parent
if os.geteuid()!=0:
    print('SKIP plugin-reset integration checks (run with sudo)')
    raise SystemExit(0)
with tempfile.TemporaryDirectory(prefix='simplify-reset-test-') as directory:
    temp=Path(directory);panel=temp/'panel';fake=temp/'bin';fake.mkdir()
    for name in ['vendor','bootstrap','storage/framework','storage/app/private/simplify','plugins/essentials','plugins/pelican-simplify']:(panel/name).mkdir(parents=True,exist_ok=True)
    (panel/'artisan').write_text('stub')
    (panel/'vendor/autoload.php').write_text('stub')
    (panel/'plugins/essentials/plugin.json').write_text('{"id":"essentials"}')
    (panel/'plugins/pelican-simplify/plugin.json').write_text('{"id":"pelican-simplify"}')
    (panel/'plugins/essentials/secret-config.json').write_text('{"retained":true}')
    feature=panel/'storage/app/private/simplify/features.json'
    original={'setup_completed':True,'friends_mode':False};feature.write_text(json.dumps(original))
    (fake/'sudo').write_text('#!/bin/bash\n[[ "$1" == -u ]] && shift 2\nexec "$@"\n')
    (fake/'php').write_text('''#!/bin/bash
if [[ "$1" == */resolve-state.php ]]; then printf '%s' "$2/storage/app/private/simplify/features.json"; exit; fi
if [[ "$1" == artisan ]]; then
 case "$2" in down) touch storage/framework/down;; up) rm -f storage/framework/down;; route:clear) if [[ "${FAIL_CLEAR:-0}" == 1 ]]; then exit 1; fi;; esac
 exit 0
fi
exit 1
''')
    for p in fake.iterdir():p.chmod(0o755)
    env={**os.environ,'PATH':str(fake)+':'+os.environ['PATH'],'PELICAN_USER':'root'}
    script=str(root/'RESET-PLUGINS.sh')
    result=subprocess.run(['bash',script,'reset',str(panel),str(temp/'backups')],env=env,capture_output=True,text=True)
    assert result.returncode==0,result.stdout+result.stderr
    backup=next((temp/'backups').iterdir())
    assert list((panel/'plugins').iterdir())==[]
    assert json.loads(feature.read_text())=={'setup_completed':False,'friends_mode':False}
    assert not (panel/'storage/framework/down').exists()
    assert (backup/'plugins.tar.gz').is_file()
    result=subprocess.run(['bash',script,'restore',str(panel),str(backup)],env=env,capture_output=True,text=True)
    assert result.returncode==0,result.stdout+result.stderr
    assert json.loads(feature.read_text())==original
    assert (panel/'plugins/essentials/secret-config.json').read_text()=='{"retained":true}'
    result=subprocess.run(['bash',script,'reset',str(panel),str(temp/'failed-backups')],env={**env,'FAIL_CLEAR':'1'},capture_output=True,text=True)
    assert result.returncode!=0
    assert (panel/'plugins/essentials/secret-config.json').is_file()
    assert json.loads(feature.read_text())==original
    assert not (panel/'storage/framework/down').exists()
    print('PASS plugin reset backup verification, preserved configuration, wizard reset, restore and failed-clear rollback')
