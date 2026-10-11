<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Enums\SubuserPermission;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Illuminate\Support\Facades\Cache;
final class MinecraftMotd
{
    public static function forServer(Server $server, ?string $reported): string
    {
        if(!ManagedSettings::minecraft($server))return '';
        if($reported!==null)return $reported;
        if(user()->can(SubuserPermission::FileReadContent,$server)) {
            $value=Cache::remember('simplify.motd-properties.'.$server->uuid,30,static function()use($server):?string {
                try{return PropertiesReader::parse(app(DaemonFileRepository::class)->setServer($server)->getContent('/server.properties',1048576))['motd']??null;}catch(\Throwable){return null;}
            });
            if($value!==null)return $value;
        }
        return (string)Cache::get('simplify.public-motd.'.$server->uuid,'');
    }
    /** Convert the public Minecraft status component into safe legacy text, never HTML. */
    public static function legacy(mixed $component, array $inherited=[], int $depth=0): string
    {
        if($depth>24)return '';
        if(is_string($component))return $component;
        if(!is_array($component))return '';
        if(array_is_list($component))return implode('',array_map(fn($part)=>self::legacy($part,$inherited,$depth+1),array_slice($component,0,200)));
        $style=array_replace($inherited,array_intersect_key($component,array_flip(['color','bold','italic','underlined','strikethrough','obfuscated'])));
        $colors=['black'=>'0','dark_blue'=>'1','dark_green'=>'2','dark_aqua'=>'3','dark_red'=>'4','dark_purple'=>'5','gold'=>'6','gray'=>'7','dark_gray'=>'8','blue'=>'9','green'=>'a','aqua'=>'b','red'=>'c','light_purple'=>'d','yellow'=>'e','white'=>'f'];
        $prefix='§r';$color=$style['color']??null;
        if(isset($colors[$color??'']))$prefix.='§'.$colors[$color];
        elseif(is_string($color)&&preg_match('/^#[0-9a-f]{6}$/i',$color))$prefix.='§x§'.implode('§',str_split(substr($color,1)));
        foreach(['bold'=>'l','italic'=>'o','underlined'=>'n','strikethrough'=>'m','obfuscated'=>'k'] as $field=>$code)if(($style[$field]??false)===true)$prefix.='§'.$code;
        $text=is_string($component['text']??null)?$component['text']:'';
        $result=$text!==''?$prefix.$text:'';
        foreach(array_slice(is_array($component['extra']??null)?$component['extra']:[],0,200) as $extra)$result.=self::legacy($extra,$style,$depth+1);
        return $result;
    }
}
