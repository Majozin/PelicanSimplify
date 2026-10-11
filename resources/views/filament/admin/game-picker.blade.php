{{-- Game picker: installed games and the Pelican egg index, searched in the browser (no request per keystroke). --}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php($cards = $getViewData()['cards'] ?? [])
    <div class="ssc-games" wire:ignore
         x-data="{
            state: $wire.$entangle(@js($getStatePath()), true),
            cards: @js($cards),
            query: '',
            filter: 'all',
            limit: 24,
            norm(text) { return String(text || '').toLowerCase().normalize('NFKD').replace(/[^a-z0-9]+/g, ' ').trim(); },
            shown() {
                const words = this.norm(this.query).split(' ').filter(Boolean);
                let list = this.cards.filter((c) => (this.filter !== 'installed' || c.installed) && (this.filter !== 'popular' || c.popular > 0)
                    && words.every((w) => this.norm(c.name + ' ' + c.description).includes(w)));
                list.sort((a, b) => (this.filter === 'popular' || !words.length ? b.popular - a.popular : 0) || (b.installed - a.installed) || a.name.localeCompare(b.name));
                return list;
            },
            initials(name) { return name.replace(/[^A-Za-z0-9 ]/g, ' ').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase() || '?'; },
            hue(name) { let h = 0; for (const ch of name) h = (h * 31 + ch.charCodeAt(0)) % 360; return h; },
            selected() { return this.cards.find((c) => c.key === this.state) || null; },
         }">
        <div class="ssc-games-bar">
            <label class="ssc-games-search">
                <x-tabler-search class="ssc-games-search-icon" />
                <input type="search" x-model.debounce.120ms="query" x-on:input="limit = 24" placeholder="Search {{ count($cards) }} games…" aria-label="Search games" autocomplete="off" />
            </label>
            <div class="ssc-games-chips" role="group" aria-label="Filter games">
                <button type="button" :aria-pressed="filter==='all'" x-on:click="filter='all'; limit=24">All</button>
                <button type="button" :aria-pressed="filter==='popular'" x-on:click="filter='popular'; limit=24">Popular</button>
                <button type="button" :aria-pressed="filter==='installed'" x-on:click="filter='installed'; limit=24">Installed</button>
            </div>
        </div>
        <div class="ssc-games-grid" role="radiogroup" aria-label="Games">
            <template x-for="card in shown().slice(0, limit)" :key="card.key">
                <button type="button" class="ssc-game" role="radio" :aria-checked="state===card.key" x-on:click="state = card.key">
                    <span class="ssc-game-icon" :style="{'--hue': hue(card.name)}" x-text="initials(card.name)"></span>
                    <span class="ssc-game-text">
                        <strong x-text="card.name"></strong>
                        <small x-text="card.description"></small>
                    </span>
                    <span class="ssc-game-badge" :class="card.installed ? 'is-installed' : 'is-available'" x-text="card.installed ? 'Installed' : 'Available'"></span>
                </button>
            </template>
        </div>
        <p class="ssc-games-empty" x-show="!shown().length">No game matches “<span x-text="query"></span>”.</p>
        <button type="button" class="ssc-games-more" x-show="shown().length > limit" x-on:click="limit += 48" x-text="'Show more (' + (shown().length - limit) + ')'"></button>
        <p class="ssc-games-note" x-show="selected() && !selected().installed">
            <strong x-text="selected()?.name"></strong> is not on this panel yet. Its egg is downloaded from the official Pelican egg index and installed when you continue.
        </p>
    </div>
</x-dynamic-component>
