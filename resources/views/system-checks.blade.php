            <section class="ps-setup-checks {{ $this->needsSetup() ? '' : 'ps-diagnostics-panel' }}" @if($this->needsSetup() && !$setupStarted && !$requirements) wire:init="refreshRequirements" @endif>
                <header class="ps-setup-heading">
                    @if(!$this->needsSetup())<button type="button" class="ps-diagnostics-close" wire:click="toggleDiagnostics" aria-label="Close system checks"><x-tabler-x /></button>@endif
                    <span class="ps-setup-mark"><x-tabler-shield-check /></span>
                    <div><span class="ps-setup-eyebrow">{{ $this->needsSetup() ? 'First setup · System check' : 'System diagnostics' }}</span><h2>Let’s get Simplify ready</h2><p>A quick check of your panel before you choose your settings.</p></div>
                </header>
                <div class="ps-check-loading" wire:loading.flex wire:target="refreshRequirements,beginSetup"><x-tabler-loader-2 class="ps-check-spinner" /><span>Checking your panel…</span></div>
                <div wire:loading.remove wire:target="refreshRequirements,beginSetup" aria-live="polite">
                    @if($requirements)
                        @php($passed = count(array_filter($requirements, fn($check) => $check['ok'])))
                        <div class="ps-check-summary {{ $this->requirementsReady() ? 'is-ready' : 'is-blocked' }}">
                            @if($this->requirementsReady())<x-tabler-circle-check />@else<x-tabler-alert-circle />@endif
                            <div><strong>{{ $this->requirementsReady() ? 'Your panel is ready' : 'A few things need attention' }}</strong><span>{{ $passed }} of {{ count($requirements) }} checks passed</span></div>
                        </div>
                        <div class="ps-check-grid">
                            @foreach($this->requirementGroups() as $group => $checks)
                                @php($failed = array_filter($checks, fn($check) => !$check['ok']))
                                <article class="ps-check-card {{ $failed ? (array_filter($failed, fn($check) => $check['blocking'] ?? true) ? 'is-blocked' : 'is-warning') : 'is-ready' }}">
                                    <div class="ps-check-card-heading">@if($failed)<x-tabler-alert-circle />@else<x-tabler-circle-check />@endif<h3>{{ $group }}</h3><span>{{ count($checks) - count($failed) }}/{{ count($checks) }}</span></div>
                                    @foreach($failed as $check)<div class="ps-check-fix"><strong>{{ $check['name'] }}</strong><p>{{ $check['fix'] }}</p>@if(!empty($check['detail']))<p>{{ $check['detail'] }}</p>@endif</div>@endforeach
                                    @foreach($checks as $check)@if($check['ok'] && !empty($check['detail']))<p class="ps-help">{{ $check['detail'] }}</p>@endif@endforeach
                                    <details><summary>View checks</summary><ul>@foreach($checks as $check)<li><span class="ps-check-dot {{ $check['ok'] ? 'is-ready' : 'is-blocked' }}"></span><span>{{ $check['name'] }}</span><small>{{ $check['ok'] ? 'Ready' : (($check['blocking'] ?? true) ? 'Needs attention' : 'Warning · optional') }}</small></li>@endforeach</ul></details>
                                </article>
                            @endforeach
                        </div>
                        <p class="ps-check-note"><x-tabler-info-circle /><span>Keep your queue worker and scheduler running. Their live status cannot be verified here.</span></p>
                        <footer class="ps-check-actions">
                            <x-filament::button type="button" color="gray" wire:click="refreshRequirements" wire:loading.attr="disabled">Check again</x-filament::button>
                            @if($this->needsSetup() && !$setupStarted)<x-filament::button type="button" wire:click="beginSetup" wire:loading.attr="disabled" :disabled="!$this->requirementsReady()" icon="tabler-arrow-right" icon-position="after">Go to first setup</x-filament::button>@endif
                        </footer>
                    @else
                        <p class="ps-check-placeholder">Preparing your system checks…</p>
                    @endif
                </div>
            </section>
