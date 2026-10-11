<?php
declare(strict_types=1);
namespace Simplify\Panel\Filament\App\Pages;
use App\Enums\SubuserPermission as P;
use App\Models\Server;
use App\Repositories\Daemon\DaemonServerRepository;
use App\Repositories\Daemon\DaemonFileRepository;
use Simplify\Panel\Services\Access;
use Simplify\Panel\Services\Extensions;
use Simplify\Panel\Services\ManagedSettings;
use Simplify\Panel\Services\ManagedResources;
use Simplify\Panel\Services\PendingChanges;
use Simplify\Panel\Services\PropertiesReader;
use Simplify\Panel\Services\MinecraftStatus;
use Simplify\Panel\Services\AutomaticBackups;
use Simplify\Panel\Services\Packages;
use Simplify\Panel\Services\ServerArtwork;
use Simplify\Panel\Services\OverviewUsage;
use Simplify\Panel\Services\HostHealth;
use Simplify\Panel\Services\ServerAppearance;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\WithFileUploads;
final class Dashboard extends Page
{
    use WithFileUploads;
    protected static ?string $slug='simplify';
    protected static ?string $title='Pelican - Enhanced by Simplify';
    protected static bool $shouldRegisterNavigation=false;
    protected string $view='pelican-simplify::dashboard';
    #[Locked] public ?string $serverUuid=null;
    public $iconUpload=null;
    public function boot(): void { app()->setLocale('en'); }
    public function mount(): void
    {
        $uuid=request()->query('server');
        if(is_string($uuid) && $uuid!==''){Access::server($uuid);$this->serverUuid=$uuid;}
    }
    private function server(): Server { abort_unless($this->serverUuid,404);return Access::server($this->serverUuid); }
    public function initial(): array
    {
        $admin=user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'));
        $base=['admin_url'=>$admin?\Filament\Facades\Filament::getPanel('admin')->getUrl():(\Simplify\Panel\Services\FeatureSettings::enabled('user_legacy')?route('filament.app.resources.servers.index',['simplify_legacy'=>1]):null),
            'create_url'=>Extensions::createUrl(),'can_create'=>Extensions::canCreate(),'create_games'=>Extensions::createGames(),'web'=>Extensions::web(),
            'native_list_url'=>\Filament\Facades\Filament::getPanel('app')->getUrl(),
            'profile_url'=>\App\Filament\Pages\Auth\EditProfile::getUrl(panel:'app'),'appearance'=>\Simplify\Panel\Services\AppearanceSettings::all(),'panel_name'=>(string)config('app.name','Pelican'),'features'=>array_replace(\Simplify\Panel\Services\FeatureSettings::all(),array_combine(['settings','files','players','backups','packages','software','resources','advanced','activity','schedules'],array_map(fn($key)=>Access::feature($key),['settings','files','players','backups','packages','software','resources','advanced','activity','schedules']))),'settings_url'=>user()?->isRootAdmin()?\Simplify\Panel\Filament\Admin\Pages\SimplifySettings::getUrl(panel:'app'):null,'website_url'=>Extensions::webAdapter()?->url(),'list_url'=>self::getUrl(panel:'app'),'server'=>null,'servers'=>[],'user'=>user()->username,'avatar'=>user()->getFilamentAvatarUrl(),'favicon_url'=>route('simplify.favicon').'?v=0.2.0-alpha.5'];
        if(!$this->serverUuid){$base['servers']=$this->listing();$base['host_health']=$this->hostHealth();return$base;}
        $s=$this->server();$base['available_colors']=ServerAppearance::choices($s->uuid);$permissions=[];foreach(P::cases() as $p)$permissions[$p->value]=user()->can($p,$s);
        $meta=ManagedSettings::content($s);$mc=MinecraftStatus::get($s);
        $properties=[];
        if(user()->can(P::FileReadContent,$s) && ManagedSettings::minecraft($s)) {
            try{$properties=PropertiesReader::parse(app(DaemonFileRepository::class)->setServer($s)->getContent('/server.properties',1048576));}catch(\Throwable $e){report($e);}
        }
        $pending=PendingChanges::read($s);
        if ($pending && !isset($pending['restart_at']) && empty($pending['error'])) {
            try { PendingChanges::progress($s,true); } catch (\Throwable $e) { /* Keep apply errors in the pending-change banner. */ }
            $pending=PendingChanges::read($s);
        }
        $artworkMeta=Extensions::web()?Extensions::webAdapter()->metadata($s):$meta;
        $base['background_choices']=array_map(fn($id)=>['id'=>$id,'name'=>ucfirst($id),'url'=>route('simplify.background',['id'=>$id])],array_keys(ServerArtwork::BACKGROUNDS));
        $base['server']=['background_id'=>ServerArtwork::selection($s) ?? ($artworkMeta['background_id'] ?? 'auto'),'color'=>ServerAppearance::color($s),'uuid'=>$s->uuid,'short'=>$s->uuid_short,'name'=>$s->name,'address'=>$s->allocation?->address,
            'memory'=>$s->memory,'disk'=>$s->disk,'cpu'=>$s->cpu,'software'=>$s->egg?->name,'image'=>user()->can(P::StartupRead,$s)?$s->image:'Restricted','startup'=>user()->can(P::StartupRead,$s)?$s->startup:'Restricted',
            'package_kind'=>Packages::capability($s),'native_sections'=>$this->nativeSections($s),
            'owner'=>user()->isRootAdmin() || (int)$s->owner_id===(int)user()->id,'minecraft'=>ManagedSettings::minecraft($s),'can_resources'=>ManagedResources::canEdit($s),'permissions'=>$permissions,
            'properties'=>array_intersect_key($properties,array_flip(['motd','max-players','difficulty','gamemode','pvp','white-list','allow-flight','view-distance','spawn-protection'])),
            'properties_available'=>!ManagedSettings::minecraft($s) || !empty($properties),'pending'=>$pending?['pending'=>true,'restart_at'=>$pending['restart_at'],'restart_requested'=>$pending['restart_requested'],'error'=>$pending['error']??null]:null,
            'pack'=>array_intersect_key($meta,array_flip(['modpack_name','modpack_version','modpack_description','modpack_icon_url','modpack_url','plugins','version','motd','icon_url'])),
            'reported_version'=>$mc['version']??'','status'=>$s->retrieveStatus()->value,'players'=>$mc['players']??null,'max_players'=>$mc['max_players']??null,'player_names'=>$mc['names']??[],
            'software_icon'=>ServerArtwork::softwareIcon($s,$meta),'server_icon'=>ServerArtwork::serverIcon($s)??($mc['icon']??null),
            'icon'=>ServerArtwork::icon($s,$meta)??($mc['icon']??null),'background'=>ServerArtwork::background($s,$artworkMeta),'backup_options'=>AutomaticBackups::settings($s),'root_admin'=>user()->isRootAdmin(),
            'files_url'=>\App\Filament\Server\Resources\Files\FileResource::getUrl('index',panel:'server',tenant:$s),
            'backups_url'=>\App\Filament\Server\Resources\Backups\BackupResource::getUrl('index',panel:'server',tenant:$s),
            'admin_server_url'=>$admin?\App\Filament\Admin\Resources\Servers\ServerResource::getUrl('edit',['record'=>$s],panel:'admin'):null];
        return$base;
    }
    /** Resolve registered pages instead of depending on version-specific URL paths. */
    private function nativeSections(Server $s): array
    {
        $panel=\Filament\Facades\Filament::getPanel('server');
        $sections=[];
        $targets=[
            'schedules'=>['ScheduleResource','SchedulesResource'],
            'activity'=>['Activity','ActivityLog','ActivityLogs','ActivityResource','ActivitiesResource','ActivityLogResource','ActivityLogsResource'],
            'sftp'=>['Settings'],
            'databases'=>['DatabaseResource','DatabasesResource'],
            'access'=>['UserResource','UsersResource','SubuserResource','SubusersResource'],
        ];
        foreach ([true=>$panel->getResources(),false=>$panel->getPages()] as $resource=>$classes) {
            foreach ($classes as $class) foreach ($targets as $key=>$names) {
                if (!in_array(class_basename($class),$names,true)) continue;
                try { $sections[$key]=$resource?$class::getUrl('index',panel:'server',tenant:$s):$class::getUrl(panel:'server',tenant:$s); }
                catch (\Throwable $e) { report($e); }
            }
        }
        return $sections;
    }
    #[Renderless]
    public function hostHealth(): array { return \Simplify\Panel\Services\FeatureSettings::enabled('host_health')?HostHealth::snapshot():[]; }
    #[Renderless]
    public function listing(): array
    {
        $list=[];
        $query=Access::overviewQuery();
        foreach($query->get() as $s){
            $linked=user()->isAdmin()||(int)$s->owner_id===(int)user()->id||(bool)$s->simplify_assigned;$m=Extensions::web()?Extensions::webAdapter()->metadata($s):[];
            $status=$s->retrieveStatus()->value;$mc=$status==='offline'?['players'=>0]:MinecraftStatus::get($s);
            $list[]=['color'=>ServerAppearance::color($s),'uuid'=>$s->uuid,'name'=>$s->name,'software'=>$s->egg?->name,'game'=>ManagedSettings::minecraft($s)?'minecraft':'other','game_label'=>ManagedSettings::minecraft($s)?'Minecraft':($s->egg?->name ?: 'Other game'),'status'=>$status,'linked'=>$linked,
                'url'=>$linked?self::getUrl(['server'=>$s->uuid],panel:'app'):null,
                'server_icon'=>ServerArtwork::serverIcon($s)??($mc['icon']??null),'software_icon'=>ServerArtwork::softwareIcon($s,$m),'egg_icon'=>$s->egg?->icon,
                'icon'=>ServerArtwork::icon($s,$m)??($mc['icon']??null),'background'=>ServerArtwork::background($s,$m),'players'=>$mc['players']??null,'max_players'=>$mc['max_players']??null,
                'pack'=>$m['modpack_name']??'','motd'=>$m['motd']??'','visible'=>$m['visible']??false,'usage'=>OverviewUsage::get($s,$status),
                'can_web'=>Extensions::web() && $linked && ((int)$s->owner_id===(int)user()->id || user()->isRootAdmin())];
        }
        return$list;
    }
    #[Renderless]
    public function socket(): array
    {
        $s=$this->server();Access::permission($s,P::WebsocketConnect);
        $token=app(\App\Services\Nodes\NodeJWTService::class)->setExpiresAt(now()->addMinutes(10)->toImmutable())
            ->setScopes(\App\Enums\NodeJwtScope::Websocket)->setUser(user())
            ->setClaims(['server_uuid'=>$s->uuid,'permissions'=>app(\App\Services\Servers\GetUserPermissionsService::class)->handle($s,user())])
            ->handle($s->node,user()->id.$s->uuid)->toString();
        return['token'=>$token,'url'=>str_replace(['https://','http://'],['wss://','ws://'],$s->node->getConnectionAddress()).'/api/servers/'.$s->uuid.'/ws'];
    }
    public function power(string $action): void
    {
        $s=$this->server();$p=match($action){'start'=>P::ControlStart,'stop','kill'=>P::ControlStop,'restart'=>P::ControlRestart,default=>null};abort_unless($p,422);Access::permission($s,$p);
        if($action==='stop' && PendingChanges::read($s)){PendingChanges::requestStop($s);return;}
        if($action==='restart' && PendingChanges::read($s)){PendingChanges::requestRestart($s,0);return;}
        abort_if($s->isInConflictState(),409,'Wait until the current installation or recovery is complete.');
        app(DaemonServerRepository::class)->setServer($s)->power($action)->throw();
    }
    public function saveSettings(array $state): array { abort_unless(Access::feature('settings'),404);return PendingChanges::stage($this->server(),$state,$this->iconUpload); }
    public function saveResourceLimits(array $state): array
    {abort_unless(Access::feature('resources'),404);
        $s=$this->server();Access::owner($s);abort_unless(ManagedResources::canEdit($s),403);
        $state=array_intersect_key($state,array_flip(['memory_gb','cpu','disk_mb']));
        abort_unless(isset($state['memory_gb'],$state['cpu'],$state['disk_mb']),422,'Provide all three resource limits.');
        return PendingChanges::stage($s,$state,null,true);
    }
    public function discard(): void { PendingChanges::discard($this->server()); }
    public function restartChanges(int $minutes=0): void { PendingChanges::requestRestart($this->server(),$minutes); }
    public function rename(string $name): void
    {
        $s=$this->server();Access::permission($s,P::SettingsRename);
        $name=trim($name);validator(['name'=>$name],['name'=>'required|string|max:255'])->validate();$s->update(['name'=>$name]);
    }
    public function saveServerBackground(string $id): array
    {
        return ServerArtwork::save($this->server(), $id);
    }
    #[Renderless]
    public function refreshInfo(): array
    {
        $s=$this->server();$p=PendingChanges::read($s);
        if ($p && (!isset($p['restart_at']) || $p['restart_at']<=time())) {
            try { PendingChanges::progress($s,!isset($p['restart_at'])); } catch (\Throwable $e) { /* Error remains visible in the pending-change banner. */ }
            $p=PendingChanges::read($s);
        }
        $data=MinecraftStatus::get($s);
        return['color'=>ServerAppearance::color($s),'name'=>$s->name,'server_icon'=>ServerArtwork::serverIcon($s)??($data['icon']??null),'reported_version'=>$data['version']??'','players'=>$data['players']??null,'max_players'=>$data['max_players']??null,'player_names'=>$data['names']??[],
            'pending'=>$p?['pending'=>true,'restart_at'=>$p['restart_at'],'restart_requested'=>$p['restart_requested'],'error'=>$p['error']??null]:null];
    }
    #[Renderless]
    public function availableServerColors(): array { return ServerAppearance::choices($this->server()->uuid); }
    #[Renderless]
    public function saveServerColor(string $color): string { return ServerAppearance::save($this->server(),$color); }
    public function website(?string $uuid=null): array
    {
        abort_unless(Extensions::web(),404);$s=$uuid?Access::server($uuid):$this->server();Access::owner($s);
        return Extensions::webAdapter()->metadata($s);
    }
    public function saveWebsite(array $input,?string $uuid=null): void
    {
        abort_unless(Extensions::web(),404);$s=$uuid?Access::server($uuid):$this->server();Access::owner($s);
        $fields=['visible','description','background_id','motd','icon_url'];
        $old=Extensions::webAdapter()->metadata($s);
        Extensions::webAdapter()->save($s,array_replace($old,array_intersect_key($input,array_flip($fields))));
    }
    public function packageSearch(string $query,string $version): array {abort_unless(Access::feature('packages'),404); return Packages::search($this->server(),$query,$version); }
    public function installedPackages(): array {abort_unless(Access::feature('packages'),404); return Packages::installed($this->server()); }
    public function installPackage(string $id,string $version): void {abort_unless(Access::feature('packages'),404); Packages::install($this->server(),$id,$version); }
    public function removePackage(string $filename): void {abort_unless(Access::feature('packages'),404); Packages::remove($this->server(),$filename); }
    public function players(): array
    {abort_unless(Access::feature('players'),404);
        $s=$this->server();Access::permission($s,P::FileReadContent);$files=app(DaemonFileRepository::class)->setServer($s);$lists=[];
        foreach(['whitelist','ops','banned-players'] as $key){try{$list=json_decode($files->getContent('/'.$key.'.json',1048576),true);$lists[$key]=is_array($list)?$list:[];}catch(\Throwable){$lists[$key]=[];}}
        return$lists;
    }
    public function subdomain(string $name): void
    {
        $s=$this->server();Access::owner($s);
        validator(['name'=>$name],['name'=>'nullable|string|max:63|regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/'])->validate();
        abort_unless(Extensions::web(),404);Extensions::webAdapter()->subdomain($s,$name);
    }
    public function deleteServer(string $confirmation): void
    {
        $s=$this->server();abort_unless(Access::feature('advanced'),404);Access::owner($s);Access::offline($s);abort_unless(hash_equals($s->name,$confirmation),422,'Enter the exact server name.');
        app(\App\Services\Servers\ServerDeletionService::class)->handle($s);
    }
    public function backupOptions(array $state): void {abort_unless(Access::feature('backups'),404); AutomaticBackups::save($this->server(),$state); }
    public function backupList(): array
    {abort_unless(Access::feature('backups'),404);
        $s=$this->server();Access::permission($s,P::BackupRead);
        return ['configured'=>$s->node->backupHosts()->exists() || \App\Models\BackupHost::doesntHave('nodes')->exists(),
            'items'=>$s->backups()->orderByDesc('created_at')->get()->map(fn($b)=>['uuid'=>$b->uuid,'name'=>$b->name,'bytes'=>$b->bytes,
                'created_at'=>$b->created_at?->toIso8601String(),'successful'=>$b->is_successful,'completed'=>(bool)$b->completed_at])->all()];
    }
    public function downloadBackup(string $uuid): string
    {abort_unless(Access::feature('backups'),404);
        $s=$this->server();Access::permission($s,P::BackupDownload);$b=$s->backups()->where('uuid',$uuid)->firstOrFail();
        abort_unless($b->is_successful && $b->completed_at,409);
        return app(\App\Services\Backups\DownloadLinkService::class)->handle($b,user());
    }
    public function restoreBackup(string $uuid,string $confirm): void
    {abort_unless(Access::feature('backups'),404);
        $s=$this->server();Access::permission($s,P::BackupRestore);Access::offline($s);abort_unless(hash_equals($s->name,$confirm),422,'Enter the server name.');
        $b=$s->backups()->where('uuid',$uuid)->firstOrFail();abort_unless($b->is_successful && $b->completed_at,409);
        $url=app(\App\Services\Backups\DownloadLinkService::class)->handle($b,user());
        $s->update(['status'=>\App\Enums\ServerState::RestoringBackup]);
        try{app(\App\Repositories\Daemon\DaemonBackupRepository::class)->setServer($s)->restore($b,$url,false)->throw();}
        catch(\Throwable $e){$s->update(['status'=>null]);throw$e;}
    }
    public function connectionData(): array { $s=$this->server();Access::permission($s,P::StartupRead);return ['requested_subdomain'=>Extensions::web()?Extensions::webAdapter()->metadata($s)['requested_subdomain']??'':'']; }
    public function advancedData(): array { abort_unless(Access::feature('advanced')||Access::feature('software'),404);return \Simplify\Panel\Services\Software::data($this->server()); }
    public function packSearch(string $query): array {abort_unless(Access::feature('software'),404); $this->server();return \Simplify\Panel\Services\ModpackCatalog::results($query); }
    public function packVersions(string $key): array {abort_unless(Access::feature('software'),404); $this->server();return \Simplify\Panel\Services\ModpackCatalog::versions($key); }
    public function changeSoftware(array $state,string $confirmation): void {abort_unless(Access::feature('software'),404); \Simplify\Panel\Services\Software::change($this->server(),$state,$confirmation); }
}
