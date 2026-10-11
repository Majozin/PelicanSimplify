<?php
namespace Simplify\Panel\Console\Commands;
use Illuminate\Console\Command;
/**
 * Second half of a safe plugin update, run in a fresh PHP process so the new plugin code is used: install
 * (migrations, seeder, assets) and confirm the plugin class loads. Exits non-zero on any failure, unlike
 * Pelican's own install command, so PluginUpdater can roll back.
 */
final class InstallPlugin extends Command
{
    protected $signature = 'simplify:plugin-install {id} {--enable}';
    protected $description = 'Install an updated plugin and verify that it loads (used by Simplify\'s safe updater).';
    public function handle(\App\Services\Helpers\PluginService $plugins): int
    {
        try {
            \App\Models\Plugin::refreshRows();
            $plugin = \App\Models\Plugin::findOrFail((string) $this->argument('id'));
            $plugins->installPlugin($plugin, (bool) $this->option('enable'));
            $plugin = $plugin->refresh();
            if (in_array($plugin->status->value, ['errored', 'incompatible', 'not_installed'], true)) throw new \RuntimeException($plugin->status_message ?: 'The plugin reports status '.$plugin->status->value.'.');
            if (!class_exists(ltrim($plugin->fullClass(), '\\'))) throw new \RuntimeException('The plugin class '.$plugin->fullClass().' could not be loaded.');
            $this->call('view:clear');
            $this->info('ok');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
