@php
    $ownPage = request()->routeIs('filament.app.pages.simplify', 'filament.app.pages.simple-server-creator', 'filament.admin.pages.simple-server-creator', '*.pages.pelican-simplify-settings');
    $config = [
        ...\Simplify\Panel\Services\FeatureSettings::all(),
        ...\Simplify\Panel\Services\AppearanceSettings::all(),
        'user' => user()?->username,
        'legacy_page' => !$ownPage || (request()->routeIs('*.pages.pelican-simplify-settings') && !request()->boolean('simplify_embed')),
    ];
@endphp
@if($scaleStyle = \Simplify\Panel\Services\InterfaceScale::style())<style id="simplify-scale">{!! $scaleStyle !!}</style>@endif
<link id="simplify-core-css" rel="stylesheet" href="{{ \Simplify\Panel\Services\BuildInfo::asset('core.css') }}">
<script>window.simplifyThemeConfig=@js($config);@if($i18n = \Simplify\Panel\Services\Translations::forJs())window.simplifyI18n=@js($i18n);@endif</script>
<script src="{{ \Simplify\Panel\Services\BuildInfo::asset('core.js') }}"></script>
