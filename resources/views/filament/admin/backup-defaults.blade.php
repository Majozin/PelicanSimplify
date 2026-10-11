@php
    $every = (int) \Simplify\Panel\Services\PerformanceSettings::get('backup_interval_minutes');
    $interval = $every === 60 ? 'every hour' : 'every '.($every % 60 === 0 ? ($every / 60).' hours' : $every.' minutes');
    $points = \Simplify\Panel\Services\PerformanceSettings::get('backup_retention');
    $unused = \Simplify\Panel\Services\PerformanceSettings::get('backup_only_when_used') ? ', skipped while the server has not run' : '';
@endphp
<p class="ssc-backup-defaults">{{ \Simplify\Panel\Services\FeatureSettings::enabled('automatic_backups') ? "Uses automatic settings: {$interval}, with {$points} recovery points{$unused}." : 'Automatic backups are paused in Simplify settings.' }}</p>
