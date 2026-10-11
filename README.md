# PelicanSimplify 0.2

Simplify combines a server dashboard and an optional easy server creator in one Pelican plugin. The separate Web extension is private and is not part of the public source package.

**Status: 0.2.0-alpha.1. Target: Pelican 1.0.0-beta38 and PHP 8.3.** Local lint and regression checks pass. This version still requires a real Pelican/Filament/Wings integration test before a stable release. The architecture and integration layers are rewritten; working game, quota and backup algorithms from the previous version are retained as single shared implementations.

## Features

- One core plugin, one namespace, one shared game catalog and one set of services.
- A first-install wizard and grouped settings in the left navigation and account menu. Only the root administrator can change installation-wide settings.
- Friends mode: all signed-in users see server summaries, while opening and managing a server still requires ownership, assignment or administrator access. Switch it off to list only owned and assigned servers.
- Independent user switches for legacy mode, advanced tools, software changes, resources, game settings, files, players, backups, packages, activity and schedules. Advanced, software, schedules and legacy access are off for ordinary users by default. Enabling a feature does not grant any Pelican permission.
- Shared light/dark/system appearance for the dashboard, creator and embedded native pages. The black console can be kept in both themes or made adaptive.
- Optional easy creation, packages, player management, resource controls, software changes, host health and automatic backups.
- Panel name from `app.name`; bird-name examples and randomization use Pelican's `RandomWordService`. No installation-specific names, addresses or paths are included.
- Web contributes its own settings, fields, navigation and presentation only when installed and enabled.

Appearance settings include a panel accent, a switch between uniform and separate server colors, and a server color picker. Turning separate colors off preserves each server’s saved color.

## Installation and upgrade

Extract the package and run:

```bash
sudo bash INSTALL-PELICAN-SIMPLIFY.sh /var/www/pelican core
```

The path can be changed for another installation. The installer validates checksums, preserves existing plugin configuration, saves a rollback copy and retires the former standalone `simple-server-creator` folder. Existing encrypted drafts, quota grants, appearances and metadata remain on their existing paths. A dynamic compatibility mapping preserves queued property jobs from a previous namespace.

Open the panel as the root administrator and complete **Features → Access → Limits**. Settings can be changed later without reinstalling. Shared Web options appear only if the private Web extension is installed. Public downloads contain no Web folder; `all` is only valid for the private two-plugin bundle.

Rollback uses the exact backup path printed during installation:

```bash
sudo bash ROLLBACK-PELICAN-SIMPLIFY.sh /var/backups/pelican-simplify-XXXXXXXX /var/www/pelican
```

Pelican's existing queue worker and scheduler are required for queued settings and automatic backups. Server creation accepts the Minecraft EULA, queues settings before the first start, and leaves the server stopped.

## Runtime cost

Settings and plugin-state reads are request-scoped. Assignment visibility uses a database existence projection instead of a separate subuser query for each card. Hidden browser tabs skip summary refreshes; overlapping refreshes are prevented. The console retains its existing single WebSocket. Shared CSS/JS is browser-cacheable instead of embedded repeatedly, and frame resize mutations are coalesced per animation frame. Offline servers skip Minecraft TCP status probes. Maintenance reads policy and pending-work files and queries candidate servers in batches of 100 instead of loading every server and all relations at once.

These are code-level reductions; no CPU, memory or latency percentage is claimed. Resource use must be measured on a real installation with a representative number of servers and viewers.

## Development

```bash
bash tests/run.sh
```

PHP 8.3 and Node.js are required. Tests use explicit boundary doubles and do not create real game servers. For the optional rendered theme check:

```bash
npm ci
npx playwright install chromium
RUN_BROWSER_TESTS=1 bash tests/run.sh
```

`check-panel.php` also validates real plugin bootstrapping, routes and compiled Blade templates during installation. Browser appearance, native Filament form behavior, server creation and Wings operations must still be verified on the target panel.

See [Pelican plugin documentation](https://pelican.dev/docs/panel/advanced/plugins/) for the native plugin installation and discovery model. Bundled game eggs retain their original upstream license and source notices.
