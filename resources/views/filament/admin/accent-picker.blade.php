<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="ssc-accent-field" wire:key="simplify-accent-{{ $getStatePath() }}" x-data="{color:$wire.entangle('{{ $getStatePath() }}')}">
        <div class="ssc-accent-options" role="group" aria-label="{{ $getLabel() }}">
            @foreach (\Simplify\Panel\Services\ServerAppearance::choices() as $choice => $name)
                <button type="button" class="ssc-accent-swatch" style="background:{{ $choice }}" title="{{ $name }}" aria-label="Select {{ $name }} accent" :aria-pressed="(color || '').toLowerCase()==='{{ $choice }}'" x-on:click="color='{{ $choice }}'" @disabled($isDisabled())></button>
            @endforeach
            <label class="ssc-settings-custom-color"><span>Custom color</span><input type="color" :value="/^#[0-9a-f]{6}$/i.test(color || '')?color:'#878787'" x-on:input="color=$event.target.value" aria-label="{{ $getLabel() }} custom color" @disabled($isDisabled())></label>
            <input type="text" class="ssc-settings-color-hex" x-model="color" maxlength="7" placeholder="#878787" aria-label="{{ $getLabel() }} hex value" spellcheck="false" @disabled($isDisabled())>
        </div>
    </div>
</x-dynamic-component>
