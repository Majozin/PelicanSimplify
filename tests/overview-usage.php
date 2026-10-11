<?php
namespace App\Models {
    class Server {
        public $cpu=200,$memory=4096,$resources=[],$calls=0,$fail=false;
        public function retrieveResources():array { $this->calls++;if($this->fail)throw new \RuntimeException('Node unavailable');return $this->resources; }
    }
}
namespace {
    require __DIR__.'/../src/Services/OverviewUsage.php';
    $s=new \App\Models\Server;
    $get=fn($status)=>\Simplify\Panel\Services\OverviewUsage::get($s,$status);
    $check=function($condition){if(!$condition)throw new \RuntimeException('Usage assertion failed');};
    $s->resources=['cpu_absolute'=>150.25,'memory_bytes'=>1073741824];$r=$get('running');
    $check($r['cpu_percent']===150.25);$check($r['memory_bytes']===1073741824.0);$check($r['memory_limit_bytes']===4294967296);$check($r['cpu_limit']===200.0);
    $s->resources=['cpu_absolute'=>'invalid','memory_bytes'=>-1];$r=$get('running');$check($r['cpu_percent']===null&&$r['memory_bytes']===null);
    $s->fail=true;$r=$get('running');$check($r['cpu_percent']===null&&$r['memory_bytes']===null);
    $calls=$s->calls;$r=$get('offline');$check($r['cpu_percent']===0.0&&$r['memory_bytes']===0.0);$check($s->calls===$calls);
    echo "PASS 8 overview usage checks: native resources, allocations, unavailable values and offline without daemon request\n";
}
