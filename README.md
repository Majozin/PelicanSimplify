# PelicanSimplify

> [!WARNING]
> This plugin is imagined, designed and tested by me, but used an AI for coding.
>
> Create backups before installing and expect bugs and/or errors, especially in the beta builds. Plugin is made to work on top of your panel so it won't interfere with the running servers. It runs fine for me, but that's no guarantee.

A fresh interface for Pelican, with simpler server management and guided server creation. Built on [Pelican](https://github.com/pelican/panel) and [Wings](https://github.com/pelican/wings); their existing functionality is credited below.

**Beta · Pelican 1.0.0-beta38 · PHP 8.3+**

## Highlighted features

- **UI overhaul** — a unified server overview, dashboard and live console.
- **Guided server creation** — choose your game or modpack; Simplify selects the matching egg automatically.
- **Friends mode** — see every server, manage only those you own or are assigned to.
- **Make it yours** — light/dark themes and shared or individual server accent colors.

## Screenshots

<a href="docs/screenshots/server-overview.png"><img src="docs/screenshots/server-overview.png" alt="Server overview" width="280"></a>
<a href="docs/screenshots/server-dashboard.png"><img src="docs/screenshots/server-dashboard.png" alt="Server dashboard" width="280"></a>
<a href="docs/screenshots/server-creator.png"><img src="docs/screenshots/server-creator.png" alt="Guided server creation" width="280"></a>

## Getting started

Use **Admin → Plugins → Import from URL** with the [latest plugin ZIP](https://raw.githubusercontent.com/jerajerajerajera/PelicanSimplify/main/dist/pelican-simplify-latest.zip), click **Install**, then complete the setup checks. Missing dependencies block installation or setup and show how to fix them. Node.js 22.12+ or 24+, Yarn, a running queue worker and scheduler are required.

For migration from the standalone creator, see [installation notes](docs/INSTALLATION.md). This beta still needs testing on a real Pelican/Wings installation. Report problems through [Issues](https://github.com/jerajerajerajera/PelicanSimplify/issues).

## Coming features

Planned improvements; scope and release order may change.

**All existing Pelican functionality remains available in the legacy panel, including features that have not yet been implemented in Simplify.**

- **Easy game server creator** — first part available since 0.3.0-beta.20 (search 170+ games from the official Pelican eggs, automatic egg install). Still to come: EULA acceptance up front, recommended resources per game and an install progress card. See [roadmap](docs/ROADMAP.md).
- **Clearer server installation** — one clear "initialising" state until the egg and server are ready, and the Minecraft EULA accepted up front without the start/stop loop.
- **Simplify as the whole panel** — an option in the setup wizard to let Simplify replace the panel, with the legacy panel kept under /legacy or switched off when no other plugins need it.
- **Egg settings in the dashboard** — edit supported startup variables with clear descriptions and validation.
- **Dashboard alerts** — warnings for crashes and resource limits (Pelican notifications already appear in the bell).
- **Known server startup fixes** — detect common startup problems and offer guided fixes.
- **More overview customization (0.3.0)** — additional information fields and layout options.
- **Server presets** — reusable settings for faster, consistent server creation.
- **Troubleshooting reports** — export useful diagnostics with secrets removed to make support easier.

## Full feature list

<details>
<summary><strong>Expand all features</strong></summary>

**Implemented from Pelican** marks existing Pelican/Wings functionality presented or reused by Simplify. Other entries describe Simplify additions; server hosting, permissions and game operations still run through Pelican/Wings.

### Installation and first setup

- Native Pelican plugin import by URL or ZIP — **Implemented from Pelican**
- Simplify latest-version ZIP and update feed using the native updater — **Implemented from Pelican**
- Installation preflight and blocking dependency checks
- PHP version, extensions and Composer package checks
- Node.js, Yarn and compiled CSS/JavaScript checks
- Database, cache, queue configuration and writable storage checks
- Upload-capacity checks for PHP and Livewire
- NGINX/proxy upload-limit guidance
- Repair instructions and repeatable checks
- Separate first-setup stages and numbered navigation
- Appearance, access, feature and resource-default setup pages
- Optional system diagnostics in settings
- Asset rebuilding and compiled-view cache clearing
- Migration, rollback and backup/reset scripts

### Server overview and host health

- Personal appearance dropdown: grid/list layout, MOTD visibility, large server/egg icon, CPU/RAM tiles and quick-action visibility
- Appearance preferences remembered per user in the browser
- Grid and list views
- Saved desktop view preference and automatic mobile list view
- Dynamic game filters
- Server names, aliases, icons and game badges
- Live server status from Wings (**Implemented from Pelican**) and Minecraft player counts
- CPU and memory history graphs
- Host CPU, RAM, storage and gameserver network traffic
- Healthy, high-load, critical, maintenance and unavailable states
- Animated upward expansion into a fixed-height workspace: node health, inline expandable server details and internal scrolling
- Upload/download rates beside cumulative network usage
- Reporting-host counts and partial-availability handling
- Animated, permission-aware power actions inside grid and list server cards
- Separate native and guided creator entry points

### Guided server creation

- Minecraft creation wizard with numbered steps and review
- Guided Vanilla, Paper and modpack setup using existing eggs and native server creation — **Implemented from Pelican**
- Automatic matching egg selection; bundled eggs imported through Pelican (upstream egg licenses retained)
- Game version and modpack release selection
- Modrinth, CurseForge, FTB and supported Technic modpacks
- Bird-name generator — **Implemented from Pelican**
- Per-server accent-color choice and suggestions
- Server icon and MOTD setup
- Clickable RAM, CPU and disk presets in step 2
- Configurable defaults for Vanilla, Vanilla+ and modpacks
- Resource presets reset when changing server type
- Server/resource allowances and quota validation
- Advanced resource options
- Saved drafts, autosave, recovery and discard
- Native creator popup for other games — **Implemented from Pelican**

### Server dashboard and console

- Game-aware management tiles
- Server aliases with an icon-only editor
- Game, software, Minecraft version and modpack information
- Live Wings console and connection indicator — **Implemented from Pelican**
- Console commands, command history and log clearing — **Implemented from Pelican**
- Start, restart, stop and force-stop controls — **Implemented from Pelican**
- CPU, memory, storage and received/sent network traffic — **Implemented from Pelican**
- Resource history graphs
- Minecraft online-player list and player heads
- Copyable server address — **Implemented from Pelican**
- Resource editing with permission and quota checks
- Profile popup and account menu — **Implemented from Pelican**
- Links back to the native panel and Simplify
- Build version in the footer

### Appearance and navigation

- Light, dark and system themes
- Shared panel accent or individual server colors
- Automatic colors for existing servers
- Always-dark or adaptive console
- Bundled Minecraft banners
- Automatic generic gaming banner for other games
- Faded broken-heart artwork for offline servers
- Custom JPG, PNG and WebP banner uploads
- Banner picker and dedicated settings library
- Upload, apply and remove banners from settings
- Permanent bundled banner collection
- Server-accent styling and outline icons in embedded workspaces
- Separate standalone settings styling
- Responsive menus, tiles and popup layouts
- Content-ready loading to prevent an unstyled page flash
- Pelican favicon and Simplify GitHub credit links

### Minecraft settings and software

- MOTD color/formatting helper with a live Minecraft-style preview
- Narrow two-line, formatted MOTD panels on the server dashboard and in the editor preview
- Unchanged settings do not create restart reminders
- MOTD and server icon
- Player capacity, difficulty and game mode
- View distance and spawn protection
- PvP, whitelist and flight toggles
- Whitelist, operator, kick and ban management
- Software/version and modpack reinstallation
- Recent successful-backup requirement for software changes
- Staged settings and restart reminders
- Immediate or scheduled restart to apply changes
- Pending-change discard and settings backups
- Detection of conflicting server.properties changes
- Modrinth plugin/mod search and compatible-release selection
- Package installation, tracked updates and removal
- Archive copies of replaced or removed package files
- Native startup-variable workspace for other games — **Implemented from Pelican**

### Files and management workspaces

- Embedded native file browser and uploads — **Implemented from Pelican**
- File list beside the editor
- Fixed-height file/editor panes with internal scrolling
- Save closes the editor while preserving the file browser
- Unsaved-edit warning and keyboard save shortcut — **Implemented from Pelican**
- File revision check before saving
- Saved-file restart reminder in the shared dashboard banner
- Compact SFTP details, username copy and connection link — **Implemented from Pelican**
- Scheduled-task and activity-log workspaces — **Implemented from Pelican**
- Database and server-access links where permitted — **Implemented from Pelican**
- Runtime/container and startup details — **Implemented from Pelican**
- Confirmed server deletion — **Implemented from Pelican**

### Backups and automation

- Simplify recovery-point overview using native backups — **Implemented from Pelican**
- Automatic backup scheduling
- Configurable intervals and retention
- Pause/resume automatic backups
- Successful-backup preservation when a backup fails
- Backup download and restore — **Implemented from Pelican**
- Native administrator backup management — **Implemented from Pelican**

### Access and settings

- Switchable Friends mode
- Native ownership and subuser permission checks — **Implemented from Pelican**
- Locked cards for servers you cannot manage
- Administrator-only plugin settings
- Per-feature visibility switches
- Separate user access to legacy, advanced and software controls
- User switches for files, settings, resources, players and packages
- User switches for backups, schedules and activity
- Optional self-service server creation
- Shared, per-role and per-user server, CPU, RAM, disk and backup allowances
- Specific user limits override roles; multiple roles use the highest allowance per resource
- Administrator-managed account/sharing/schedule restrictions
- Dedicated settings categories and banner tab

### Optional integrations

- Website-extension adapter when an extension is installed
- Website visibility and presentation controls
- Supported subdomain requests

</details>

MIT licensed. Bundled game eggs retain their upstream licenses.
