<?php
require __DIR__.'/../src/Services/NetworkRates.php';
use Simplify\Panel\Services\NetworkRates as N;
$check=function($ok){if(!$ok)throw new RuntimeException('Network rate regression');};
$old=['at'=>10.0,'counters'=>['a'=>['rx_bytes'=>100,'tx_bytes'=>200],'b'=>['rx_bytes'=>500,'tx_bytes'=>800]]];
$check(N::calculate(['a'=>['rx_bytes'=>2148,'tx_bytes'=>1224]],12.0,$old)===['rx_per_second'=>1024.0,'tx_per_second'=>512.0]);
$check(N::calculate(['a'=>['rx_bytes'=>0,'tx_bytes'=>0],'b'=>['rx_bytes'=>2548,'tx_bytes'=>1824]],12.0,$old)===['rx_per_second'=>1024.0,'tx_per_second'=>512.0]);
foreach([null,['at'=>12.0,'counters'=>[]],['at'=>-200.0,'counters'=>[]]] as $previous)$check(N::calculate([],12.0,$previous)['rx_per_second']===null);
$check(N::calculate(['new'=>['rx_bytes'=>100000,'tx_bytes'=>100000]],12.0,$old)['rx_per_second']===null);
$check(N::calculate(['a'=>['rx_bytes'=>-1,'tx_bytes'=>300]],12.0,$old)['rx_per_second']===null);
echo "PASS elapsed-time rates, container reset isolation, removed/new servers and missing/stale samples\n";
