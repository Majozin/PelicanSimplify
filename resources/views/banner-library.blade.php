<section class="ps-banner-library">
    <header><h2>Server banners</h2><p>Upload and manage server banners here. The standard collection always stays available.</p></header>
    <div class="ps-banner-upload">
        <label>Server<select wire:model="bannerServer"><option value="">Choose a server</option>@foreach($this->bannerServers() as $uuid => $name)<option value="{{ $uuid }}">{{ $name }}</option>@endforeach</select></label>
        @error('bannerServer')<p role="alert">{{ $message }}</p>@enderror
        <label>Banner<input type="file" accept="image/png,image/jpeg,image/webp" wire:model="bannerUpload"></label>
        @error('bannerUpload')<p role="alert">{{ $message }}</p>@enderror
        <small>JPG, PNG or WebP · up to {{ \Simplify\Panel\Services\UploadLimits::label(\Simplify\Panel\Services\UploadLimits::bannerBytes()) }}</small>
        <label class="ps-banner-use"><input type="checkbox" wire:model="useUploadedBanner"> Use this banner on the selected server</label>
        <button type="button" class="fi-btn fi-color-primary" wire:click="uploadBanner" wire:loading.attr="disabled" wire:target="bannerUpload,uploadBanner"><span wire:loading.remove wire:target="bannerUpload,uploadBanner">Upload banner</span><span wire:loading wire:target="bannerUpload,uploadBanner">Uploading…</span></button>
    </div>
    <h3>Standard banners</h3>
    <div class="ps-banner-library-grid">
        @foreach(\Simplify\Panel\Services\ServerArtwork::BACKGROUNDS as $id => $file)
            <article><img src="{{ route('simplify.background',['id'=>$id]) }}" alt="{{ ucfirst($id) }}" loading="lazy"><div><strong>{{ ucfirst($id) }}</strong><small>Included · always available</small></div></article>
        @endforeach
    </div>
    <h3>Uploaded banners</h3>
    <div class="ps-banner-library-grid">
        @forelse($this->bannerLibrary() as $banner)
            <article><img src="{{ $banner['url'] }}" alt="{{ $banner['name'] }}" loading="lazy"><div><strong>{{ $banner['name'] }}</strong><small>{{ \App\Models\Server::query()->where('uuid',$banner['server_uuid'])->value('name') ?? 'Deleted server' }}</small><button type="button" wire:click="deleteBanner('{{ $banner['id'] }}')" wire:confirm="Remove this uploaded banner? Servers using it will return to their automatic background." wire:loading.attr="disabled">Remove upload</button></div></article>
        @empty
            <p class="ps-banner-empty">No uploaded banners yet.</p>
        @endforelse
    </div>
</section>
