<?php

declare(strict_types=1);

namespace Simplify\Panel\Http\Middleware;

use Closure;
use Simplify\Panel\Filament\Admin\Pages\CreateMinecraftServer;

final class RedirectServerCreate
{
    public function handle($request, Closure $next)
    {
        if (\Simplify\Panel\Services\FeatureSettings::enabled('easy_creator') && $request->isMethod('GET') && $request->routeIs('filament.admin.resources.servers.create')) {
            $parameters=[];
            if (in_array($request->query('game'),['minecraft','other'],true)) $parameters['game']=$request->query('game');
            if ($request->query('simplify_embed')==='1') $parameters['simplify_embed']=1;
            return redirect(CreateMinecraftServer::getUrl($parameters,panel: 'admin'));
        }
        return $next($request);
    }
}
