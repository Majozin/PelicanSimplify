<x-filament-panels::page>
    @assets
    <link rel="stylesheet" href="{{ \Simplify\Panel\Services\BuildInfo::asset('wizard.css') }}">
    @endassets
    @php($plugins = $this->plugins())
    <div class="ps-admin-page ps-plugins">
        <header class="ps-plugins-head">
            <p class="ps-help">
                @if(\Simplify\Panel\Services\FeatureSettings::enabled('auto_update_plugins'))
                    Updates are installed automatically once a day, with a backup and automatic rollback.
                @else
                    Automatic updates are off; install updates here. A backup is kept and restored if anything fails.
                @endif
            </p>
            <a class="ps-btn is-sm" href="{{ $this->legacyUrl() }}"><x-tabler-file-import class="ps-ico" />Import or remove plugins</a>
        </header>
        <div class="ps-plugin-list" wire:poll.15s>
            @forelse($plugins as $plugin)
                <article class="ps-plugin" wire:key="plugin-{{ $plugin['id'] }}">
                    <div class="ps-plugin-main">
                        <div class="ps-plugin-title">
                            <strong>{{ $plugin['name'] }}</strong>
                            <span class="ps-badge ps-status {{ ['enabled' => 'running', 'disabled' => 'unavailable', 'not_installed' => 'unavailable', 'errored' => 'offline', 'incompatible' => 'offline'][$plugin['status']] ?? '' }}"><i></i><span>{{ str_replace('_', ' ', $plugin['status']) }}</span></span>
                            @if($plugin['latest'])<span class="ps-badge is-info"><span>Update {{ $plugin['latest'] }}</span></span>@endif
                        </div>
                        <small>{{ $plugin['author'] }} · version {{ $plugin['version'] }}</small>
                        @if($plugin['description'])<p>{{ $plugin['description'] }}</p>@endif
                        @if($plugin['message'] && in_array($plugin['status'], ['errored', 'incompatible'], true))<p class="ps-plugin-error">{{ $plugin['message'] }}</p>@endif
                    </div>
                    @if($plugin['can_change'])
                        <div class="ps-plugin-actions">
                            @if($plugin['latest'])
                                <button type="button" class="ps-btn is-sm is-primary" wire:click="updatePlugin('{{ $plugin['id'] }}')" wire:loading.attr="disabled" @disabled($plugin['updating'])>
                                    <x-tabler-download class="ps-ico" />{{ $plugin['updating'] ? 'Updating…' : 'Update' }}
                                </button>
                            @endif
                            @if($plugin['status'] === 'enabled')
                                <button type="button" class="ps-btn is-sm" wire:click="setEnabled('{{ $plugin['id'] }}', false)">Disable</button>
                            @elseif($plugin['status'] === 'disabled')
                                <button type="button" class="ps-btn is-sm" wire:click="setEnabled('{{ $plugin['id'] }}', true)">Enable</button>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <p class="ps-empty">No plugins installed.</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
