<div class="ssc-accent-field" wire:key="ssc-server-accent" wire:ignore x-data="{color:$wire.entangle('data.server_color')}">
    <span class="ssc-accent-label" id="ssc-accent-label">Server accent color</span>
    <div class="ssc-accent-options" role="group" aria-labelledby="ssc-accent-label">
        @foreach ($this->availableServerColors() as $choice => $name)
            <button type="button" class="ssc-accent-swatch" style="background:{{ $choice }}" title="{{ $name }}" aria-label="Select {{ $name }} server accent color" :aria-pressed="color==='{{ $choice }}'" x-on:click="color='{{ $choice }}';$dispatch('change')"></button>
        @endforeach
        <button type="button" class="ssc-accent-custom" x-on:click="$refs.customColor.click()" title="Choose a custom color"><span :style="{'background-color':color}"></span>Custom</button>
        <input x-ref="customColor" type="color" x-model="color" aria-label="Custom server accent color" class="ssc-accent-native">
    </div>
</div>
