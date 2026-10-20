@php
    $settings = [];
    foreach (['server_type', 'minecraft_version', 'modpack_project', 'modpack_version', 'java_version', 'image'] as $key) {
        $settings[$key] = $get($key);
    }
    $egg = \App\Models\Egg::find($get('egg_id'));
    $runtime = \Simplify\Panel\Services\JavaRuntime::class;
    $other = $get('game') === 'other';
    $java = $other ? null : $runtime::major($settings);
    $image = $other && $egg ? \Simplify\Panel\Services\OtherGameCatalog::image($egg) : $runtime::image($settings, $egg);
    $ram = $get('ram_gb') === 'custom' ? $get('ram_custom_gb') : $get('ram_gb');
    $type = ['vanilla' => 'Vanilla', 'plus' => 'Vanilla+ / Paper', 'modded' => __('Modded')][$get('server_type')] ?? '—';
    $version = $runtime::minecraftVersion($settings);
    $rows = [
        'Server name' => $get('name') ?: '—',
        'Server color'=>$get('server_color') ?: __('Automatic'),
        'First start'=>__('Manual · server stays stopped'),
        'Server type' => $type,
        'Minecraft' => $version ?: __('Select a modpack version'),
        'Connection' => \App\Models\Allocation::find($get('allocation_id'))?->address ?? __('Automatic'),
        'RAM' => $ram ? $ram . ' GB' : '—',
        'CPU limit' => (int) $get('cpu') === 0 ? __('Unlimited') : $get('cpu') . '%',
        'Disk space' => round((int) $get('disk_mb') / 1024, 1) . ' GB',
        'Java runtime' => trim((string) $get('image')) !== '' ? __('Custom image') : ($java ? 'Java ' . $java . ' · ' . (($get('java_version') ?: 'auto') === 'auto' ? __('Automatic') : __('Manual')) : __('Not determined')),
        'Egg' => $egg?->name ?? '—',
        'Automatic backups' => \Simplify\Panel\Services\FeatureSettings::enabled('automatic_backups') ? __(':minutes minutes · :points recovery points', ['minutes' => $get('change_backup_schedule')?$get('backup_interval_minutes'):\Simplify\Panel\Services\PerformanceSettings::get('backup_interval_minutes'), 'points' => $get('change_backup_schedule')?$get('backup_retention'):\Simplify\Panel\Services\PerformanceSettings::get('backup_retention')]) : __('Paused'),
        'Server properties' => $get('properties_enabled') ? __(':count players', ['count' => $get('max_players')]) . ' · ' . __(ucfirst((string) $get('gamemode'))) . ' · ' . __(ucfirst((string) $get('difficulty'))) : __('Installer defaults'),
        'MOTD' => $get('properties_enabled') ? $get('motd') : __('Installer default'),
        'Server icon' => $get('server_icon') ? __('Ready to upload') : __('Installer default'),
        'Start after installation' => $get('start_on_completion') ? __('Yes') : __('No'),
    ];
    if (\Simplify\Panel\Services\Subdomains::active()) {
        $rows['Subdomain'] = ($sub = strtolower(trim((string) $get('requested_subdomain')))) !== '' ? $sub . '.' . \Simplify\Panel\Services\Subdomains::settings()['domain'] : __('None');
    }
    if ($other) {
        $rows['Game'] = $egg?->name ?? '—';
        foreach (['Minecraft', 'Java runtime', 'Server type', 'Server properties', 'MOTD', 'Server icon'] as $key) { unset($rows[$key]); }
    }
    if (!$other && $get('server_type') === 'modded') {
        $rows['Modpack'] = \Simplify\Panel\Services\ModpackCatalog::pack($get('modpack_project'))['title'] ?? '—';
        $rows['Modpack version ID'] = $get('modpack_version') ?: '—';
    }
@endphp
<section class="ssc-overview">
    <div class="ssc-overview-heading">
        <h3>{{ __('Server overview') }}</h3>
        <p>{{ __('Everything is prepared. Change settings below if needed.') }}</p>
    </div>
    <dl class="ssc-summary-grid">
        @foreach ($rows as $label => $value)
            <div class="ssc-summary-item"><dt>{{ __($label) }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
        <div class="ssc-summary-item ssc-summary-wide"><dt>{{ __('Docker image') }}</dt><dd><code>{{ $image ?: __('Choose Java in Edit settings') }}</code></dd></div>
    </dl>
    @if (!$other && !$java && !$get('image'))
        <p class="ssc-summary-notice">{{ __('Java could not be determined. Choose a modpack version, or set Java manually below.') }}</p>
    @endif
    <div class="ssc-overview-footer">
        <x-filament::button type="button" color="gray" outlined wire:click="toggleAdvanced">
            {{ $this->editingAdvanced ? __('Done editing') : __('Edit settings') }}
        </x-filament::button>
    </div>
</section>
