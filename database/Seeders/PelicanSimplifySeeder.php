<?php
declare(strict_types=1);
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Simplify\Panel\Services\DependencyCheck;
final class PelicanSimplifySeeder extends Seeder
{
    public function run(): void
    {
        // Pelican runs seeders before enabling a plugin, including on updates.
        // Pelican treats build failures as warnings for non-theme plugins.
        // Repeat with strict failure handling so a broken build cannot enable Simplify.
        app(\App\Services\Helpers\PluginService::class)->buildAssets(true);
        DependencyCheck::assertReady(DependencyCheck::inspect(base_path(), plugin_path('pelican-simplify')));
    }
}
