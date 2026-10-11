<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Server;
final class MinecraftStatus
{
    private static function vi(int $n): string { $b='';do{$c=$n&127;$n>>=7;$b.=chr($c|($n?128:0));}while($n);return $b; }
    private static function read($socket,int $n): string
    {
        $s='';while(strlen($s)<$n){$chunk=fread($socket,$n-strlen($s));if($chunk===false||$chunk==='')throw new \RuntimeException('Incomplete Minecraft status');$s.=$chunk;}return $s;
    }
    private static function num($socket): int { $v=0;for($i=0;$i<5;$i++){$b=ord(self::read($socket,1));$v|=($b&127)<<($i*7);if(!($b&128))return$v;}throw new \RuntimeException('Invalid status length'); }
    public static function get(Server $s): array
    {
        if(!ManagedSettings::minecraft($s))return[];
        return \Illuminate\Support\Facades\Cache::remember('simplify.mcstatus.'.$s->uuid,10,function()use($s):array {
            $host=$s->allocation?->ip;$port=(int)$s->allocation?->port;
            if(in_array($host,['','0.0.0.0','::',null],true))$host=parse_url($s->node->getConnectionAddress(),PHP_URL_HOST);
            if(!$host||!$port)return[];
            $socket=@stream_socket_client('tcp://'.(str_contains($host,':')?'['.$host.']':$host).':'.$port,$errno,$error,0.7);
            if(!$socket)return[];
            try {
                stream_set_timeout($socket,1);
                $handshake=self::vi(0).self::vi(47).self::vi(strlen($host)).$host.pack('n',$port).self::vi(1);
                fwrite($socket,self::vi(strlen($handshake)).$handshake."\x01\x00");
                $len=self::num($socket);if($len>2097152||$len<1)return[];
                if(self::num($socket)!==0)return[];
                $jsonLen=self::num($socket);if($jsonLen>2097152||$jsonLen<1)return[];
                $data=json_decode(self::read($socket,$jsonLen),true,512,JSON_THROW_ON_ERROR);
                return ['online'=>true,'players'=>$data['players']['online']??0,'max_players'=>$data['players']['max']??0,
                    'names'=>array_values(array_filter($data['players']['sample']??[],fn($p)=>preg_match('/^[A-Za-z0-9_]{1,16}$/',$p['name']??''))),
                    'version'=>$data['version']['name']??'','icon'=>str_starts_with($data['favicon']??'','data:image/png;base64,')?mb_substr($data['favicon'],0,150000):null];
            }catch(\Throwable){return[];}finally{fclose($socket);}
        });
    }
}
