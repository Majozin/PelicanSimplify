<?php
require __DIR__.'/../src/Services/MinecraftMotd.php';
use Simplify\Panel\Services\MinecraftMotd as M;
$check=function($ok){if(!$ok)throw new RuntimeException('Minecraft MOTD decoding regression');};
$check(M::legacy('§aHello')==='§aHello');
$r=M::legacy(['text'=>'Hello ','color'=>'green','bold'=>true,'extra'=>[['text'=>'world','bold'=>false,'color'=>'red']]]);$check($r==='§r§a§lHello §r§cworld');
$check(M::legacy(['text'=>'RGB','color'=>'#12ab34'])==='§r§x§1§2§a§b§3§4RGB');
$check(M::legacy(['text'=>'<script>text</script>'])==='§r<script>text</script>');$check(M::legacy(123)==='');
echo "PASS public Minecraft MOTD text, inherited formatting, child resets, RGB colors and safe literal content\n";
