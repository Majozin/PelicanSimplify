<section class="ps-banner-library">
    <header><h2>{{ __('Server banners') }}</h2><p>{{ __('Upload and manage server banners here. The standard collection always stays available.') }}</p></header>
    <div class="ps-banner-upload">
        <label class="ps-field">{{ __('Server') }}<select wire:model="bannerServer"><option value="">{{ __('Choose a server') }}</option>@foreach($this->bannerServers() as $uuid => $name)<option value="{{ $uuid }}">{{ $name }}</option>@endforeach</select></label>
        @error('bannerServer')<p class="ps-alert is-danger" role="alert">{{ $message }}</p>@enderror
        <label class="ps-field">{{ __('Banner') }}<input type="file" accept="image/png,image/jpeg,image/webp" wire:model="bannerUpload"></label>
        @error('bannerUpload')<p class="ps-alert is-danger" role="alert">{{ $message }}</p>@enderror
        <small class="ps-help">{{ __('JPG, PNG or WebP · up to :size', ['size' => \Simplify\Panel\Services\UploadLimits::label(\Simplify\Panel\Services\UploadLimits::bannerBytes())]) }}</small>
        <label class="ps-check"><input type="checkbox" role="switch" wire:model="useUploadedBanner"> {{ __('Use this banner on the selected server') }}</label>
        <button type="button" class="ps-btn is-primary" wire:click="uploadBanner" wire:loading.attr="disabled" wire:target="bannerUpload,uploadBanner"><span wire:loading.remove wire:target="bannerUpload,uploadBanner">{{ __('Upload banner') }}</span><span wire:loading wire:target="bannerUpload,uploadBanner">{{ __('Uploading…') }}</span></button>
    </div>
    <h3>{{ __('Standard banners') }}</h3>
    <div class="ps-banner-library-grid">
        @foreach(\Simplify\Panel\Services\ServerArtwork::BACKGROUNDS as $id => $file)
            <article><img src="{{ route('simplify.background',['id'=>$id]) }}" alt="{{ ucfirst($id) }}" loading="lazy"><div><strong>{{ ucfirst($id) }}</strong><small>{{ __('Included · always available') }}</small></div></article>
        @endforeach
    </div>
    <h3>{{ __('Uploaded banners') }}</h3>
    <div class="ps-banner-library-grid">
        @php($library = $this->bannerLibrary())
        @php($serverNames = \App\Models\Server::query()->whereIn('uuid', array_column($library, 'server_uuid'))->pluck('name', 'uuid')->all())
        @forelse($library as $banner)
            <article><img src="{{ $banner['url'] }}" alt="{{ $banner['name'] }}" loading="lazy"><div><strong>{{ $banner['name'] }}</strong><small>{{ $serverNames[$banner['server_uuid']] ?? __('Deleted server') }}</small><button type="button" class="ps-btn is-sm is-danger" x-on:click="Simplify.confirm({title:@js(__('Remove this uploaded banner?')),message:@js(__('Servers using it return to their automatic background.')),confirmLabel:@js(__('Remove banner')),tone:'danger'}).then(ok => ok && $wire.deleteBanner(@js($banner['id'])))" wire:loading.attr="disabled">{{ __('Remove upload') }}</button></div></article>
        @empty
            <p class="ps-banner-empty">{{ __('No uploaded banners yet.') }}</p>
        @endforelse
    </div>
</section>
