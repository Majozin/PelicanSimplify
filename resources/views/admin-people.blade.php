<x-filament-panels::page>
    @assets
    <link rel="stylesheet" href="{{ \Simplify\Panel\Services\BuildInfo::asset('wizard.css') }}">
    @endassets
    <div class="ps-admin-page ps-people">
        <nav class="ps-segmented ps-people-tabs" role="tablist" aria-label="{{ __('People and roles') }}">
            <button type="button" role="tab" aria-pressed="{{ $tab === 'people' ? 'true' : 'false' }}" wire:click="$set('tab', 'people')"><x-tabler-users class="ps-ico" />{{ __('People') }}</button>
            <button type="button" role="tab" aria-pressed="{{ $tab === 'roles' ? 'true' : 'false' }}" wire:click="$set('tab', 'roles')"><x-tabler-shield-lock class="ps-ico" />{{ __('Roles') }}</button>
        </nav>

        @if($tab === 'people')
            @php($people = $this->people())
            <div class="ps-people-bar">
                <label class="ps-people-search"><x-tabler-search class="ps-ico" /><input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or email') }}" aria-label="{{ __('Search people') }}"></label>
                <span class="ps-help ps-people-count">{{ $people['total'] }} {{ \Illuminate\Support\Str::plural('person', $people['total']) }}</span>
                <button type="button" class="ps-btn is-primary" wire:click="mountAction('addPerson')"><x-tabler-user-plus class="ps-ico" />{{ __('Add person') }}</button>
            </div>
            <div class="ps-people-list">
                @forelse($people['rows'] as $person)
                    <article class="ps-person" wire:key="person-{{ $person['id'] }}">
                        <span class="ps-person-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($person['username'], 0, 1)) }}</span>
                        <div class="ps-person-main">
                            <strong>{{ $person['username'] }} @if($person['self'])<span class="ps-badge">{{ __('You') }}</span>@endif</strong>
                            <small>{{ $person['email'] }}</small>
                            <div class="ps-person-roles">
                                @forelse($person['roles'] as $role)
                                    <span class="ps-badge {{ $role === \App\Models\Role::ROOT_ADMIN ? 'is-root' : 'is-info' }}">{{ $role }}</span>
                                @empty
                                    <span class="ps-badge is-muted">{{ __('No role · Everyone') }}</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="ps-person-meta">
                            <span data-tip="{{ __('Servers owned') }}"><x-tabler-server class="ps-ico" />{{ $person['servers'] }}</span>
                            <span data-tip="{{ $person['mfa'] ? 'Two-factor sign-in on' : 'Two-factor sign-in off' }}" class="{{ $person['mfa'] ? 'is-ok' : 'is-off' }}"><x-tabler-shield-check class="ps-ico" />2FA</span>
                        </div>
                        <div class="ps-person-actions">
                            <button type="button" class="ps-btn is-sm" wire:click="mountAction('editPerson', { user: {{ $person['id'] }} })"><x-tabler-edit class="ps-ico" />{{ __('Edit') }}</button>
                            <button type="button" class="ps-btn is-sm is-icon is-subtle" data-tip="{{ __('Send password reset link') }}" aria-label="{{ __('Send password reset link') }}" wire:click="mountAction('resetPassword', { user: {{ $person['id'] }} })"><x-tabler-key class="ps-ico" /></button>
                            <button type="button" class="ps-btn is-sm is-icon is-subtle is-danger" data-tip="{{ $person['self'] ? __('You cannot delete yourself') : ($person['servers'] ? __('Owns servers') : __('Delete')) }}" aria-label="{{ __('Delete :name', ['name' => $person['username']]) }}" @disabled($person['self'] || $person['servers']) wire:click="mountAction('deletePerson', { user: {{ $person['id'] }} })"><x-tabler-trash class="ps-ico" /></button>
                        </div>
                    </article>
                @empty
                    <p class="ps-empty">{{ __('Nobody matches “:search”.', ['search' => $search]) }}</p>
                @endforelse
            </div>
            @if($people['pages'] > 1)
                <nav class="ps-people-pages" aria-label="{{ __('Pages') }}">
                    <button type="button" class="ps-btn is-sm" @disabled($peoplePage <= 1) wire:click="setPage({{ $peoplePage - 1 }})">{{ __('Previous') }}</button>
                    <span class="ps-help">{{ __('Page :page of :pages', ['page' => $peoplePage, 'pages' => $people['pages']]) }}</span>
                    <button type="button" class="ps-btn is-sm" @disabled($peoplePage >= $people['pages']) wire:click="setPage({{ $peoplePage + 1 }})">{{ __('Next') }}</button>
                </nav>
            @endif
        @else
            <div class="ps-people-bar">
                <p class="ps-help ps-people-intro">{{ __('A role combines Simplify permissions (what members can do on their servers) with optional admin panel access. Everyone without a role gets the Everyone permissions from Simplify settings → Users & access. Assign roles on the People tab.') }}</p>
                <button type="button" class="ps-btn is-primary" wire:click="mountAction('addRole')"><x-tabler-shield-plus class="ps-ico" />{{ __('Add role') }}</button>
            </div>
            <div class="ps-role-grid">
                @foreach($this->roles() as $role)
                    <article class="ps-role {{ $role['root'] ? 'is-root' : '' }}" wire:key="role-{{ $role['id'] }}">
                        <header>
                            <span class="ps-role-icon">@if($role['root'])<x-tabler-crown class="ps-ico" />@else<x-tabler-shield class="ps-ico" />@endif</span>
                            <strong>{{ $role['name'] }}</strong>
                        </header>
                        <p class="ps-help">
                            {{ trans_choice(':count member|:count members', $role['members'], ['count' => $role['members']]) }}
                            @if($role['root']) · {{ __('Full access to everything') }} @else · {{ __(':abilities Simplify · :permissions admin permissions', ['abilities' => $role['abilities'], 'permissions' => $role['permissions']]) }} @endif
                        </p>
                        @unless($role['root'])
                            <footer>
                                <button type="button" class="ps-btn is-sm" wire:click="mountAction('editRole', { role: {{ $role['id'] }} })"><x-tabler-edit class="ps-ico" />{{ __('Edit') }}</button>
                                <button type="button" class="ps-btn is-sm is-icon is-subtle is-danger" data-tip="{{ __('Delete role') }}" aria-label="{{ __('Delete role :name', ['name' => $role['name']]) }}" wire:click="mountAction('deleteRole', { role: {{ $role['id'] }} })"><x-tabler-trash class="ps-ico" /></button>
                            </footer>
                        @else
                            <footer><span class="ps-help"><x-tabler-lock class="ps-ico" /> {{ __('Managed by Pelican') }}</span></footer>
                        @endunless
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
