<div class="ssc-accent-field" wire:key="ssc-server-accent" wire:ignore x-data="{color:$wire.entangle('data.server_color')}">
    <span class="ssc-accent-label" id="ssc-accent-label">{{ __('Server accent color') }}</span>
    <div class="ps-swatches" role="group" aria-labelledby="ssc-accent-label">
        @foreach ($this->availableServerColors() as $choice => $name)
            <button type="button" class="ps-swatch" style="background:{{ $choice }}" data-tip="{{ __($name) }}" aria-label="{{ __('Select :name server accent color', ['name' => __($name)]) }}" :aria-pressed="color==='{{ $choice }}'" x-on:click="color='{{ $choice }}';$dispatch('change')"></button>
        @endforeach
        <label class="ps-btn is-sm"><span class="ps-swatch" style="width:16px;height:16px" :style="{'background-color':color}"></span>{{ __('Custom') }}<input x-ref="customColor" type="color" x-model="color" aria-label="{{ __('Custom server accent color') }}" class="ssc-accent-native"></label>
    </div>
</div>
