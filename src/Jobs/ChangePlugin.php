<?php
namespace Simplify\Panel\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
/** Install or uninstall started from the Plugins page; success and failure both arrive as a notification. */
final class ChangePlugin implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;
    public int $timeout = 1200;
    public int $tries = 1;
    public function __construct(public string $pluginId, public string $action, public ?int $userId = null) {}
    public function uniqueId(): string { return 'simplify-plugin-change-'.$this->pluginId; }
    public function handle(): void
    {
        $plugin = \App\Models\Plugin::find($this->pluginId);
        if ($plugin) \Simplify\Panel\Services\PluginUpdater::change($plugin, $this->action, $this->userId);
    }
    public function failed(\Throwable $exception): void
    {
        \Simplify\Panel\Services\PluginUpdater::jobFailed($this->pluginId, $this->action, $this->userId, $exception);
    }
}
