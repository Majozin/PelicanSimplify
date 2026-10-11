<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Node;
final class HostHealth
{
    public static function snapshot(): array
    {
        $samples=[];
        foreach(Node::query()->get() as $node){
            try{$samples[]=['maintenance'=>$node->isUnderMaintenance(),'stats'=>$node->statistics()];}
            catch(\Throwable){$samples[]=['maintenance'=>false,'stats'=>[]];}
        }
        return self::summarize($samples);
    }
    public static function summarize(array $samples): array
    {
        $result=['hosts'=>count($samples),'available'=>0,'cpu_percent'=>null,'memory_used'=>null,'memory_total'=>null,'disk_used'=>null,'disk_total'=>null,'state'=>'unavailable','label'=>'Unavailable','metric_states'=>['cpu'=>'unavailable','memory'=>'unavailable','disk'=>'unavailable']];
        $levels=['cpu'=>0,'memory'=>0,'disk'=>0];$cpu=0.0;$memoryUsed=0.0;$memoryTotal=0.0;$diskUsed=0.0;$diskTotal=0.0;$maintenance=false;$critical=false;$warning=false;
        $number=static fn($v):bool=>is_numeric($v)&&is_finite((float)$v)&&(float)$v>=0;
        foreach($samples as $sample){
            $s=$sample['stats']??[];$maintenance=$maintenance||($sample['maintenance']??false);
            if(!$number($s['memory_total']??null)||(float)$s['memory_total']<=0||!$number($s['memory_used']??null)||!$number($s['disk_total']??null)||(float)$s['disk_total']<=0||!$number($s['disk_used']??null)||!$number($s['cpu_percent']??null))continue;
            $result['available']++;$cpu+=(float)$s['cpu_percent'];$memoryUsed+=(float)$s['memory_used'];$memoryTotal+=(float)$s['memory_total'];$diskUsed+=(float)$s['disk_used'];$diskTotal+=(float)$s['disk_total'];
            $ram=(float)$s['memory_used']/(float)$s['memory_total']*100;$disk=(float)$s['disk_used']/(float)$s['disk_total']*100;
            $values=['cpu'=>(float)$s['cpu_percent'],'memory'=>$ram,'disk'=>$disk];foreach($values as $key=>$value)$levels[$key]=max($levels[$key],$value>=95?2:($value>=85?1:0));
            $critical=$critical||(float)$s['cpu_percent']>=95||$ram>=95||$disk>=95;
            $warning=$warning||(float)$s['cpu_percent']>=85||$ram>=85||$disk>=85;
        }
        if(!$result['available'])return $result;
        $result=array_replace($result,['cpu_percent'=>$cpu/$result['available'],'memory_used'=>$memoryUsed,'memory_total'=>$memoryTotal,'disk_used'=>$diskUsed,'disk_total'=>$diskTotal]);
        foreach($levels as $key=>$level)$result['metric_states'][$key]=['healthy','warning','critical'][$level];
        if($result['available']<$result['hosts']){foreach($result['metric_states'] as $key=>$state)if($state==='healthy')$result['metric_states'][$key]='unavailable';}
        if($result['available']<$result['hosts'])return array_replace($result,['state'=>'unavailable','label'=>'Partly unavailable']);
        if($critical)return array_replace($result,['state'=>'critical','label'=>'Critical load']);
        if($maintenance)return array_replace($result,['state'=>'warning','label'=>'Maintenance']);
        return array_replace($result,['state'=>$warning?'warning':'healthy','label'=>$warning?'High load':'Healthy']);
    }
}
