@php
    $features=\Simplify\Panel\Services\FeatureSettings::all();
    $css=route('simplify.asset',['asset'=>'global.css']).'?v=0.2.0-alpha.4';
@endphp
<link id="simplify-global-css" rel="stylesheet" href="{{ $css }}">
<script>window.simplifyThemeConfig=@js([...$features,...\Simplify\Panel\Services\AppearanceSettings::all(),'css_url'=>$css]);</script>
<script src="{{ route('simplify.asset',['asset'=>'theme.js']) }}?v=0.2.0-alpha.4"></script>
