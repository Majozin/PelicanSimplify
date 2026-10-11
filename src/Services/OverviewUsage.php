<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Server;
final class OverviewUsage
{
    public static function get(Server $server, string $status): array
    {
        $usage=['cpu_percent'=>null,'memory_bytes'=>null,'network_rx_bytes'=>null,'network_tx_bytes'=>null,'network_sampled_at'=>microtime(true),'disk_bytes'=>null,'disk_limit_bytes'=>(int)($server->disk??0)*1048576,'cpu_limit'=>(float)$server->cpu,'memory_limit_bytes'=>(int)$server->memory*1048576];
        if($status==='offline') return array_replace($usage,['cpu_percent'=>0.0,'memory_bytes'=>0.0,'network_rx_bytes'=>0.0,'network_tx_bytes'=>0.0]);
        try {
            $resources=$server->retrieveResources();
            foreach(['cpu_absolute'=>'cpu_percent','memory_bytes'=>'memory_bytes','disk_bytes'=>'disk_bytes'] as $source=>$target){
                $value=$resources[$source]??null;
                if(is_numeric($value)&&is_finite((float)$value)&&(float)$value>=0)$usage[$target]=(float)$value;
            }
            foreach(['rx_bytes'=>'network_rx_bytes','tx_bytes'=>'network_tx_bytes'] as $source=>$target){$value=$resources['network'][$source]??null;if(is_numeric($value)&&is_finite((float)$value)&&(float)$value>=0)$usage[$target]=(float)$value;}
        } catch(\Throwable) { /* Missing telemetry stays unavailable instead of displaying invented values. */ }
        return $usage;
    }
}
