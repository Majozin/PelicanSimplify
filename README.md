# PelicanSimplify

![This plugin is thought of, designed and tested by me, but used AI for coding. May contain bugs or errors.](docs/ai-disclaimer.svg)

A fresh interface for Pelican, with simpler server management and guided server creation.

**Alpha · Pelican 1.0.0-beta38 · PHP 8.3+**

## Highlighted features

- **UI overhaul** — a unified server overview, dashboard and live console.
- **Guided server creation** — choose your game or modpack; Simplify selects the matching egg automatically.
- **Friends mode** — see every server, manage only those you own or are assigned to.
- **Make it yours** — light/dark themes and shared or individual server accent colors.

## Screenshots

![Server overview](docs/screenshots/server-overview.png)
![Server dashboard](docs/screenshots/server-dashboard.png)
![Guided server creation](docs/screenshots/server-creator.png)

## Getting started

Download the [plugin ZIP](https://github.com/jerajerajerajera/PelicanSimplify/raw/refs/heads/main/dist/pelican-simplify-v0.2.0-alpha.3.zip), import it in **Admin → Plugins**, click **Install**, then complete the setup checks. Missing dependencies block installation or setup and show how to fix them. Node.js 22.12+ or 24+, Yarn, a running queue worker and scheduler are required.

For migration from the standalone creator, see [installation notes](docs/INSTALLATION.md). This alpha still needs testing on a real Pelican/Wings installation. Report problems through [Issues](https://github.com/jerajerajerajera/PelicanSimplify/issues).

## Full feature list

- **Overview:** grid/list views, game filters, saved desktop preferences, mobile list view, server status, player counts, CPU/RAM graphs and host health.
- **Dashboard:** server banners and artwork, game/version details, live console, start/restart/stop/kill controls, resource metrics and player list.
- **Creation:** Minecraft or other games, Vanilla/Paper/modpacks, automatic egg selection, bundled egg import, game/version selection, bird-name generator, server colors, saved drafts, resource limits and review before creation.
- **Minecraft:** Modrinth, CurseForge, FTB and supported Technic packs; software/version changes, game settings, MOTD, whitelist, operators and player moderation.
- **Management:** files and SFTP, mods/plugins, backup recovery points, automatic backup policies and retention, schedules, activity and advanced workspaces, subject to native Pelican permissions.
- **Appearance:** light/dark/system theme, shared styling, optional legacy styling, always-dark or adaptive console, panel accent and separate server colors.
- **Access:** switchable Friends mode, administrator settings, individual user-feature switches, optional self-service creation and per-user server/resource allowances.
- **Setup:** PHP/extensions, Composer and icons, Node/Yarn, writable storage, database/cache, queue configuration and validation of compiled CSS/JavaScript; failed checks block progress and can be rerun.

MIT licensed. Bundled game eggs retain their upstream licenses.
