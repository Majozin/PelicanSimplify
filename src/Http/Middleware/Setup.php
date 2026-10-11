<?php
declare(strict_types=1);
namespace Simplify\Panel\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Simplify\Panel\Services\FeatureSettings;
use Simplify\Panel\Filament\Admin\Pages\SimplifySettings;
final class Setup
{
    public function handle(Request $request,Closure $next): mixed
    {
        if($request->isMethod('GET')&&!$request->boolean('simplify_embed')&&user()?->isRootAdmin()
            &&!FeatureSettings::enabled('setup_completed')&&!$request->routeIs('*.pages.pelican-simplify-settings')
            &&$request->routeIs('filament.*')&&!$request->routeIs('*.auth.*')){
            return redirect(SimplifySettings::getUrl(panel:'app'));
        }
        return $next($request);
    }
}
