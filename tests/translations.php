<?php
// Every translatable text has a pt_BR translation with the same :placeholders (and plural forms).
// Literal keys come from __()/trans_choice() in PHP and Blade and t() in the browser scripts; texts that
// reach __() through a variable are listed from their source arrays below.
$root=dirname(__DIR__);
require $root.'/src/Services/FeatureSettings.php';
require $root.'/src/Services/PerformanceSettings.php';
require $root.'/src/Services/RoleAccess.php';
use Simplify\Panel\Services\{FeatureSettings,PerformanceSettings,RoleAccess};
function fail($message){throw new RuntimeException($message);}
$unescape=fn(string $s,string $quote)=>str_replace(['\\\\','\\'.$quote],['\\',$quote],$s);
$keys=[];
$files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src'));
foreach([...$files,...new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views'))] as $file){
    $path=(string)$file;
    if(str_ends_with($path,'.php')){
        preg_match_all("/(?<![\w$])(?<!->)(?<!::)(?:__|trans_choice)\(\s*'((?:[^'\\\\]|\\\\.)*)'/",file_get_contents($path),$m);
        foreach($m[1] as $key)$keys[$unescape($key,"'")]=basename($path);
        if(str_ends_with($path,'.blade.php')){
            preg_match_all("/(?<![\w.$])t\(\s*'((?:[^'\\\\]|\\\\.)*)'/",file_get_contents($path),$m);
            foreach($m[1] as $key)$keys[$unescape($key,"'")]=basename($path);
        }
    }elseif(str_ends_with($path,'.js')){
        preg_match_all("/(?<![\w.$])t\(\s*'((?:[^'\\\\]|\\\\.)*)'/",file_get_contents($path),$m);
        foreach($m[1] as $key)$keys[$unescape($key,"'")]=basename($path);
    }
}
$dynamic=[];
foreach(FeatureSettings::definitions() as [$label,$help])array_push($dynamic,$label,$help);
foreach(PerformanceSettings::FIELDS as [$label,$help])array_push($dynamic,$label,$help);
foreach(RoleAccess::ABILITIES as [$label,$help])array_push($dynamic,$label,$help);
$settings=file_get_contents($root.'/src/Filament/Admin/Pages/SimplifySettings.php');
foreach(['CATEGORIES','ADDONS'] as $constant){
    preg_match('/const '.$constant.' = \[(.*?)\];/s',$settings,$block);
    preg_match_all("/'([^']+)' =>/",$block[1],$m);array_push($dynamic,...$m[1]);
}
// featureSection() titles and descriptions, requirementGroups() and the extra settings tabs.
preg_match_all("/self::featureSection\('([^']+)', '[^']+'(?:, '((?:[^'\\\\]|\\\\.)*)')?\)/",$settings,$m);
foreach([...$m[1],...$m[2]] as $text)if($text!=='')$dynamic[]=$unescape($text,"'");
preg_match("/match \(true\) \{(.*?)\};/s",$settings,$block);preg_match_all("/=> '([^']+)'/",$block[1],$m);array_push($dynamic,...$m[1]);
array_push($dynamic,'Website','Server banners','PelicanSimplify settings');
// HostHealth labels (cached for every viewer, translated in the dashboard).
preg_match_all("/'label'=>(?:\\\$warning\\?)?'([^']+)'(?::'([^']+)')?/",file_get_contents($root.'/src/Services/HostHealth.php'),$m);
foreach([...$m[1],...$m[2]] as $text)if($text!=='')$dynamic[]=$text;
// AdminPages group names (translated when the menu is built).
preg_match("/const GROUPS = \[([^\]]+)\]/",file_get_contents($root.'/src/Services/AdminPages.php'),$block);
preg_match_all("/'([^']+)'/",$block[1],$m);array_push($dynamic,...$m[1]);
// ServerAppearance::choices() colour names.
preg_match("/\\\$names=\[([^\]]+)\]/",file_get_contents($root.'/src/Services/ServerAppearance.php'),$block);
preg_match_all("/'([^']+)'/",$block[1],$m);array_push($dynamic,...$m[1]);
foreach($dynamic as $key)$keys[$key]??='dynamic';

$table=json_decode(file_get_contents($root.'/lang/pt_BR.json'),true,4,JSON_THROW_ON_ERROR);
$problems=[];
foreach($keys as $key=>$where){
    if(!isset($table[$key])||trim($table[$key])===''){$problems[]="missing ($where): $key";continue;}
    preg_match_all('/:[a-z_]+/',$key,$a);preg_match_all('/:[a-z_]+/',$table[$key],$b);
    $a=array_unique($a[0]);$b=array_unique($b[0]);sort($a);sort($b);
    if($a!==$b)$problems[]="placeholders differ: $key";
    if(substr_count($key,'|')!==substr_count($table[$key],'|'))$problems[]="plural forms differ: $key";
}
if($problems)fail(count($problems)." translation problem(s):\n".implode("\n",$problems));
echo 'PASS pt_BR translates all '.count($keys)." texts with matching placeholders and plural forms\n";
