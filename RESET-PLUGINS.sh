#!/usr/bin/env bash
# Back up and retire all panel plugins, or restore an earlier reset.
set -Eeuo pipefail
[[ $EUID -eq 0 ]] || { echo 'Run with sudo.' >&2; exit 1; }
MODE=${1:-reset}
PANEL=$(realpath -e -- "${2:-/var/www/pelican}")
PANEL_USER=${PELICAN_USER:-www-data}
[[ "$MODE" == reset || "$MODE" == restore ]] || { echo 'Usage: RESET-PLUGINS.sh reset|restore PANEL [BACKUP_DIRECTORY]' >&2; exit 1; }
[[ -f "$PANEL/artisan" && -f "$PANEL/vendor/autoload.php" && -d "$PANEL/plugins" && ! -L "$PANEL/plugins" ]] || { echo 'Expected a Pelican panel with a regular plugins directory.' >&2; exit 1; }
id "$PANEL_USER" >/dev/null
for COMMAND in php python3 tar sha256sum sudo flock; do command -v "$COMMAND" >/dev/null; done
exec 9>"$PANEL/storage/framework/simplify-plugin-reset.lock"
flock -n 9 || { echo 'Another plugin reset is running.' >&2; exit 1; }
WORK=$(mktemp -d "$PANEL/storage/framework/plugin-reset-work-XXXXXXXX")
chmod 755 "$WORK"
MAINTENANCE=0
SWAPPED=0
COMMITTED=0
STATE_CHANGED=0
RETIRED=''
FEATURE_FILE=''
BACKUP=''
cleanup() {
    local STATUS=$?
    trap - EXIT
    if (( COMMITTED == 0 )); then
        if (( SWAPPED == 1 )); then
            mv "$PANEL/plugins" "$WORK/failed-plugins"
            mv "$RETIRED" "$PANEL/plugins"
        fi
        if (( STATE_CHANGED == 1 )); then
            if [[ -f "$WORK/previous-features.json" ]]; then
                cp -a "$WORK/previous-features.json" "$FEATURE_FILE"
            else
                rm -f -- "$FEATURE_FILE"
            fi
        fi
        if (( SWAPPED == 1 )); then
            (cd "$PANEL" && sudo -u "$PANEL_USER" php artisan config:clear && sudo -u "$PANEL_USER" php artisan route:clear && sudo -u "$PANEL_USER" php artisan view:clear) || true
        fi
    fi
    if (( MAINTENANCE == 1 )); then (cd "$PANEL" && sudo -u "$PANEL_USER" php artisan up) || true; fi
    rm -rf -- "$WORK"
    if (( STATUS != 0 )); then echo "Reset failed; previous plugins and setup state restored. Backup retained: $BACKUP" >&2; fi
    exit "$STATUS"
}
trap cleanup EXIT
cat > "$WORK/resolve-state.php" <<'PHP'
<?php
ob_start();
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$path = \Illuminate\Support\Facades\Storage::disk('local')->path('simplify/features.json');
ob_end_clean();
echo $path;
PHP
chmod 644 "$WORK/resolve-state.php"
FEATURE_FILE=$(cd "$PANEL" && sudo -u "$PANEL_USER" php "$WORK/resolve-state.php" "$PANEL")
[[ "$FEATURE_FILE" == /* && "$FEATURE_FILE" != *$'\n'* && ! -L "$FEATURE_FILE" ]] || { echo 'Could not safely resolve Simplify settings.' >&2; exit 1; }
if [[ -f "$PANEL/storage/framework/down" ]]; then echo 'Panel is already in maintenance mode; finish that operation first.' >&2; exit 1; fi
if [[ -f "$FEATURE_FILE" ]]; then cp -a "$FEATURE_FILE" "$WORK/previous-features.json"; fi
if [[ "$MODE" == reset ]]; then
    BACKUP_BASE=${3:-/var/backups/pelican-simplify}
    mkdir -p -- "$BACKUP_BASE"
    BACKUP=$(mktemp -d "$BACKUP_BASE/plugin-reset-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")
    chmod 700 "$BACKUP"
    printf '%s\n' "$PANEL" > "$BACKUP/panel-path.txt"
    printf '%s\n' "$FEATURE_FILE" > "$BACKUP/features-path.txt"
    if [[ -f "$FEATURE_FILE" ]]; then cp -a "$FEATURE_FILE" "$BACKUP/features.json"; else touch "$BACKUP/features-absent"; fi
else
    BACKUP=$(realpath -e -- "${3:?Pass the backup directory printed by the reset}")
    [[ "$(cat "$BACKUP/panel-path.txt")" == "$PANEL" && "$(cat "$BACKUP/features-path.txt")" == "$FEATURE_FILE" ]] || { echo 'Backup belongs to a different panel or storage configuration.' >&2; exit 1; }
    (cd "$BACKUP" && sha256sum -c SHA256SUMS >/dev/null)
fi
(cd "$PANEL" && sudo -u "$PANEL_USER" php artisan down)
MAINTENANCE=1
if [[ "$MODE" == reset ]]; then
    tar -czpf "$BACKUP/plugins.tar.gz" -C "$PANEL" plugins
    tar -tzf "$BACKUP/plugins.tar.gz" >/dev/null
    tar -dzf "$BACKUP/plugins.tar.gz" -C "$PANEL" plugins
    (cd "$BACKUP" && sha256sum plugins.tar.gz panel-path.txt features-path.txt features* > SHA256SUMS)
    # Copy directory permissions before moving the originals out of discovery.
    mkdir "$WORK/plugins"
    chmod --reference="$PANEL/plugins" "$WORK/plugins"
    chown --reference="$PANEL/plugins" "$WORK/plugins"
else
    tar -xzpf "$BACKUP/plugins.tar.gz" -C "$WORK"
fi
mkdir -p "$PANEL/plugin-disabled"
RETIRED=$(mktemp -d "$PANEL/plugin-disabled/reset-XXXXXXXX")
rmdir "$RETIRED"
mv "$PANEL/plugins" "$RETIRED"
# Mark the swap immediately so a failure moving the replacement rolls back too.
SWAPPED=1
if ! mv "$WORK/plugins" "$PANEL/plugins"; then
    mv "$RETIRED" "$PANEL/plugins"
    SWAPPED=0
    exit 1
fi
STATE_CHANGED=1
if [[ "$MODE" == reset ]]; then
    if [[ -f "$FEATURE_FILE" ]]; then
        python3 - "$FEATURE_FILE" <<'PY'
import json,os,sys,tempfile,stat
from pathlib import Path
path=Path(sys.argv[1]); data=json.loads(path.read_text())
if not isinstance(data,dict):raise SystemExit('Invalid Simplify settings; rolling back.')
data['setup_completed']=False
old=path.stat();fd,name=tempfile.mkstemp(prefix='.simplify-reset-',dir=path.parent)
try:
    with os.fdopen(fd,'w') as out:json.dump(data,out,indent=2);out.write('\n')
    os.chown(name,old.st_uid,old.st_gid);os.chmod(name,stat.S_IMODE(old.st_mode));os.replace(name,path)
finally:
    if os.path.exists(name):os.unlink(name)
PY
    fi
else
    if [[ -f "$BACKUP/features.json" ]]; then
        mkdir -p -- "$(dirname -- "$FEATURE_FILE")"
        cp -a "$BACKUP/features.json" "$FEATURE_FILE"
    else
        rm -f -- "$FEATURE_FILE"
    fi
fi
for ACTION in config:clear route:clear view:clear event:clear filament:clear-cached-components queue:restart; do
    (cd "$PANEL" && sudo -u "$PANEL_USER" php artisan "$ACTION")
done
(cd "$PANEL" && sudo -u "$PANEL_USER" php artisan up)
MAINTENANCE=0
COMMITTED=1
if [[ "$MODE" == reset ]]; then
    echo "All panel plugins retired. Verified backup: $BACKUP"
    echo "Original plugin files retained outside the active folder: $RETIRED"
    echo 'Import PelicanSimplify through Admin > Plugins, click Install, then open Simplify settings.'
    printf 'Restore command: sudo bash %q restore %q %q\n' "${BASH_SOURCE[0]}" "$PANEL" "$BACKUP"
else
    echo "Plugins and setup state restored from $BACKUP"
    echo "The replaced plugins are retained at $RETIRED"
fi
