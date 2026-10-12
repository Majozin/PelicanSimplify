<?php
namespace Simplify\Panel\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
/** Pelican's plugin, user and role lists (and links to them) open Simplify's pages when those add-ons are on. */
final class PluginsHome
{
    public function handle(Request $r,Closure $next): mixed
    {
        if (!$r->isMethod('GET') || $r->boolean('simplify_legacy')) return $next($r);
        $embed = array_filter(['simplify_embed'=>$r->query('simplify_embed')]);
        $route = $r->route()?->getName();
        if ($route==='filament.admin.resources.plugins.index' && \Simplify\Panel\Services\FeatureSettings::enabled('plugin_manager'))
            return redirect(\Simplify\Panel\Filament\Admin\Pages\ManagePlugins::getUrl($embed,panel:'admin'));
        // People & roles: Pelican's lists open Simplify's page for root admins; record pages stay reachable.
        if (in_array($route,['filament.admin.resources.users.index','filament.admin.resources.roles.index'],true) && \Simplify\Panel\Filament\Admin\Pages\ManagePeople::canAccess())
            return redirect(\Simplify\Panel\Filament\Admin\Pages\ManagePeople::getUrl([...$embed,'tab'=>str_contains($route,'roles')?'roles':'people'],panel:'admin'));
        return $next($r);
    }
}
