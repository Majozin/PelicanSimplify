# Installation

Target: Pelican 1.0.0-beta38 and PHP 8.3 or newer. This is an alpha release.

## Panel import

Import a ZIP containing only the `pelican-simplify` folder through **Admin → Plugins → Import**, then click **Install**. The repository also uses Pelican's standard plugin layout, with plugin.json and src at its root. The installer checks dependencies before enabling the plugin. Open Simplify settings and finish setup after every required check passes.

Node.js 22.12+ or 24+, Yarn, the panel's PHP extensions and Composer dependencies, writable Laravel storage, a working database/cache and a persistent queue connection are required. The setup page lists failed checks and repair instructions; use **Check again** after fixing them. The configured queue check does not prove that the worker or scheduler is running. Keep both services active as required by Pelican.

Simplify supplies its own CSS/JavaScript without a separate build. Pelican's own compiled assets must exist and be readable. If the build check fails, repair Node/Yarn and run `yarn install` and `yarn build` from your Pelican directory. Browser delivery, reverse-proxy configuration and Wings operations still need testing on your installation.

## Migration from the standalone creator

The former standalone creator must not run alongside the unified creator. Use the full migration package rather than a panel import:

```bash
sudo bash INSTALL-PELICAN-SIMPLIFY.sh /var/www/pelican core
```

The path can be changed. The installer checks dependencies as the panel user before replacing files, preserves configuration and creates a rollback copy. Use the exact backup path printed during installation:

```bash
sudo bash ROLLBACK-PELICAN-SIMPLIFY.sh /var/backups/pelican-simplify-XXXXXXXX /var/www/pelican
```

## Development

Run `bash tests/run.sh` with PHP 8.3 and Node installed. For the browser theme check, run `npm ci`, `npx playwright install chromium`, then `RUN_BROWSER_TESTS=1 bash tests/run.sh`.
