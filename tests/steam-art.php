<?php
declare(strict_types=1);
// Steam artwork lookup: egg names become store search terms, and only a real match is picked.
require __DIR__.'/../src/Services/SteamArt.php';
use Simplify\Panel\Services\SteamArt;
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
check(SteamArt::searchName('Valheim (Plus) Dedicated Server') === 'Valheim', 'egg suffixes removed');
check(SteamArt::searchName('ARK: Survival Ascended') === 'ARK: Survival Ascended', 'real names kept');
check(SteamArt::searchName('Satisfactory [Experimental]') === 'Satisfactory', 'brackets removed');
$items = [['id' => 1, 'name' => 'Valheim Soundtrack'], ['id' => 892970, 'name' => 'Valheim'], ['id' => 3, 'name' => 'Other']];
$art = SteamArt::pick($items, 'Valheim');
check(($art['appid'] ?? null) === 892970, 'exact name wins over an earlier partial match');
check(str_ends_with($art['banner'], '/892970/library_hero.jpg') && str_starts_with($art['banner'], 'https://cdn.cloudflare.steamstatic.com/'), 'banner from Steam CDN');
check(SteamArt::pick([['id' => 9, 'name' => 'Rusty Lake']], 'Rust') !== null, 'containing match accepted');
check(SteamArt::pick([['id' => 9, 'name' => 'Terraria']], 'Valheim') === null, 'unrelated result ignored');
check(SteamArt::pick([['id' => 'x', 'name' => 'Valheim']], 'Valheim') === null, 'non-numeric id ignored');
echo "PASS Steam art: search names, exact match first, unrelated results ignored, CDN-only URLs\n";
