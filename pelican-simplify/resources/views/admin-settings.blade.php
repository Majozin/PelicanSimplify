<x-filament-panels::page>
    @if($this->needsSetup())
        <section class="fi-section" style="padding:1.5rem;margin-bottom:1.5rem">
            <h2>Welcome to Simplify</h2>
            <p>1. Choose the features below. 2. Configure optional self-service grants and resource limits. 3. Finish setup to save your choices.</p>
            <p>All choices can be changed here later. Web features are supplied by the separate Web extension.</p>
        </section>
    @endif
    <form wire:submit="save">
        {{ $this->form }}
        <div style="margin-top:1.5rem"><x-filament::button type="submit">{{ $this->needsSetup() ? 'Finish setup' : 'Save settings' }}</x-filament::button></div>
    </form>
</x-filament-panels::page>
