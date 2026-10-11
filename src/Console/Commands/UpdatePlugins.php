<?php
namespace Simplify\Panel\Console\Commands;
use Illuminate\Console\Command;
/** Scheduled: installs available plugin updates safely when "Install plugin updates automatically" is on. */
final class UpdatePlugins extends Command
{
    protected $signature = 'simplify:plugin-updates {--force : Run even when automatic updates are off}';
    protected $description = 'Install available plugin updates with backup, verification and rollback.';
    public function handle(): int
    {
        if (!$this->option('force') && !\Simplify\Panel\Services\FeatureSettings::enabled('auto_update_plugins')) return self::SUCCESS;
        foreach (\Simplify\Panel\Services\PluginUpdater::available() as $plugin) {
            $result = \Simplify\Panel\Services\PluginUpdater::update($plugin);
            $result['ok'] ? $this->info($result['message']) : $this->error($result['message']);
        }
        return self::SUCCESS;
    }
}
