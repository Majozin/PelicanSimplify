# Changes

## 0.2.0-alpha.8

- Fixed release ZIP timestamps so updated files no longer share the same date as older builds.
- Clear compiled Blade views during installation and updates so the new screens appear immediately.

## 0.2.0-alpha.7

- Show the installed build number beside Enhanced by Simplify in the copyright footer.
- Replaced the README disclaimer image with a native GitHub warning block.

- Added a Go to Simplify Panel button with a dashboard icon to the legacy server overview.
- Added a matching return button beside the admin and legacy topbar controls, with an icon-only mobile layout.
- Kept the current server selected when returning from its native pages; embedded workspaces hide the button.

## 0.2.0-alpha.6

- Split first setup into separate categories with the same numbered stepper and controls as server creation.
- Organized settings into category tabs with grouped columns.
- Made Vanilla, Vanilla+ and modpack CPU/RAM/disk defaults configurable in setup and settings. Existing resource allocations remain unchanged.
- Updated the README AI disclaimer with the requested orange notice and backup guidance.

## 0.2.0-alpha.5

- Redesigned first-run checks into compact cards with a separate first-setup button.
- Added a dedicated navigation icon and popup for Simplify settings; diagnostics stay hidden by default.
- Restored automatic backgrounds for existing Minecraft servers without the Web extension.
- Added a background picker next to the server color control.
- Automatically assigned distinct saved colors to servers without one when individual colors are enabled.

## 0.2.0-alpha.4

- Added a verified plugin backup/reset/restore script to reopen the setup wizard.
- Added a stable latest ZIP URL for native panel imports.
- Made README screenshots smaller, clickable previews.

## 0.2.0-alpha.3

- Moved plugin.json, src, resources and database to the repository root for Pelican Hub submission and native source-archive imports.
- Updated packaging, migration preflight and regression paths for the standard plugin layout.

## 0.2.0-alpha.2

- Added blocking dependency checks to the native installer, migration installer and setup/settings save.
- Validate PHP extensions, Composer/icons, Node/Yarn, actual compiled CSS/JS, storage, database/cache and queue configuration.
- Show failed requirements with repair instructions and a Check again action.
- Shortened the README, added highlighted features, screenshots, full feature list and AI disclosure.

## 0.2.0-alpha.3

- Folded the standalone creator into Simplify; removed duplicate services and game resources.
- Added a shared feature registry, grouped settings and first-install wizard.
- Added configurable Friends mode and ordinary-user workspace visibility.
- Added a global theme manager, shared controls, optional legacy styling and console modes.
- Extracted private Web navigation, creator fields, settings and subdomain UI into the separate extension.
- Removed installation-specific branding, domains and paths. Panel branding and bird-name examples are dynamic.
- Added request-scoped settings resolution, bounded maintenance, batched assignment checks, hidden-tab refresh suppression and shared cached assets.
- Added migration of legacy queued-job namespaces and backward-compatible reads of existing metadata and drafts.
- Added feature/access and theme regression tests and a reproducible check runner.
