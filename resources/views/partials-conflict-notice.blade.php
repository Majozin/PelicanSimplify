@php($conflicts = \Simplify\Panel\Services\ExternalPlugins::conflicts())
@if($conflicts)
    <div class="ps-alert is-warn ps-conflict-card" role="alert">
        <x-tabler-alert-triangle class="ps-ico" aria-hidden="true" />
        <div>
            <strong>{{ trans_choice('Simplify\'s dashboard is switched off: :plugins is installed|Simplify\'s dashboard is switched off: :plugins are installed', count($conflicts), ['plugins' => collect($conflicts)->pluck('name')->join(', ', ' '.__('and').' ')]) }}</strong>
            @foreach($conflicts as $conflict)<small>{{ $conflict['name'] }}: {{ $conflict['reason'] }}</small>@endforeach
            <small>{{ trans_choice('Everyone gets Pelican\'s own panel for now. Disable or remove that plugin to turn Simplify back on; your Simplify settings are kept.|Everyone gets Pelican\'s own panel for now. Disable or remove those plugins to turn Simplify back on; your Simplify settings are kept.', count($conflicts)) }}</small>
        </div>
    </div>
@endif
