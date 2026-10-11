<?php
declare(strict_types=1);
namespace Simplify\Panel\Filament\Admin\Pages;

use App\Models\Plugin;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Simplify\Panel\Services\PluginUpdater;

/** Simplify's plugin overview: status, versions and safe updates with rollback. Opens from Admin tools. */
final class ManagePlugins extends Page
{
    protected static ?string $title = 'Plugins';
    protected static ?string $slug = 'simplify-plugins';
    protected string $view = 'pelican-simplify::admin-plugins';
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool { return (bool) user()?->can('viewAny', Plugin::class); }

    private function canChange(Plugin $plugin): bool { return (bool) user()?->can('update', $plugin); }

    /** Rows for the view; update lookups are cached for ten minutes. */
    public function plugins(): array
    {
        Plugin::refreshRows();
        return Plugin::query()->orderBy('load_order')->orderBy('name')->get()->map(function (Plugin $plugin): array {
            $latest = $plugin->status->value === 'not_installed' ? null : PluginUpdater::latestVersion($plugin);
            return [
                'id' => $plugin->id,
                'name' => $plugin->name,
                'author' => $plugin->author,
                'description' => $plugin->description,
                'version' => $plugin->version,
                'latest' => $latest && version_compare($latest, $plugin->version, '>') ? $latest : null,
                'status' => $plugin->status->value,
                'message' => $plugin->status_message,
                'can_change' => $this->canChange($plugin),
                'updating' => \Illuminate\Support\Facades\Cache::has('simplify.plugin-update-queued.'.$plugin->id),
            ];
        })->all();
    }

    public function updatePlugin(string $id): void
    {
        $plugin = Plugin::query()->findOrFail($id);
        abort_unless($this->canChange($plugin), 403);
        \Illuminate\Support\Facades\Cache::put('simplify.plugin-update-queued.'.$id, true, 1200);
        \Simplify\Panel\Jobs\UpdatePlugin::dispatch($plugin->id, (int) user()->id);
        Notification::make()->title('Update started')->body($plugin->name.' is updated in the background. You get a notification when it is done; if anything fails, the current version is restored.')->info()->send();
    }

    public function setEnabled(string $id, bool $enabled): void
    {
        $plugin = Plugin::query()->findOrFail($id);
        abort_unless($this->canChange($plugin) && in_array($plugin->status->value, ['enabled', 'disabled'], true), 403);
        $service = app(\App\Services\Helpers\PluginService::class);
        $enabled ? $service->enablePlugin($plugin) : $service->disablePlugin($plugin);
        Notification::make()->title($plugin->name.($enabled ? ' enabled' : ' disabled'))->body('Reload the panel to apply the change.')->success()->send();
    }

    public function legacyUrl(): string
    {
        return \App\Filament\Admin\Resources\Plugins\PluginResource::getUrl('index', panel: 'admin');
    }
}
