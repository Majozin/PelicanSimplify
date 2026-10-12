# Roadmap

Planned work after the 0.3 betas. Scope and order may change.

## Easy server creator
A separate add-on next to the **easy Minecraft server creator**.
- Build a searchable index of games from Pelican's egg sources (the same GitHub index Pelican's *Import egg* dialog uses: Games Standalone, Games SteamCMD, Minecraft, …), so users can search "Valheim" instead of picking an egg.
- Clicking a game downloads and installs the matching egg automatically for users with the *Install missing eggs* ability (Users & access), then continues with the same guided steps as the Minecraft creator.
- Keep a small cache of the index; refresh it in the background.

## Server installation overhaul
- Show one clear state ("Egg initialising…", "Server initialising…") with progress until the server is ready, instead of the start/stop flicker during installation.
- Accept the Minecraft EULA before the first start (write `eula.txt` as part of installation) so the server does not stop several times first.

## Simplify as the whole panel
- Setup wizard option: Simplify replaces the panel. The native (legacy) panel stays reachable under `/legacy`.
- Optional: switch the legacy panel off completely. Only offered when no other plugins are installed, because other plugins add their pages to Pelican's panels.
- Depends on the Minecraft plugin installer and mod installers being finished first.

## Host mode
- Larger hosts (several nodes) can switch to host mode: servers in a compact list per node on the left, Pelican's admin pages as the main workspace.

## Phone experience
- Bottom tab bar (Servers, Create, Alerts, Account) and a top bar that collapses on scroll.
- Full-screen sheets with a drag handle instead of centred popups; the save bar sits above the tab bar.
- Server page on phones: a status header with large power buttons, swipeable sections (Console, Players, Files, Settings), and a console with a command bar above the keyboard plus quick-command chips.
- Embedded Pelican pages (files, admin) restyled for narrow screens: one column and 44 px touch targets.
- Host mode on phones: a node and server switcher instead of three columns.
- Installable web app (manifest, icons, theme color) and safe-area support for notched phones.
- Checked with automated phone screenshots (360, 390 and 430 px wide) in the browser tests.

## Server page
- Sticky status bar: name, status, address, players, uptime and the power buttons always in view.
- Layout per game: console first for modded servers, players first for busy servers; users can reorder or hide tiles.
- Workspaces (files, backups, settings, players) open as tabs inside the page instead of popups, with a back button and deep links.
- Console: filter by errors/warnings, search, pause scrolling, and highlighted player joins and crashes.
- One "needs attention" card: pending restarts, failed backups, outdated software, crash on last start.
- Activity timeline: recent starts, crashes, backups and changes in one list.
- Resource graphs with a 1 h / 24 h choice and clear limits.

