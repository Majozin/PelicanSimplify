@php
    $ownPage = request()->routeIs('filament.app.pages.simplify', 'filament.app.pages.simple-server-creator', 'filament.admin.pages.simple-server-creator', '*.pages.pelican-simplify-settings');
    $config = [
        ...\Simplify\Panel\Services\FeatureSettings::all(),
        ...\Simplify\Panel\Services\AppearanceSettings::all(),
        'legacy_page' => !$ownPage || (request()->routeIs('*.pages.pelican-simplify-settings') && !request()->boolean('simplify_embed')),
    ];
@endphp
<link id="simplify-core-css" rel="stylesheet" href="{{ \Simplify\Panel\Services\BuildInfo::asset('core.css') }}">
<script>window.simplifyThemeConfig=@js($config);</script>
<script src="{{ \Simplify\Panel\Services\BuildInfo::asset('core.js') }}"></script>
