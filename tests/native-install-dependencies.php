<?php
declare(strict_types=1);
namespace Illuminate\Database { class Seeder {} }
namespace App\Services\Helpers { class PluginService { public bool $strict=false; public bool $fail=true; public function buildAssets($strict){$this->strict=$strict;if($this->fail)throw new \RuntimeException('Build failed');} } }
namespace Illuminate\Support\Facades { class Artisan {public static array $calls=[];public static int $result=0;public static function call($command){self::$calls[]=$command;return self::$result;}} }
namespace Simplify\Panel\Services { class DependencyCheck {public static bool $checked=false;public static function inspect(...$args){return [];}public static function assertReady($checks){self::$checked=true;}} }
namespace {
 require __DIR__.'/../database/Seeders/PelicanSimplifySeeder.php';
 $service=new \App\Services\Helpers\PluginService;
 function app($class){global $service;return $service;}
 function base_path(){return '/panel';}function plugin_path($id){return '/panel/plugins/'.$id;}
 (new \Database\Seeders\PelicanSimplifySeeder)->run();
 if($service->strict)throw new \RuntimeException('Simplify must not trigger a second panel asset build');
 if(\Illuminate\Support\Facades\Artisan::$calls!==['view:clear']||!\Simplify\Panel\Services\DependencyCheck::$checked)throw new \RuntimeException('update did not clear views and validate dependencies');
 \Illuminate\Support\Facades\Artisan::$result=1;
 \Simplify\Panel\Services\DependencyCheck::$checked=false;
 try{(new \Database\Seeders\PelicanSimplifySeeder)->run();throw new \RuntimeException('accepted failed view cache clear');}
 catch(\RuntimeException $e){if($e->getMessage()!=='Could not clear compiled views after updating Simplify.')throw $e;}
 if(\Simplify\Panel\Services\DependencyCheck::$checked)throw new \RuntimeException('continued after failed view cache clear');
 echo "PASS native install skips a second panel build, clears stale views and stops on cache-clear failure\n";
}
