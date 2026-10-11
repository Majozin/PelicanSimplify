<?php
declare(strict_types=1);
namespace Illuminate\Database { class Seeder {} }
namespace App\Services\Helpers { class PluginService { public bool $strict=false; public function buildAssets($strict){$this->strict=$strict;throw new \RuntimeException('Build failed');} } }
namespace {
 require __DIR__.'/../pelican-simplify/src/Services/DependencyCheck.php';
 require __DIR__.'/../pelican-simplify/database/Seeders/PelicanSimplifySeeder.php';
 $service=new \App\Services\Helpers\PluginService;
 function app($class){global $service;return $service;}
 try{(new \Database\Seeders\PelicanSimplifySeeder)->run();throw new \RuntimeException('accepted broken build');}
 catch(\RuntimeException $e){if($e->getMessage()!=='Build failed'||!$service->strict)throw $e;}
 echo "PASS native install propagates asset build failures before plugin enablement\n";
}
