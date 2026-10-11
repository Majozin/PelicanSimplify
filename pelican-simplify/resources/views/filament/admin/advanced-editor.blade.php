{{-- CSS visibility keeps fields mounted and dehydrated, including untouched automatic defaults. --}}
<div class="ssc-advanced-editor" @if (!$this->editingAdvanced) hidden @endif>
    {{ $getChildSchema() }}
</div>
