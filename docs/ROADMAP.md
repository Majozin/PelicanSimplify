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
