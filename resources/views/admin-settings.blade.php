<x-filament-panels::page>
    @assets
    @include('pelican-simplify::filament.admin.styles')
    @if(!$this->embedded)<link rel="stylesheet" href="{{ route('simplify.asset',['asset'=>'settings-page.css']) }}?v=0.2.0-alpha.26">@endif
    <script src="{{ route('simplify.asset',['asset'=>'setup-progress.js']) }}?v=0.2.0-alpha.26"></script>
    @endassets
    <div class="ps-settings-page {{ $this->embedded ? 'is-embedded' : 'is-standalone' }}">
        @if($this->needsSetup() && !$setupStarted)
            @include('pelican-simplify::system-checks')
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
        @if(!$this->needsSetup())
            @if($showDiagnostics)
                @include('pelican-simplify::system-checks')
            @else
                <button type="button" class="ps-diagnostics-toggle" wire:click="toggleDiagnostics" wire:loading.attr="disabled"><x-tabler-shield-check />System checks</button>
            @endif
        @endif
    </div>
</x-filament-panels::page>
