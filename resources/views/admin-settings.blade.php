<x-filament-panels::page>
    @assets
    @include('pelican-simplify::filament.admin.styles')
    <script src="{{ route('simplify.asset',['asset'=>'setup-progress.js']) }}?v=0.2.0-alpha.6"></script>
    @endassets
    <div class="ps-settings-page">
        @if(($this->needsSetup() && !$setupStarted) || $showDiagnostics)
            <section class="ps-setup-checks" @if($this->needsSetup() && !$setupStarted && !$requirements) wire:init="refreshRequirements" @endif>
                <header class="ps-setup-heading">
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
                                <article class="ps-check-card {{ $failed ? 'is-blocked' : 'is-ready' }}">
                                    <div class="ps-check-card-heading">@if($failed)<x-tabler-alert-circle />@else<x-tabler-circle-check />@endif<h3>{{ $group }}</h3><span>{{ count($checks) - count($failed) }}/{{ count($checks) }}</span></div>
                                    @foreach($failed as $check)<div class="ps-check-fix"><strong>{{ $check['name'] }}</strong><p>{{ $check['fix'] }}</p></div>@endforeach
                                    <details><summary>View checks</summary><ul>@foreach($checks as $check)<li><span class="ps-check-dot {{ $check['ok'] ? 'is-ready' : 'is-blocked' }}"></span><span>{{ $check['name'] }}</span><small>{{ $check['ok'] ? 'Ready' : 'Needs attention' }}</small></li>@endforeach</ul></details>
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
        @endif
        @if(!$this->needsSetup() || $setupStarted)
            @if($this->needsSetup())
                <header class="ps-setup-heading ps-setup-welcome"><span class="ps-setup-mark"><x-tabler-adjustments /></span><div><span class="ps-setup-eyebrow">First setup · Preferences</span><h2>Make Simplify yours</h2><p>Choose your features, access rules and resource limits. You can change these later.</p></div></header>
            @endif
            <div class="ssc-wizard ps-settings-form" @if($this->needsSetup()) x-data="simplifySetupProgress()" @endif>
                @if($this->needsSetup())
                    <nav class="ssc-progress" wire:ignore aria-label="Simplify setup progress">
                        @foreach($this->setupSteps() as $index => $label)
                            <button type="button" :class="{'is-current':step==={{ $index+1 }},'is-complete':furthestStep>{{ $index+1 }}}" :disabled="{{ $index+1 }}>furthestStep" :aria-current="step==={{ $index+1 }}?'step':null" x-on:click="goToStep({{ $index+1 }})"><span>{{ $index+1 }}</span>{{ $label }}</button>
                            @unless($loop->last)<span class="ssc-step-arrow" aria-hidden="true">&gt;</span>@endunless
                        @endforeach
                    </nav>
                @endif
                <form wire:submit="save">
                    {{ $this->form }}
                    @if(!$this->needsSetup())<footer class="ps-settings-actions"><x-filament::button type="submit" wire:loading.attr="disabled" icon="tabler-check">Save settings</x-filament::button></footer>@endif
                </form>
            </div>
        @endif
        @if(!$this->needsSetup())<button type="button" class="ps-diagnostics-toggle" wire:click="toggleDiagnostics" wire:loading.attr="disabled"><x-tabler-shield-check />{{ $showDiagnostics ? 'Hide system checks' : 'System checks' }}</button>@endif
    </div>
</x-filament-panels::page>
