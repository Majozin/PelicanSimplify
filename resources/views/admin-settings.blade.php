<x-filament-panels::page>
    <section style="padding:1.5rem;border:1px solid #71717a;border-radius:12px;margin-bottom:1.5rem">
        <h2 style="font-weight:700;font-size:1.2rem">Dependency checks</h2>
        <p>Every required check must pass before setup or settings can be saved.</p>
        <ul style="margin:1rem 0;list-style:none;padding:0">
            @foreach($requirements as $check)
                <li style="margin:.6rem 0">
                    <strong style="color:{{ $check['ok'] ? '#16a34a' : '#ef4444' }}">{{ $check['ok'] ? 'PASS' : 'FAIL' }}</strong>
                    {{ $check['name'] }}
                    @if(!$check['ok'])<p style="margin:.2rem 0 0;color:#ef4444">{{ $check['fix'] }}</p>@endif
                </li>
            @endforeach
        </ul>
        @if(!$this->requirementsReady())<p style="color:#ef4444;font-weight:700">Setup is blocked. Fix the failed checks, then check again.</p>@endif
        <x-filament::button type="button" wire:click="refreshRequirements" wire:loading.attr="disabled">Check again</x-filament::button>
        <p style="margin-top:.8rem">A configured queue is checked here. Your queue worker and scheduler must also be running; this check cannot confirm that they stay active.</p>
    </section>
    @if($this->requirementsReady())
        @if($this->needsSetup())
            <section class="fi-section" style="padding:1.5rem;margin-bottom:1.5rem">
                <h2>Welcome to Simplify</h2>
                <p>Choose your features, access rules and resource limits, then finish setup. You can change these choices later.</p>
            </section>
        @endif
        <form wire:submit="save">
            {{ $this->form }}
            <div style="margin-top:1.5rem"><x-filament::button type="submit" wire:loading.attr="disabled">{{ $this->needsSetup() ? 'Finish setup' : 'Save settings' }}</x-filament::button></div>
        </form>
    @endif
</x-filament-panels::page>
