# Changes

## 0.3.0-beta.3
- Fix Next in the server creator for real (verified on Pelican 1.0.0-beta38 / Filament 5 / Livewire 4): the hidden allocation field lost its options on single-node panels, and the draft step watcher lost track when Livewire 4 re-rendered the wizard.
- Embedded pages no longer flip the dashboard to light mode (Filament's start-up theme event is ignored).
- Popups with Pelican pages: no frames inside frames, no second page title, Simplify icon buttons.
- Files: the page fits the popup and only the file list scrolls; toolbar and pagination stay in place.
- File editor: fills the space beside the list with only the code scrolling; Simplify header with file name, Save and Close.
- Pelican's own notifications appear in the Simplify bell (mark read / mark all read); notifications inside popups become Simplify toasts.
- Suggested server accent colours are pastel; custom colours stay free.
- Simplify settings reorganised into General, Appearance, Users & access, Server creation and Backups & performance, with side tabs.
- System checks only on the legacy settings page; new "Run setup wizard again" button there.
- The file revision check reports an unreachable node instead of failing.

## 0.3.0-beta.2
- Fix Next in the server creator: fields that are hidden in the browser (custom RAM, backup schedule) are no longer validated, and CPU/disk presets always win over hidden custom values.
- Panel name follows the accent colour; server names fade from white (ink) into their accent.
- MOTDs use the Minecraft-style Monocraft font (SIL OFL) with their dark background on overview cards.
- Offline artwork is faded and blurred like background banners.
- Server and egg icons swap place and size with a short animation when "Egg icon" is chosen.
- New "Large name" choice in the overview menu: alias or server name.
- Dark tooltips and game labels in dark mode.
- Compact list rows (about 68 px) with the MOTD beside the name.
- Quick actions sit on the card background with regular grey buttons.
- Remove leftover backgrounds behind the footer on the overview.

## 0.3.0-beta.1
- Rebuild the frontend on one design system: shared tokens, buttons, fields, switches, badges, dropdowns, popups, tooltips and toasts, used by the dashboard, creator, settings and embedded Pelican pages.
- Replace every browser confirm, prompt and hover text with Simplify dialogs and tooltips; add a notification bell for pending changes, health warnings, updates and notices.
- Give popups fixed sizes with a fixed header and footer; only the body scrolls, and the file manager uses a single scroll container that fits the popup.
- Style embedded Pelican pages before they paint, in the current theme and accent.
- Reorganise server settings into General, Gameplay and MOTD tabs.
- Show plugin updates in Simplify style in the notification bell and Simplify settings.
- Add Performance settings for overview, server, scheduled-restart and host-health intervals and for automatic backup defaults.
- Pause refreshes while a popup is open or the tab is hidden; back off websocket reconnects up to five minutes.
- Cache missing icons, MOTDs, failed lookups and host health; send server icons as cached URLs instead of base64 on every refresh.
- Serve assets and backgrounds as versioned, long-cached files with ETags.
- Make read-only dashboard actions renderless and build the initial dashboard state once per request.
- Automatic backups default to every 6 hours and skip servers that have not run since the last backup.
- Speed up the server creator: fewer live fields, memoised lookups, debounced draft saves and reactive step tracking.
- Keep only the current release archive in dist/; packages no longer ship screenshots.
- Fix saved-message reporting, repeated settings toasts, truncated favicons, Packages metadata paths, the 24-hour scheduler lock and Server creation hooks throwing on errors.
- Remove dead code: the legacy create redirect, unused catalog and settings helpers and old stylesheets and scripts.

## 0.2.0-alpha.26
- Replace tiny beak dots with two distinct four-point sparkles in the favicon and legacy navigation icon.
- Center health status between its heading and reporting count, enlarge the heart and clear footer-hook backgrounds.
- Add accent swatches, custom colors and editable hex values to Appearance settings.
- Carry global accent colors through Other game creation and aggregate health; tint server-name gradients with their accent.
- Add a personal switch to keep Minecraft creation green or use the global accent.
- Match copyright background to the page and darken quick-action buttons.
- Compact desktop list rows and show original names beside aliases, preserving two-line MOTDs and nearby resource widgets.

## 0.2.0-alpha.25
- Match solid quick-action bars to the page background in both themes.
- Show avatar and username in a wider profile button; reveal inline profile-settings and logout icons instead of an account dropdown.
- Move Light, Dark and System theme controls into the appearance dropdown, also available on server pages.
- Remove surrounding overview and legacy page backgrounds behind the health dock.
- Align Host health to the rendered server grid, including platform scrollbar widths.
- Increase aggregate health icon sizes and padding.
- Balance free space above and below the MOTD while keeping metrics beside player counts.
- Use shorter grid cards without CPU/RAM tiles, with room for quick actions when enabled.

## 0.2.0-alpha.24
- Anchor grid metrics above player counts and place list metrics farther right, preserving title/icon positions during power-control reveal.
- Align health edges with the server cards and confine footer background to the copyright row.
- Health details grow to their content height up to a viewport-aware cap, scroll internally and close on outside clicks.
- Add a quick-action visibility preference; solid action bars join the card edges and shift artwork aside.
- Give the aggregate health row slightly more height.
- Add a personal overview dropdown with layout, MOTD, primary icon and CPU/RAM tile preferences saved per user in the browser.
- Move grid/list controls into the header dropdown and use a gear for panel settings.


## 0.2.0-alpha.23
- Reduce collapsed Host health padding, typography and icons to keep the dock compact.
- Remove the empty strip above Host health; its soft shadow overlays the scrolling server region.
- Shorter list cards use smaller server icons, software icons alongside and raised titles.
- Taller overview grid cards retain logos and compress their contents when quick controls appear, without hover lift.
- Original server names appear in parentheses beneath aliases.
- Compact, borderless node summaries show four metrics in one row, with node icons and persistent expanded highlights.


## 0.2.0-alpha.22
- Overview titles shrink to fit; aliases and original names use consistent stacked rows.
- Software labels move into styled icon tooltips with reported versions.
- Inline cancel/confirm controls for draft deletion and overview power actions.
- Batch wizard draft autosaves and ignore draft indicator mutations when sizing popups.


## 0.2.0-alpha.21

- Confirm every overview quick power action before sending a command.
- Compact resource presets with larger icons beside their values and descriptions underneath.
- Build the creator progress bar from available steps; hide website display without its helper and respect disabled backups.
- Preserve draft stages by their names when optional plugins change.
- Avoid repeated iframe height writes when creator content has not changed; retain content shrinking and popup scroll positions.
- Lift grid server icons and place egg icons underneath server icons in list view.
- Remove MOTD rendering from overview while keeping its data for future configurable cards in 0.3.0.
- Use a narrow two-line MOTD display and support multiline MOTDs in the creator.
- Remove native copyright footer hook backgrounds and excessive wrapper height; tighten copyright spacing and add a shadow above host health.

## 0.2.0-alpha.20

- Give expanded host health a fixed, viewport-bounded detail area with internal scrolling.
- Keep every node visible; expand each node’s servers inline, with independent animated accordions.
- Preserve aggregate metrics and the upward expansion animation while expanding nodes.

## 0.2.0-alpha.19

- Redirect to the overview after deletion, without rendering the deleted server.
- Keep health and the transparent copyright footer docked on mobile; scroll inside the server list.
- Reveal quick power actions inside the same-size cards, with dark buttons and a slightly lighter dark background.
- Add a small sparkle beside the pelican beak in the favicon and legacy navigation icon.
- Repeat bump and shine effects on each hovered creator logo; respect reduced motion.
- Smaller offline heart further right in grid; shift the list heart slightly right.

## 0.2.0-alpha.18

- Expand host health upward with animated node details and visible-server resource rows.
- Show sampled upload/download rates alongside container traffic totals, with reset-safe calculations.
- Add permission-aware overview power controls below grid cards and alongside list rows.
- Refine offline heart size and position separately for grid and list views.
- Avoid restart reminders for unchanged settings, equivalent Minecraft values and unchanged allocations.
- Add MOTD color/formatting tools and safe live previews; show formatted MOTDs in dark overview and dashboard panels.
- Scale list top spacing with viewport height.

## 0.2.0-alpha.17

- Move clickable RAM, CPU and disk presets into creator step 2; reload configured type defaults when returning to step 1 and changing type.
- Add per-role and per-user allowances with backend quota enforcement and explicit access grants.
- Preserve popup scroll positions during embedded resizing and restore usable fixed file/editor heights.
- Use a filled broken-heart silhouette fading into the right side of offline cards.
- Credit existing Pelican/Wings functionality throughout the README feature list.

## 0.2.0-alpha.16

- Align the host health status and open per-node CPU, memory, storage and gameserver traffic details from the health tile.
- Display offline player counts as 0 / 0 and fade a large broken-heart symbol into offline artwork.
- Link header/footer Simplify credits to GitHub with inherited color and an underline.
- Group the complete feature inventory inside a collapsible README section.

## 0.2.0-alpha.15

- Remove leaked popup helper sections, empty footers and creator header spacing.
- Adapt server menus to game capabilities; hide Minecraft-only software and player controls.
- Show temporary gray offline artwork and a replaceable game-controller banner for other games.
- Add received/sent network traffic to host health and server resource graphs.
- Set the plugin author to jera.
- Keep SFTP details compact and inline, with a connection link beside them.
- Use fixed file viewer/editor heights with internal scrolling; record file saves in the shared restart banner.
- Allow uploads directly in the settings banner library, with server selection and optional immediate use.
- Keep host health width independent of server grid size and use a smaller icon-only alias action; show aliases on overview cards too.
- Match both creator hover patterns in their own accent colors.
- Replace embedded action and file icons with Simplify outline icons.

## 0.2.0-alpha.14

- Align dark iframe control colors with Simplify and use the panel accent in overview workspaces.
- Retain the alpha 13 features and correct its browser regression fixture.


## 0.2.0-alpha.13

- Show advisory PHP/Livewire upload capacity and NGINX/proxy configuration guidance; cap banner uploads at the detected application limit.
- Add a dedicated iframe stylesheet with server accent colors and shared control/icon styling.
- Keep the file list visible while its native editor opens alongside it; save closes only the editor, preserving native warnings and permissions. Protect unsaved changes.
- Show expandable, permission-gated SFTP details with copy and connect actions.
- Enlarge the favicon pelican, tint Offline badges light red and add editable display aliases beside actual server names.
- Place Server banners on its own tab inside Settings.


## 0.2.0-alpha.12

- Stop popup iframe height feedback loops; resize when content grows or shrinks.
- Fix mobile navigation stacking, touch targets and menu overflow.
- Replace the account settings icon with a Profile button; allow authenticated same-origin profile embedding and retain save actions.
- Move Server banners into a dedicated header menu button and popup.


## 0.2.0-alpha.11

- Wait for dashboard initialization, styles, fonts and visible images before showing the page, preventing refresh flashes.
- Include the page loader and standalone settings stylesheet in blocking installation checks.

## 0.2.0-alpha.10

- Outline the server title tile in its server accent color.
- Reuse the outline navigation pelican in the favicon on dark gray.
- Upload and manage server banners with validated JPG/PNG/WebP images; retain the entire bundled collection.
- Add uploaded banner management to settings and server image pickers, with server permission checks.
- Use content-sized embedded workspaces and one scrolling popup area; remove native shell spacing.
- Keep popups open until their close icon is used, including after saving.
- Style standalone settings separately from embedded settings.
- Open Other game creation in an embedded native creator until its configurator is ready.

## 0.2.0-alpha.9

- Place one Simplify return button before notifications on the legacy overview and admin topbar; keep the admin topbar visible.
- Add a permission-aware creator menu with native and Simplify choices, and stop redirecting native creation.
- Preserve native legacy styling while matching embedded popup backgrounds and removing nested spacing.
- Fit the settings popup to its contents and show diagnostics below settings with a close button.
- Keep creator tiles stationary on hover and isolate each tile’s hover layout.
- Add sharp gray pelican favicon artwork, an outline Simplify navigation icon and sparkle accents.

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
