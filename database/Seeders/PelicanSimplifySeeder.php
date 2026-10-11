<?php
declare(strict_types=1);
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Simplify\Panel\Services\DependencyCheck;
final class PelicanSimplifySeeder extends Seeder
{
    public function run(): void
    {
        // Pelican runs seeders before enabling a plugin, including on updates, and builds its own
        // assets itself. Simplify ships ready-made CSS/JS, so no second (memory-hungry) build runs here.
        // Native updates clear Filament component caches, but not compiled Blade
        // views. Clear these as well, including views from older fixed-mtime ZIPs.
        if (Artisan::call('view:clear') !== 0) {
            throw new \RuntimeException('Could not clear compiled views after updating Simplify.');
        }
        DependencyCheck::assertReady(DependencyCheck::inspect(base_path(), plugin_path('pelican-simplify')));
    }
}
