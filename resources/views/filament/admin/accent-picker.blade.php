<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="ssc-accent-field" wire:key="simplify-accent-{{ $getStatePath() }}" x-data="{color:$wire.entangle('{{ $getStatePath() }}')}">
        <div class="ps-swatches" role="group" aria-label="{{ $getLabel() }}">
            @foreach (\Simplify\Panel\Services\ServerAppearance::choices() as $choice => $name)
                <button type="button" class="ps-swatch" style="background:{{ $choice }}" data-tip="{{ __($name) }}" aria-label="{{ __('Select :name accent', ['name' => __($name)]) }}" :aria-pressed="(color || '').toLowerCase()==='{{ $choice }}'" x-on:click="color='{{ $choice }}'" @disabled($isDisabled())></button>
            @endforeach
            <label class="ps-btn is-sm">{{ __('Custom color') }}<input type="color" class="ps-color-input" :value="/^#[0-9a-f]{6}$/i.test(color || '')?color:'#878787'" x-on:input="color=$event.target.value" aria-label="{{ __(':label custom color', ['label' => $getLabel()]) }}" @disabled($isDisabled())></label>
            <input type="text" class="ssc-color-hex" x-model="color" maxlength="7" placeholder="#878787" aria-label="{{ __(':label hex value', ['label' => $getLabel()]) }}" spellcheck="false" @disabled($isDisabled())>
        </div>
    </div>
</x-dynamic-component>
