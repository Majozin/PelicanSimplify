<?php
declare(strict_types=1);
$panel=$argv[1];$file=$argv[2];
$data=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
require $panel.'/vendor/autoload.php';$app=require $panel.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\Illuminate\Support\Facades\DB::transaction(function()use($data):void {
    foreach($data['schedules'] as $row) \App\Models\Schedule::whereKey((int)$row['id'])->update(['is_active'=>(bool)$row['is_active']]);
    foreach($data['limits'] as $row) \App\Models\Server::whereKey((int)$row['id'])->update(['backup_limit'=>(int)$row['backup_limit']]);
});
echo "Previous backup limits and SSC schedule state restored.\n";
