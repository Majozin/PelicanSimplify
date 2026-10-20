            <section class="ps-setup-checks {{ $this->needsSetup() ? '' : 'ps-diagnostics-panel' }}" @if($this->needsSetup() && !$setupStarted && !$requirements) wire:init="refreshRequirements" @endif>
                <header class="ps-setup-heading">
                    @if(!$this->needsSetup())<button type="button" class="ps-btn is-icon is-subtle ps-diagnostics-close" wire:click="toggleDiagnostics" aria-label="{{ __('Close system checks') }}"><x-tabler-x class="ps-ico" /></button>@endif
                    <span class="ps-setup-mark"><x-tabler-shield-check /></span>
                    <div><span class="ps-setup-eyebrow">{{ $this->needsSetup() ? __('First setup · System check') : __('System diagnostics') }}</span><h2>{{ __('Let’s get Simplify ready') }}</h2><p>{{ __('A quick check of your panel before you choose your settings.') }}</p></div>
                </header>
                <div class="ps-check-loading" wire:loading.flex wire:target="refreshRequirements,beginSetup"><x-tabler-loader-2 class="ps-check-spinner" /><span>{{ __('Checking your panel…') }}</span></div>
                <div wire:loading.remove wire:target="refreshRequirements,beginSetup" aria-live="polite">
                    @if($requirements)
                        @php($passed = count(array_filter($requirements, fn($check) => $check['ok'])))
                        <div class="ps-check-summary {{ $this->requirementsReady() ? 'is-ready' : 'is-blocked' }}">
                            @if($this->requirementsReady())<x-tabler-circle-check />@else<x-tabler-alert-circle />@endif
                            <div><strong>{{ $this->requirementsReady() ? __('Your panel is ready') : __('A few things need attention') }}</strong><span>{{ __(':passed of :total checks passed', ['passed' => $passed, 'total' => count($requirements)]) }}</span></div>
                        </div>
                        <div class="ps-check-grid">
                            @foreach($this->requirementGroups() as $group => $checks)
                                @php($failed = array_filter($checks, fn($check) => !$check['ok']))
                                <article class="ps-check-card {{ $failed ? (array_filter($failed, fn($check) => $check['blocking'] ?? true) ? 'is-blocked' : 'is-warning') : 'is-ready' }}">
                                    <div class="ps-check-card-heading">@if($failed)<x-tabler-alert-circle />@else<x-tabler-circle-check />@endif<h3>{{ __($group) }}</h3><span>{{ count($checks) - count($failed) }}/{{ count($checks) }}</span></div>
                                    @foreach($failed as $check)<div class="ps-check-fix"><strong>{{ $check['name'] }}</strong><p>{{ $check['fix'] }}</p>@if(!empty($check['detail']))<p>{{ $check['detail'] }}</p>@endif</div>@endforeach
                                    @foreach($checks as $check)@if($check['ok'] && !empty($check['detail']))<p class="ps-help">{{ $check['detail'] }}</p>@endif@endforeach
                                    <details><summary>{{ __('View checks') }}</summary><ul>@foreach($checks as $check)<li><span class="ps-check-dot {{ $check['ok'] ? 'is-ready' : 'is-blocked' }}"></span><span>{{ $check['name'] }}</span><small>{{ $check['ok'] ? __('Ready') : (($check['blocking'] ?? true) ? __('Needs attention') : __('Warning · optional')) }}</small></li>@endforeach</ul></details>
                                </article>
                            @endforeach
                        </div>
                        <p class="ps-check-note"><x-tabler-info-circle /><span>{{ __('Keep your queue worker and scheduler running. Their live status cannot be verified here.') }}</span></p>
                        <footer class="ps-check-actions">
                            <x-filament::button type="button" color="gray" wire:click="refreshRequirements" wire:loading.attr="disabled">{{ __('Check again') }}</x-filament::button>
                            @if($this->needsSetup() && !$setupStarted)<x-filament::button type="button" wire:click="beginSetup" wire:loading.attr="disabled" :disabled="!$this->requirementsReady()" icon="tabler-arrow-right" icon-position="after">{{ __('Go to first setup') }}</x-filament::button>@endif
                        </footer>
                    @else
                        <p class="ps-check-placeholder">{{ __('Preparing your system checks…') }}</p>
                    @endif
                </div>
            </section>
