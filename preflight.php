<?php
declare(strict_types=1);
$panel = rtrim($argv[1] ?? '/var/www/pelican', '/');
try {
    require $panel.'/vendor/autoload.php';
    require_once __DIR__.'/pelican-simplify/src/Services/DependencyCheck.php';
    $checks = \Simplify\Panel\Services\DependencyCheck::inspect($panel, __DIR__.'/pelican-simplify', false);
    foreach ($checks as $check) echo ($check['ok'] ? 'PASS ' : 'FAIL ').$check['name'].($check['ok'] ? '' : ': '.$check['fix'])."\n";
    \Simplify\Panel\Services\DependencyCheck::assertReady($checks);
} catch (\Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); }
