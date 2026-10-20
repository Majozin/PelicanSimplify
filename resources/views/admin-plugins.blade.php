<x-filament-panels::page>
    @assets
    <link rel="stylesheet" href="{{ \Simplify\Panel\Services\BuildInfo::asset('wizard.css') }}">
    @endassets
    @php($plugins = $this->plugins())
    <div class="ps-admin-page ps-plugins">
        <header class="ps-plugins-head">
            @include('pelican-simplify::partials-conflict-notice')
            <div class="ps-alert is-info ps-plugin-channel" role="note">
                <x-tabler-versions class="ps-ico" aria-hidden="true" />
                <div>
                    <strong>Pelican {{ \Simplify\Panel\Services\PluginUpdater::panelVersion() }} · {{ \Simplify\Panel\Services\PluginUpdater::channel() === 'beta' ? __('Simplify beta channel') : __('Simplify stable channel') }}</strong>
                    <small>{{ __('Updates are always the build made for your Pelican version, so you never get a version that does not fit your panel.') }} {{ \Simplify\Panel\Services\PluginUpdater::channel() === 'beta' ? __('You also get beta releases.') : __('You get stable releases only.') }} {{ __('Change this in Simplify settings → Plugin manager.') }}</small>
                </div>
            </div>
            <p class="ps-help">
                @if(\Simplify\Panel\Services\FeatureSettings::enabled('auto_update_plugins'))
                    {{ __('New versions are installed automatically within about a minute, with a backup and automatic rollback.') }}
                @else
                    {{ __('Automatic updates are off: you get a notification for each new version; install it here. A backup is kept and restored if anything fails.') }}
                @endif
            </p>
            @php($age = $this->lastCheckAge())
            @if($age === null || $age > 300)
                <p class="ps-plugin-error">{{ $age === null ? __('The automatic update check has not run yet.') : __('The automatic update check last ran :time ago.', ['time' => \Carbon\CarbonInterval::seconds($age)->cascade()->forHumans(['parts' => 1])]) }} {{ __('Pelican\'s scheduler is probably not running: add this cron job for the web server user. Updates from this page still work.') }} <code>* * * * * php {{ base_path('artisan') }} schedule:run</code></p>
            @endif
        </header>
        <div class="ps-plugin-list" @if($this->busy) wire:poll.3s @else wire:poll.15s @endif>
            @forelse($plugins as $plugin)
                <article class="ps-plugin" wire:key="plugin-{{ $plugin['id'] }}">
                    <div class="ps-plugin-main">
                        <div class="ps-plugin-title">
                            <strong>{{ $plugin['name'] }}</strong>
                            <span class="ps-badge ps-status {{ ['enabled' => 'running', 'disabled' => 'unavailable', 'not_installed' => 'unavailable', 'errored' => 'offline', 'incompatible' => 'offline'][$plugin['status']] ?? '' }}"><i></i><span>{{ ['enabled' => __('enabled'), 'disabled' => __('disabled'), 'not_installed' => __('not installed'), 'errored' => __('errored'), 'incompatible' => __('incompatible')][$plugin['status']] ?? str_replace('_', ' ', $plugin['status']) }}</span></span>
                            @if($plugin['latest'])<span class="ps-badge is-info"><span>{{ __('Update :version', ['version' => $plugin['latest']]) }}</span></span>@endif
                        </div>
                        <small>{{ $plugin['author'] }} · {{ __('version :version', ['version' => $plugin['version']]) }}</small>
                        @if($plugin['description'])<p>{{ $plugin['description'] }}</p>@endif
                        @if($plugin['busy_since'])
                            <p class="ps-help">{{ __('Working on it… you get a notification when it is done.') }}
                                @if(now()->timestamp - (int) $plugin['busy_since'] > 300)<span class="ps-plugin-error">{{ __('This is taking long: reload the page; if it stays, check the panel log.') }}</span>@endif
                            </p>
                        @endif
                        @if($plugin['message'] && in_array($plugin['status'], ['errored', 'incompatible', 'not_installed'], true))<p class="ps-plugin-error">{{ $plugin['message'] }}</p>@endif
                    </div>
                    @if($plugin['can_change'])
                        <div class="ps-plugin-actions">
                            @if($plugin['latest'])
                                <button type="button" class="ps-btn is-sm is-primary" wire:click="updatePlugin('{{ $plugin['id'] }}')" wire:loading.attr="disabled" @disabled($plugin['updating'])>
                                    <x-tabler-download class="ps-ico" />{{ $plugin['updating'] ? __('Updating…') : __('Update') }}
                                </button>
                            @endif
                            @if($plugin['status'] === 'not_installed')
                                <button type="button" class="ps-btn is-sm is-primary" wire:click="installPlugin('{{ $plugin['id'] }}')">{{ __('Install') }}</button>
                            @endif
                            @if($plugin['status'] === 'enabled')
                                <button type="button" class="ps-btn is-sm" wire:click="setEnabled('{{ $plugin['id'] }}', false)">{{ __('Disable') }}</button>
                            @elseif($plugin['status'] === 'disabled')
                                <button type="button" class="ps-btn is-sm" wire:click="setEnabled('{{ $plugin['id'] }}', true)">{{ __('Enable') }}</button>
                            @endif
                            @if(in_array($plugin['status'], ['not_installed', 'errored'], true))
                                @if($plugin['can_delete'])
                                    <button type="button" class="ps-btn is-sm is-danger" wire:click="deletePlugin('{{ $plugin['id'] }}')" wire:confirm="{{ __('Delete :name and its files?', ['name' => $plugin['name']]) }}">{{ __('Delete') }}</button>
                                @endif
                            @else
                                <button type="button" class="ps-btn is-sm is-danger" wire:click="uninstallPlugin('{{ $plugin['id'] }}')" wire:confirm="{{ __('Uninstall :name? Its data may be removed.', ['name' => $plugin['name']]) }}">{{ __('Uninstall') }}</button>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <p class="ps-empty">{{ __('No plugins installed.') }}</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
