<?php
namespace Simplify\Panel\Console\Commands;
use Illuminate\Console\Command;
use Simplify\Panel\Services\FeatureSettings;
use Simplify\Panel\Services\PluginUpdater;
/**
 * Scheduled every minute: announces each new plugin version once to root admins and, when
 * "Install plugin updates automatically" is on, installs it safely right away. Update lookups are cached for 50 seconds.
 */
final class UpdatePlugins extends Command
{
    protected $signature = 'simplify:plugin-updates {--force : Install even when automatic updates are off}';
    protected $description = 'Announce available plugin updates and install them with backup, verification and rollback.';
    public function handle(): int
    {
        if (!FeatureSettings::enabled('plugin_manager')) return self::SUCCESS;
        $install = $this->option('force') || FeatureSettings::enabled('auto_update_plugins');
        foreach (PluginUpdater::available() as $plugin) {
            PluginUpdater::announce($plugin, $install);
            if (!$install) continue;
            $result = PluginUpdater::update($plugin);
            $result['ok'] ? $this->info($result['message']) : $this->error($result['message']);
        }
        return self::SUCCESS;
    }
}
