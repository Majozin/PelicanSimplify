<section class="ps-banner-library">
    <header><h2>Server banners</h2><p>Upload banners from a server’s image picker. Manage uploaded images here; the standard collection always stays available.</p></header>
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
