<?php
require __DIR__.'/../src/Services/InterfaceScale.php';
require __DIR__.'/../src/Services/BuildInfo.php';
use Simplify\Panel\Services\InterfaceScale as S;
use Simplify\Panel\Services\BuildInfo;
function check($ok,$message='Interface scale regression'){if(!$ok)throw new RuntimeException($message);}
$wrap=fn(string $px)=>'calc('.$px.' * var(--ps-scale, 1))';
check(S::css('.a{font-size:12px}')==='.a{font-size:'.$wrap('12px').'}');
check(S::css('.a{font: 600 13px/18px var(--ps-font)}')==='.a{font: 600 '.$wrap('13px').'/'.$wrap('18px').' var(--ps-font)}');
check(S::css('h2{font-size: clamp(22px, 3vw, 30px)}')==='h2{font-size: clamp('.$wrap('22px').', 3vw, '.$wrap('30px').')}');
check(S::css(':root{--ps-text-sm: 12px; --ps-text: 14px}')===':root{--ps-text-sm: '.$wrap('12px').'; --ps-text: '.$wrap('14px').'}');
check(S::css('.a{line-height:18px;letter-spacing:1px}')==='.a{line-height:'.$wrap('18px').';letter-spacing:1px}');
// Already relative or token-based sizes are not scaled twice; layout, borders and media queries stay untouched.
foreach (['.a{font-size:var(--ps-text-sm)}','.a{font-size:1.2em}','.a{font-family:Inter;font-weight:600}','@media (max-width: 480px){.a{width:12px;border:1px solid}}','.a{transition:font-size .2s}','.a{--ps-radius-sm:7px}'] as $same) check(S::css($same)===$same,'unexpected change: '.$same);
// Every px text size in the real bundles is scaled.
$views=__DIR__.'/../resources/views/';
foreach (BuildInfo::BUNDLES as $bundle=>$files) {
    if (!str_ends_with($bundle,'.css')) continue;
    $css=S::css(implode("\n",array_map(fn($f)=>file_get_contents($views.$f),$files)));
    preg_match_all('/(?<![\w-])(?:font-size|font|line-height|--ps-text(?:-[a-z]+)?)\s*:\s*([^;{}]+)/',$css,$m);
    foreach ($m[1] as $value) check(!preg_match('/(?<![\w.(-])\d*\.?\d+px\b(?! \* var\(--ps-scale)/',$value),$bundle.': unscaled '.$value);
    check(substr_count($css,'{')===substr_count($css,'}'),$bundle.': unbalanced braces');
}
echo "PASS interface scale wraps px text sizes (font-size, font, line-height, tokens) and leaves layout, media queries and relative sizes alone\n";
