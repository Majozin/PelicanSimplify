<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Server;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
final class ServerMetadata
{
    public static function read(Server $server): array
    {
        $disk=Storage::disk('local');
        foreach(['simplify/server-metadata/','ssc/website-visibility/'] as $prefix){
            $path=$prefix.$server->uuid.'.json';
            if(!$disk->exists($path))continue;
            $data=json_decode($disk->get($path),true,512,JSON_THROW_ON_ERROR);
            return is_array($data)?$data:[];
        }
        return [];
    }
    public static function save(Server $server,array $data): void
    {
        Cache::lock('simplify.metadata.'.$server->uuid,10)->block(3,function()use($server,$data):void{
            $next=array_replace(self::read($server),array_intersect_key($data,array_flip(['server_alias','game','modpack','server_flavour'])));
            if(!Storage::disk('local')->put('simplify/server-metadata/'.$server->uuid.'.json',json_encode($next,JSON_THROW_ON_ERROR)))throw new \RuntimeException('Could not save server metadata.');
        });
    }
}
