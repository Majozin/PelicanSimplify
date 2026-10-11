<?php
declare(strict_types=1);
// The bundled egg index: every game points to a pinned official egg with a checksum, ids are unique.
$index = json_decode((string) file_get_contents(__DIR__.'/../resources/eggs/game-index.json'), true, 512, JSON_THROW_ON_ERROR);
$games = $index['games'] ?? [];
if (count($games) < 100) throw new RuntimeException('egg index unexpectedly small');
$ids = [];
foreach ($games as $game) {
    if (!preg_match('#^https://raw\.githubusercontent\.com/pelican-eggs/[a-z-]+/[0-9a-f]{40}/.+\.json$#', (string) ($game['url'] ?? ''))) throw new RuntimeException('unpinned or foreign egg url: '.($game['url'] ?? '?'));
    if (!preg_match('/^[0-9a-f]{64}$/', (string) ($game['sha256'] ?? ''))) throw new RuntimeException('missing checksum for '.$game['name']);
    if (trim((string) ($game['name'] ?? '')) === '' || isset($ids[$game['id']])) throw new RuntimeException('empty name or duplicate id '.$game['id']);
    $ids[$game['id']] = true;
}
echo 'PASS egg index: '.count($games)." pinned games with checksums and unique ids\n";
