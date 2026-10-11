<?php
namespace Illuminate\Support\Facades {class Storage {public static array $records=[];public static function disk($name){return new self;}public function exists($path){return isset(self::$records[$path]);}public function get($path){return self::$records[$path];}}}
namespace App\Models {class Server {public static array $servers=[];public function __construct(public string $uuid){}public static function query(){return new self('query');}public function get($columns){return self::$servers;}}}
namespace {
require __DIR__.'/../pelican-simplify/src/Services/ServerAppearance.php';
use Simplify\Panel\Services\ServerAppearance as Colors;
use App\Models\Server;
use Illuminate\Support\Facades\Storage;
function check($condition,$message){if(!$condition)throw new \Exception($message);}
$base=Colors::choices();check(count($base)===6,'empty panel should suggest six distinct colors');
$first=array_key_first($base);check(Colors::defaultColor()===$first,'default must select first suggestion');
Server::$servers=[new Server('a')];Storage::$records['simplify/appearance/a.json']=json_encode(['color'=>strtoupper($first)]);
$ranked=Colors::choices();check(!isset($ranked[$first]),'used color was offered');
$hue=new \ReflectionMethod(Colors::class,'hue');$hue->setAccessible(true);
$firstHue=$hue->invoke(null,$first);$suggestedHue=$hue->invoke(null,array_key_first($ranked));$delta=abs($firstHue-$suggestedHue);
check(min($delta,360-$delta)>=170,'first suggestion should be opposite the existing hue');
check(array_key_first(Colors::choices('a'))===$first,'current server must be excluded from contrast ranking');
Storage::$records=[];check(count(Colors::choices())===6,'automatic colors should still get six ranked suggestions');
check((bool)preg_match('/^#[0-9a-f]{6}$/',Colors::defaultColor()),'default must be a concrete valid color');
echo "PASS six contrasting suggestions, used-color exclusion, opposite hue first, current-server exception and automatic default\n";
}
