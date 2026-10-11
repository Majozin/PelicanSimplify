#!/usr/bin/env bash
set -Eeuo pipefail
[[ $EUID -eq 0 ]] || { echo 'Run with sudo.' >&2; exit 1; }
PACKAGE=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
PANEL=${1:-/var/www/pelican}
MODE=${2:-core}
[[ "$MODE" == all || "$MODE" == core ]] || { echo 'Mode must be all or core.' >&2; exit 1; }
[[ -f "$PANEL/artisan" && -f "$PANEL/vendor/autoload.php" ]] || { echo 'Pelican not found.' >&2; exit 1; }
php -r 'if(version_compare(PHP_VERSION,"8.3","<"))exit(1);'
command -v python3 >/dev/null
(cd "$PACKAGE" && sha256sum -c SHA256SUMS >/dev/null)
PLUGINS=(pelican-simplify)
if [[ "$MODE" == all ]]; then PLUGINS+=(server-directory); fi
for ID in "${PLUGINS[@]}"; do
    SOURCE="$PACKAGE/$ID"
    if [[ "$ID" == pelican-simplify ]]; then SOURCE="$PACKAGE"; fi
    [[ -f "$SOURCE/plugin.json" ]] || { echo "This package does not contain $ID." >&2; exit 1; }
    [[ ! -L "$PANEL/plugins/$ID" ]] || { echo "Refusing symlink: $ID" >&2; exit 1; }
    while IFS= read -r -d '' FILE; do php -l "$FILE" >/dev/null; done < <(find "$SOURCE" -name '*.php' ! -name '*.blade.php' -print0)
done
# Validate as the same user that serves the panel, before replacing anything.
sudo -u www-data php "$PACKAGE/preflight.php" "$PANEL"
BACKUP=$(mktemp -d /var/backups/pelican-simplify-XXXXXXXX)
AFFECTED=("${PLUGINS[@]}")
if [[ -d "$PANEL/plugins/simple-server-creator" ]]; then
    [[ ! -L "$PANEL/plugins/simple-server-creator" ]] || { echo 'Refusing legacy creator symlink.' >&2; exit 1; }
    AFFECTED+=(simple-server-creator)
fi
printf '%s\n' "${AFFECTED[@]}" > "$BACKUP/installed-plugins.txt"
STAGE=$(mktemp -d "$PANEL/storage/framework/simplify-install-XXXXXXXX")
chmod 755 "$STAGE"
cp "$PACKAGE/check-panel.php" "$STAGE/check-panel.php"
cp "$PACKAGE/prepare-policies.php" "$STAGE/prepare-policies.php"
chmod 644 "$STAGE/check-panel.php"
COMMITTED=0
CHANGED=()
clear_bootstrap() {
    for ACTION in config:clear route:clear view:clear event:clear filament:clear-cached-components; do sudo -u www-data php artisan "$ACTION"; done
}
rollback() {
    local STATUS=$?
    if (( COMMITTED == 0 && ${#CHANGED[@]} )); then
        for ID in "${CHANGED[@]}"; do
            if [[ -d "$PANEL/plugins/$ID" ]]; then mv "$PANEL/plugins/$ID" "$STAGE/failed-$ID"; fi
            if [[ -d "$BACKUP/$ID" ]]; then cp -a "$BACKUP/$ID" "$PANEL/plugins/$ID"; fi
        done
        (cd "$PANEL" && clear_bootstrap && sudo -u www-data php artisan queue:restart) || true
        echo "Installation failed; previous plugins restored. Backup: $BACKUP" >&2
    fi
    if (( STATUS != 0 )); then echo "Backup retained: $BACKUP" >&2; fi
}
trap rollback EXIT
if [[ -d "$PANEL/plugins/simple-server-creator" ]]; then
    cp -a "$PANEL/plugins/simple-server-creator" "$BACKUP/simple-server-creator"
    CHANGED+=(simple-server-creator)
    mv "$PANEL/plugins/simple-server-creator" "$STAGE/previous-simple-server-creator"
fi
for ID in "${PLUGINS[@]}"; do
    TARGET="$PANEL/plugins/$ID"
    if [[ -d "$TARGET" ]]; then cp -a "$TARGET" "$BACKUP/$ID"; fi
    if [[ "$ID" == pelican-simplify ]]; then
        mkdir "$STAGE/$ID"
        for ENTRY in plugin.json src resources database config lang README.md LICENSE docs; do
            if [[ -e "$PACKAGE/$ENTRY" ]]; then cp -a "$PACKAGE/$ENTRY" "$STAGE/$ID/"; fi
        done
    else
        cp -a "$PACKAGE/$ID" "$STAGE/$ID"
    fi
    python3 - "$TARGET" "$STAGE/$ID" <<'PY'
import json,sys,shutil
from pathlib import Path
old,new=map(Path,sys.argv[1:]);after=json.loads((new/'plugin.json').read_text());meta={}
if (old/'plugin.json').exists():
    before=json.loads((old/'plugin.json').read_text())
    if before.get('id')!=after['id']:raise SystemExit('Plugin identity mismatch; nothing installed.')
    meta=before.get('meta',{})
    if (old/'config').exists():shutil.copytree(old/'config',new/'config',dirs_exist_ok=True)
after['meta']={**meta,'status':'enabled','status_message':None}
(new/'plugin.json').write_text(json.dumps(after,indent=2)+'\n')
PY
    if [[ "$ID" == pelican-simplify ]]; then
        python3 - "$BACKUP" "$STAGE/$ID" <<'PYCODE'
import json,sys
from pathlib import Path
backup,stage=map(Path,sys.argv[1:]);names=[]
for plugin in ['pelican-simplify','simple-server-creator']:
    source=backup/plugin/'plugin.json'
    if source.exists():
        namespace=json.loads(source.read_text()).get('namespace')
        if namespace and namespace!='Simplify\\Panel':names.append(namespace)
previous=stage/'config/compatibility.json'
if previous.exists():names.extend(json.loads(previous.read_text()))
previous.parent.mkdir(exist_ok=True)
previous.write_text(json.dumps(sorted(set(names))))
# Extract the prior installation's website link into optional Web configuration.
import re
view=backup/'pelican-simplify/resources/views/dashboard.blade.php'
if view.exists():
    match=re.search(r'<a\s+x-show="boot\.web"[^>]*\shref="(https?://[^"<>]+)"',view.read_text())
    config=stage/'config/legacy-web.json'
    if match and not config.exists():config.write_text(json.dumps({'website_url':match.group(1)}))
PYCODE
    fi
    chown -R www-data:www-data "$STAGE/$ID"
    CHANGED+=("$ID")
    if [[ -d "$TARGET" ]]; then mv "$TARGET" "$STAGE/previous-$ID"; fi
    mv "$STAGE/$ID" "$TARGET"
done
cd "$PANEL"
clear_bootstrap
sudo -u www-data php artisan queue:restart
# Do not trust PHP's exit code alone: Laravel can consume an exception.
install -d -o www-data -g www-data -m 750 "$STAGE/validation"
MARKER="$STAGE/validation/success"
if ! sudo -u www-data php "$STAGE/check-panel.php" "$PANEL" "$MARKER"; then
    echo 'Pelican validation failed; rolling back.' >&2
    exit 1
fi
if [[ ! -f "$MARKER" ]] || [[ "$(cat "$MARKER")" != 'PELICAN_SIMPLIFY_VALIDATED' ]]; then
    echo 'Pelican validation did not finish successfully; rolling back.' >&2
    exit 1
fi
sudo -u www-data php "$STAGE/prepare-policies.php" "$PANEL"
COMMITTED=1
echo "PelicanSimplify installed. Backup: $BACKUP"
echo 'Open the panel homepage and refresh with Ctrl+F5.'
echo 'First opening: complete the Simplify setup wizard. Settings remain available in the sidebar and account menu.'
echo 'Automatic backups and scheduled changes require the existing Pelican scheduler.'
echo 'Check scheduler registration: sudo -u www-data php artisan schedule:list'
echo 'Existing backup policies, grants and drafts were preserved.'
echo 'No network, website asset or environment-token configuration was changed.'
