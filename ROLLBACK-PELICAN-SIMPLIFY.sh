#!/usr/bin/env bash
set -Eeuo pipefail
[[ $EUID -eq 0 ]] || { echo 'Run with sudo.' >&2; exit 1; }
BACKUP=${1:?Specify the backup directory printed by the installer.}
PANEL=${2:-/var/www/pelican}
[[ -d "$BACKUP" && -f "$BACKUP/installed-plugins.txt" && -f "$PANEL/artisan" ]] || { echo 'Invalid backup or panel.' >&2; exit 1; }
while IFS= read -r ID; do
    case "$ID" in pelican-simplify|simple-server-creator|server-directory) ;; *) echo 'Invalid plugin in backup.' >&2; exit 1;; esac
    [[ ! -L "$PANEL/plugins/$ID" && ! -L "$BACKUP/$ID" ]] || { echo 'Refusing symlink.' >&2; exit 1; }
done < "$BACKUP/installed-plugins.txt"
QUARANTINE=$(mktemp -d /var/backups/pelican-simplify-rollback-XXXXXXXX)
while IFS= read -r ID; do
    if [[ -d "$PANEL/plugins/$ID" ]]; then mv "$PANEL/plugins/$ID" "$QUARANTINE/$ID"; fi
    if [[ -d "$BACKUP/$ID" ]]; then cp -a "$BACKUP/$ID" "$PANEL/plugins/$ID"; fi
done < "$BACKUP/installed-plugins.txt"
cd "$PANEL"
for ACTION in config:clear route:clear view:clear event:clear filament:clear-cached-components; do sudo -u www-data php artisan "$ACTION"; done
if [[ -f "$BACKUP/backup-policy-before.json" ]]; then
    php "$(dirname -- "${BASH_SOURCE[0]}")/restore-backup-policy.php" "$PANEL" "$BACKUP/backup-policy-before.json"
fi
sudo -u www-data php artisan queue:restart
echo "Previous plugins restored. Replaced files retained at: $QUARANTINE"
echo 'Refresh your browser with Ctrl+F5.'
