<?php
declare(strict_types=1);
namespace Illuminate\Validation {class ValidationException extends \RuntimeException {public static function withMessages($messages){return new self(json_encode($messages));}}}
namespace {
require __DIR__.'/../src/Services/CreatorAccess.php';
use Simplify\Panel\Services\CreatorAccess as A;
function check($ok,$message){if(!$ok)throw new \RuntimeException($message);echo "PASS $message\n";}
$config=A::defaults();$config['enabled']=true;$config['node_ids']=[1];$config['role_ids']=[2];
$user=(object)['id'=>10,'roles'=>[(object)['id'=>2],(object)['id'=>3]]];
check(A::allowed($user,$config),'role grant allows self-service without an explicit user grant');
check(!A::allowed((object)['id'=>11,'roles'=>[]],$config),'unrelated users cannot create or edit resources');
$first=['role_id'=>2,'max_servers'=>3,'max_memory_mb'=>4096,'max_disk_mb'=>20480,'max_cpu'=>100,'max_backups'=>1];
$second=['role_id'=>3,'max_servers'=>1,'max_memory_mb'=>16384,'max_disk_mb'=>10240,'max_cpu'=>300,'max_backups'=>4];
$config['role_limits']=[$first,$second];$limits=A::effective($user,$config);
check($limits['max_memory_mb']===16384&&$limits['max_disk_mb']===20480&&$limits['max_servers']===3&&$limits['max_cpu']===300,'multiple roles use highest per-resource allowance, even when below shared defaults');
$config['user_limits']=[['user_id'=>10,'max_servers'=>1,'max_memory_mb'=>2048,'max_disk_mb'=>5120,'max_cpu'=>50,'max_backups'=>0]];
$limits=A::effective($user,$config);check($limits['max_cpu']===50&&$limits['max_memory_mb']===2048&&$limits['max_servers']===1,'specific user limits take precedence over all roles');
$payload=['memory'=>2048,'disk'=>5120,'cpu'=>50,'backup_limit'=>0];$usage=['count'=>0,'memory'=>0,'disk'=>0];A::checkQuota($payload,$usage,$limits);
foreach ([['memory'=>3072],['disk'=>6144],['cpu'=>51],['backup_limit'=>1]] as $over){try{A::checkQuota(array_replace($payload,$over),$usage,$limits);throw new \LogicException('quota bypassed');}catch(\Illuminate\Validation\ValidationException){}}
try{A::checkQuota($payload,['count'=>1,'memory'=>0,'disk'=>0],$limits);throw new \LogicException('count bypassed');}catch(\Illuminate\Validation\ValidationException){}
check(true,'effective RAM, disk, CPU, backup and server limits enforced by creation quota');
$config['role_ids']=[];check(!A::allowed($user,$config),'a resource profile does not grant self-service permission');
$config['user_ids']=[10];check(A::allowed($user,$config),'existing explicit user grants remain compatible');
$config['enabled']=false;check(!A::allowed($user,$config),'disabled grants remain disabled');
}
