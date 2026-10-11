<?php
namespace Illuminate\Support\Facades {
 class Cache {public static function remember($key,$seconds,$fn){return $fn();}}
 class Storage {public static array $files=[];public static function disk($d){return new self;}public function exists($p){return isset(self::$files[$p]);}public function get($p){return self::$files[$p]??null;}}
}
namespace App\Models {class Server {public $uuid='server-a';public $installed_at='today';public $egg;}}
namespace App\Repositories\Daemon {class DaemonFileRepository {public static $bytes='';public static $calls=[];public function setServer($s){return $this;}public function getContent($p,$n){self::$calls[]=[$p,$n];return self::$bytes;}}}
namespace Simplify\Panel\Services {class ManagedSettings {public static function minecraft($s){return true;}}class Extensions {public static bool $web=true;public static function web(){return self::$web;}}}
namespace {
 function app($c){return new $c;}function route($name,$args){return '/simplify/background/'.$args['id'];}
 function check($v,$message){if(!$v)throw new \RuntimeException($message);echo "PASS $message\n";}
 require __DIR__.'/../src/Services/ServerArtwork.php';
 use Simplify\Panel\Services\ServerArtwork as A;
 use App\Repositories\Daemon\DaemonFileRepository as F;
 $s=new App\Models\Server;$s->egg=(object)['icon'=>'https://panel.test/egg.png'];
 F::$bytes="\x89PNG\r\n\x1a\n".pack('N',13).'IHDR'.pack('NN',64,64).str_repeat("\0",10);
 check(str_starts_with(A::icon($s,['modpack_icon_url'=>'https://pack.test/icon.png']),'data:image/png;base64,'),'server-icon.png wins over pack and egg icons');
 check(F::$calls[0]===['/server-icon.png',102400],'reads only fixed bounded public icon');
 F::$bytes='<html>not an icon</html>';check(A::icon($s,['modpack_icon_url'=>'https://pack.test/icon.png'])==='https://pack.test/icon.png','non-PNG file falls back to pack');
 F::$bytes="\x89PNG\r\n\x1a\n".pack('N',13).'IHDR'.pack('NN',1024,1024).str_repeat("\0",10);check(A::icon($s)=== 'https://panel.test/egg.png','incorrect dimensions fall back to egg');
 F::$bytes=str_repeat('x',102401);check(A::icon($s)==='https://panel.test/egg.png','oversized image rejected');
 check(A::background($s,['background_id'=>'harbor'])==='/simplify/background/harbor','manual website background preserved');
 Illuminate\Support\Facades\Storage::$files['server-directory/backgrounds/server-a.json']='{"background_id":"valley"}';
 check(A::background($s,['background_id'=>'auto'])==='/simplify/background/valley','automatic website selection reused without changing it');
 check(A::background($s,['background_id'=>'../secret'])===null,'unknown or path-like image ID rejected');
 \Simplify\Panel\Services\Extensions::$web=false;check(A::background($s,['background_id'=>'harbor'])===null,'main remains independent of Web');
}
