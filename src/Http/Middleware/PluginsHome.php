<?php
namespace Simplify\Panel\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
/** With the plugin manager add-on on, Pelican's plugin list (and links to it in notifications) opens Simplify's Plugins page. */
final class PluginsHome
{
    public function handle(Request $r,Closure $next): mixed
    {
        if ($r->isMethod('GET') && $r->route()?->getName()==='filament.admin.resources.plugins.index' && !$r->boolean('simplify_legacy') && \Simplify\Panel\Services\FeatureSettings::enabled('plugin_manager'))
            return redirect(\Simplify\Panel\Filament\Admin\Pages\ManagePlugins::getUrl(array_filter(['simplify_embed'=>$r->query('simplify_embed')]),panel:'admin'));
        return $next($r);
    }
}
