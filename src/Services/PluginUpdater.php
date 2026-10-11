<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;

use App\Models\Plugin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

/**
 * Safe plugin updates. Pelican's own update swaps the plugin folder (including plugin.json and its install
 * status) and leaves the plugin disabled or half installed. This keeps a backup, downloads (checking the
 * published sha256 when the update file has one), restores the install status, installs in a fresh PHP
 * process and confirms the panel boots with it. Any failure restores the previous version.
 */
final class PluginUpdater
{
    /** Installed plugins with an update for this panel version. */
    public static function available(): array
    {
        Plugin::refreshRows();
        return Plugin::all()->filter(function (Plugin $plugin): bool {
            try {
                return $plugin->status->value !== 'not_installed' && $plugin->isUpdateAvailable();
            } catch (\Throwable) {
                return false;
            }
        })->values()->all();
    }

    /** The version an update file offers for this panel, cached like Pelican's own check. */
    public static function latestVersion(Plugin $plugin): ?string
    {
        if (!$plugin->update_url) return null;
        return Cache::remember('simplify.plugin-latest.'.$plugin->id, 600, function () use ($plugin): ?string {
            try {
                $data = Http::timeout(5)->connectTimeout(2)->get((string) $plugin->update_url)->throw()->json();
                $data = is_array($data[$plugin->id] ?? null) ? $data[$plugin->id] : $data;
                $entry = $data[config('app.version')] ?? $data['*'] ?? null;
                return is_array($entry) && is_string($entry['version'] ?? null) ? $entry['version'] : null;
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /** @return array{ok: bool, message: string} */
    public static function update(Plugin $plugin, ?int $notifyUserId = null): array
    {
        $id = $plugin->id;
        $lock = Cache::lock('simplify.plugin-update.'.$id, 1200);
        if (!$lock->get()) return ['ok' => false, 'message' => $plugin->name.' is already being updated.'];
        $directory = plugin_path($id);
        $backup = storage_path('app/simplify/plugin-backups/'.$id);
        $from = $plugin->version;
        $result = null;
        try {
            $url = $plugin->getDownloadUrlForUpdate();
            if (!is_string($url) || $url === '') throw new \RuntimeException('No download is published for this panel version.');
            $manifest = File::json($directory.'/plugin.json');
            $meta = is_array($manifest['meta'] ?? null) ? $manifest['meta'] : [];
            $wasEnabled = ($meta['status'] ?? '') === 'enabled';

            File::deleteDirectory($backup);
            File::ensureDirectoryExists(dirname($backup));
            if (!File::copyDirectory($directory, $backup)) throw new \RuntimeException('Could not back up the current version.');

            try {
                self::download($plugin, $url);
                // Keep the install status the new plugin.json would otherwise wipe.
                $next = File::json($directory.'/plugin.json');
                $next['meta'] = [...(is_array($next['meta'] ?? null) ? $next['meta'] : []), ...$meta, 'status_message' => null];
                File::put($directory.'/plugin.json', json_encode($next, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $to = (string) ($next['version'] ?? '?');

                $install = Process::path(base_path())->timeout(1100)->run(array_values(array_filter([PHP_BINARY, 'artisan', 'simplify:plugin-install', $id, $wasEnabled ? '--enable' : null])));
                if (!$install->successful()) throw new \RuntimeException(self::output($install) ?: 'The new version could not be installed.');
                // A second fresh process proves the panel still boots with the new code.
                $boot = Process::path(base_path())->timeout(120)->run([PHP_BINARY, 'artisan', 'list', '--raw']);
                if (!$boot->successful()) throw new \RuntimeException('The panel does not start with the new version: '.self::output($boot));
                $result = ['ok' => true, 'message' => $plugin->name.' updated from '.$from.' to '.$to.'.'];
            } catch (\Throwable $exception) {
                self::restore($directory, $backup);
                report($exception);
                $result = ['ok' => false, 'message' => $plugin->name.' could not be updated, version '.$from.' was restored: '.$exception->getMessage()];
            }
        } catch (\Throwable $exception) {
            report($exception);
            $result = ['ok' => false, 'message' => $plugin->name.' could not be updated: '.$exception->getMessage()];
        } finally {
            $lock->release();
            try {
                Plugin::refreshRows();
                cache()->forget('plugins.'.$id.'.update');
                cache()->forget('simplify.plugin-update-queued.'.$id);
                cache()->forget('simplify.plugin-latest.'.$id);
            } catch (\Throwable) {}
        }
        self::notify($result, $notifyUserId);
        return $result;
    }

    private static function download(Plugin $plugin, string $url): void
    {
        $service = app(\App\Services\Helpers\PluginService::class);
        $expected = self::publishedChecksum($plugin);
        if ($expected === null) {
            $service->downloadPluginFromUrl($url, $plugin->id);
            return;
        }
        $body = Http::timeout(60)->connectTimeout(5)->throw()->get($url)->body();
        if (!hash_equals($expected, hash('sha256', $body))) throw new \RuntimeException('The download does not match the published checksum.');
        $temporary = tempnam(sys_get_temp_dir(), 'simplify-plugin-').'.zip';
        try {
            file_put_contents($temporary, $body);
            $service->downloadPluginFromFile(new \Illuminate\Http\UploadedFile($temporary, basename($url), 'application/zip', null, true), $plugin->id);
        } finally {
            @unlink($temporary);
        }
    }

    /** The sha256 an update file publishes for this panel version (Simplify does), if any. */
    private static function publishedChecksum(Plugin $plugin): ?string
    {
        try {
            $data = Http::timeout(5)->connectTimeout(2)->get((string) $plugin->update_url)->throw()->json();
            $data = is_array($data[$plugin->id] ?? null) ? $data[$plugin->id] : $data;
            $entry = $data[config('app.version')] ?? $data['*'] ?? null;
            $sha = is_array($entry) ? ($entry['sha256'] ?? null) : null;
            return is_string($sha) && preg_match('/^[a-f0-9]{64}$/', $sha) ? $sha : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function restore(string $directory, string $backup): void
    {
        if (!File::isDirectory($backup)) return;
        File::deleteDirectory($directory);
        File::copyDirectory($backup, $directory);
        try {
            Process::path(base_path())->timeout(120)->run([PHP_BINARY, 'artisan', 'view:clear']);
        } catch (\Throwable) {}
    }

    private static function output(\Illuminate\Contracts\Process\ProcessResult $result): string
    {
        return trim(substr(trim($result->errorOutput()."\n".$result->output()), -600));
    }

    /** Result in the Simplify bell (Pelican database notification) for the starter or all root admins. */
    /** Tells root admins once per version that an update is available (and whether it is being installed). */
    public static function announce(Plugin $plugin, bool $installing): void
    {
        $latest = self::latestVersion($plugin);
        if (!$latest || !\Illuminate\Support\Facades\Cache::add('simplify.plugin-announced.'.$plugin->id.'.'.$latest, true, now()->addDays(60))) return;
        try {
            $notification = \Filament\Notifications\Notification::make()->info()
                ->title($plugin->name.' '.$latest.' is available')
                ->body($installing ? 'Installing it now with a backup and automatic rollback. You get another notification when it is done.' : 'You are running '.$plugin->version.'. Install it from the Plugins page.')
                ->actions([\Filament\Actions\Action::make('open')->label('Open plugins')->url(\Simplify\Panel\Filament\Admin\Pages\ManagePlugins::getUrl(panel: 'admin'))]);
            foreach (\App\Models\User::query()->get()->filter(fn ($user) => $user->isRootAdmin()) as $user) $user->notifyNow($notification->toDatabase());
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private static function notify(array $result, ?int $userId): void
    {
        try {
            $users = $userId ? \App\Models\User::query()->whereKey($userId)->get() : \App\Models\User::query()->get()->filter(fn ($user) => $user->isRootAdmin());
            $notification = \Filament\Notifications\Notification::make()->title($result['ok'] ? 'Plugin updated' : 'Plugin update failed')->body($result['message']);
            $result['ok'] ? $notification->success() : $notification->danger();
            // Delivered right away: the result must arrive even when the queue worker is busy or down.
            foreach ($users as $user) $user->notifyNow($notification->toDatabase());
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
