<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
final class ServerArtwork
{
    public const BACKGROUNDS=['valley'=>'minecraft-valley.jpg','harbor'=>'minecraft-harbor.jpg','village'=>'minecraft-village.jpg','survival'=>'preview-world.jpg','town'=>'preview-modpack.jpg'];
    public static function serverIcon(Server $server): ?string
    {
        $icon=null;
        if(ManagedSettings::minecraft($server) && $server->installed_at){
            $icon=Cache::remember('simplify.server-icon.'.$server->uuid,30,function()use($server):?string {
                try {
                    // The overview exposes this one public image, never general file access.
                    $bytes=app(DaemonFileRepository::class)->setServer($server)->getContent('/server-icon.png',102400);
                    if(strlen($bytes)>102400 || strlen($bytes)<24 || substr($bytes,0,8)!=="\x89PNG\r\n\x1a\n" || substr($bytes,12,4)!=='IHDR')return null;
                    $size=unpack('Nwidth/Nheight',substr($bytes,16,8));
                    if($size['width']!==64 || $size['height']!==64)return null;
                    return 'data:image/png;base64,'.base64_encode($bytes);
                }catch(\Throwable){return null;}
            });
        }
        return $icon;
    }
    public static function softwareIcon(Server $server,array $meta=[]): ?string
    {
        return ($meta['modpack_icon_url']??null) ?: ($server->egg?->icon ?: null);
    }
    public static function icon(Server $server,array $meta=[]): ?string
    {
        return self::serverIcon($server) ?: ($meta['modpack_icon_url']??null) ?: ($meta['icon_url']??null) ?: ($server->egg?->icon ?: null);
    }
    public static function background(Server $server,array $meta): ?string
    {
        if(!Extensions::web())return null;
        if(!empty($meta['screenshot_url']))return $meta['screenshot_url'];
        $id=$meta['background_id']??'auto';
        if($id==='auto'){
            $disk=Storage::disk('local');$path='server-directory/backgrounds/'.$server->uuid.'.json';
            $raw=$disk->exists($path)?$disk->get($path):null;
            $id=$raw?(json_decode($raw,true)['background_id']??''):'';
            if(!isset(self::BACKGROUNDS[$id]))$id=Extensions::webAdapter()?->backgroundFor($server)??'';
        }
        return isset(self::BACKGROUNDS[$id])?route('simplify.background',['id'=>$id]):null;
    }
}
