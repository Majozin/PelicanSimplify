<?php
declare(strict_types=1);
namespace Simplify\Panel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Actions\Action;
use Simplify\Panel\Filament\App\Pages\Dashboard;
use Simplify\Panel\Filament\Admin\Pages\SimplifySettings;
use Simplify\Panel\Filament\Admin\Pages\CreateMinecraftServer;
use Simplify\Panel\Filament\Admin\Pages\CreateServerLegacy;
use Simplify\Panel\Filament\App\Pages\CreateServer;
use Simplify\Panel\Services\FeatureSettings;
use App\Filament\Admin\Resources\Servers\ServerResource;
final class PelicanSimplifyPlugin implements Plugin
{
    public function getId(): string { return 'pelican-simplify'; }
    public function register(Panel $panel): void
    {
        if(!in_array($panel->getId(),['app','admin','server'],true))return;
        $panel->middleware([Http\Middleware\Setup::class,Http\Middleware\CreatorFramePolicy::class]);
        $panel->renderHook('panels::head.end',fn()=>view('pelican-simplify::shared-assets'));
        $panel->renderHook('panels::global-search.after',fn()=>view('pelican-simplify::legacy-return'));
        $panel->userMenuItems([
            Action::make('simplify_settings')->label('Simplify settings')->icon('tabler-settings')
                ->url(fn()=>SimplifySettings::getUrl(panel:$panel->getId()==='server'?'app':$panel->getId()))
                ->visible(fn()=>(bool)user()?->isRootAdmin()),
        ]);
        if($panel->getId()==='app'){
            $panel->pages([Dashboard::class,CreateServer::class,SimplifySettings::class]);
            $panel->middleware([Http\Middleware\Home::class]);
            $panel->homeUrl(fn()=>FeatureSettings::enabled('dashboard')?Dashboard::getUrl(panel:'app'):route('filament.app.resources.servers.index'));
        }
        if($panel->getId()==='admin'){
            $panel->topbar(true);
            $panel->pages([SimplifySettings::class,CreateMinecraftServer::class]);
            ServerResource::registerCustomPages(['legacy'=>CreateServerLegacy::route('/create-legacy')]);
        }
        if($panel->getId()==='server')$panel->middleware([Http\Middleware\ServerHome::class]);
    }
    public function boot(Panel $panel): void { app('view')->addNamespace($this->getId(),plugin_path($this->getId(),'resources/views')); }
}
