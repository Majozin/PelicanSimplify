<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Server;
use Filament\Facades\Filament;
use Simplify\Panel\Filament\App\Pages\Dashboard;
final class LegacyNavigation
{
    public static function url(): ?string
    {
        $request = request();
        if (!user() || !FeatureSettings::enabled('dashboard') || $request->boolean('simplify_embed')
            || !$request->routeIs('filament.*') || $request->routeIs('*.auth.*', 'filament.app.pages.simplify', 'filament.app.pages.simple-server-creator')) return null;
        $server = Filament::getCurrentPanel()?->getId() === 'server' ? Filament::getTenant() : null;
        $parameters = $server instanceof Server && Access::linked($server) ? ['server' => $server->uuid] : [];
        return Dashboard::getUrl($parameters, panel: 'app');
    }
    public static function topbarVisible(): bool
    {
        // The native server overview has its own page-header button on the right.
        return !request()->routeIs('filament.app.resources.servers.index') && self::url() !== null;
    }
}
