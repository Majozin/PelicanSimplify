<?php
declare(strict_types=1);
$panel=$argv[1];
require $panel.'/vendor/autoload.php';$app=require $panel.'/bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$disk=\Illuminate\Support\Facades\Storage::disk('local');
foreach(\App\Models\Server::select(['id','uuid'])->lazyById(100) as $server){
    $path='simplify/backups/'.$server->uuid.'.json';
    if(!$disk->exists($path)&&!$disk->put($path,json_encode(['enabled'=>true,'interval_minutes'=>30,'retention'=>2,'last_attempt'=>0],JSON_THROW_ON_ERROR)))throw new \RuntimeException('Could not initialize backup policy.');
}
echo "Backup policy index prepared; existing policies preserved.\n";
