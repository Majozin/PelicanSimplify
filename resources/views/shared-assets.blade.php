@php
    $features=\Simplify\Panel\Services\FeatureSettings::all();
    $css=route('simplify.asset',['asset'=>'global.css']).'?v=0.2.0-alpha.21';
@endphp
@if(request()->routeIs('filament.app.pages.simplify'))
<style>html.ps-page-loading .fi-layout{visibility:hidden!important}html.ps-page-loading body{background:#f4f4f5}html.dark.ps-page-loading body{background:#101012}[data-simplify-root][x-cloak]{display:none!important}#simplify-page-loading{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;gap:10px;padding:24px;color:#71717a;font:14px system-ui;z-index:9999}#simplify-page-loading button{padding:8px 12px;border:1px solid #a1a1aa;border-radius:8px;background:transparent;color:inherit;cursor:pointer}</style>
<script>document.documentElement.classList.add('ps-page-loading');</script>
<script src="{{ route('simplify.asset',['asset'=>'page-load.js']) }}?v=0.2.0-alpha.21"></script>
@endif
<link id="simplify-global-css" rel="stylesheet" href="{{ $css }}">
<script>window.simplifyThemeConfig=@js([...$features,...\Simplify\Panel\Services\AppearanceSettings::all(),'css_url'=>$css,'frame_css_url'=>route('simplify.asset',['asset'=>'frame.css']).'?v=0.2.0-alpha.21','legacy_page'=>(request()->routeIs('*.pages.pelican-simplify-settings')&&!request()->boolean('simplify_embed'))||!request()->routeIs('filament.app.pages.simplify','filament.app.pages.simple-server-creator','filament.admin.pages.simple-server-creator','*.pages.pelican-simplify-settings')]);</script>
<script src="{{ route('simplify.asset',['asset'=>'theme.js']) }}?v=0.2.0-alpha.21"></script>
