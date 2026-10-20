@php
    $every = (int) \Simplify\Panel\Services\PerformanceSettings::get('backup_interval_minutes');
    $interval = $every === 60 ? __('every hour') : ($every % 60 === 0 ? __('every :count hours', ['count' => $every / 60]) : __('every :count minutes', ['count' => $every]));
    $points = \Simplify\Panel\Services\PerformanceSettings::get('backup_retention');
    $unused = \Simplify\Panel\Services\PerformanceSettings::get('backup_only_when_used') ? __(', skipped while the server has not run') : '';
@endphp
<p class="ssc-backup-defaults">{{ \Simplify\Panel\Services\FeatureSettings::enabled('automatic_backups') ? __('Uses automatic settings: :interval, with :points recovery points:unused.', ['interval' => $interval, 'points' => $points, 'unused' => $unused]) : __('Automatic backups are paused in Simplify settings.') }}</p>
