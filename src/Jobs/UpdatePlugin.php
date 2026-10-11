<?php
namespace Simplify\Panel\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
/** A safe plugin update started from the Plugins popup; the result arrives as a notification. */
final class UpdatePlugin implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;
    public int $timeout = 1200;
    public int $tries = 1;
    public function __construct(public string $pluginId, public ?int $userId = null) {}
    public function uniqueId(): string { return 'simplify-plugin-update-'.$this->pluginId; }
    public function handle(): void
    {
        $plugin = \App\Models\Plugin::find($this->pluginId);
        if ($plugin) \Simplify\Panel\Services\PluginUpdater::update($plugin, $this->userId);
    }
    public function failed(\Throwable $exception): void
    {
        \Simplify\Panel\Services\PluginUpdater::jobFailed($this->pluginId, 'update', $this->userId, $exception);
    }
}
