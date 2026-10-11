<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
/**
 * Compares the installed version with the published update.json (the same file Pelican's
 * plugin updater reads). Checked at most twice a day; installing stays with Pelican.
 */
final class UpdateCheck
{
    public static function status(): ?array
    {
        $current = BuildInfo::version();
        $manifest = self::manifest();
        $url = is_string($manifest['update_url'] ?? null) ? $manifest['update_url'] : null;
        if (!$current || !$url || !str_starts_with($url, 'https://')) return null;
        $published = Remember::value('simplify.update-check.'.hash('sha256', $url), 43200, function () use ($url): ?array {
            $response = Http::timeout(3)->connectTimeout(2)->acceptJson()->get($url);
            return $response->successful() && is_array($response->json()) ? $response->json() : null;
        }, 3600);
        $entry = is_array($published) ? ($published[$manifest['panel_version'] ?? ''] ?? null) : null;
        $latest = is_array($entry) && is_string($entry['version'] ?? null) ? $entry['version'] : null;
        if (!$latest || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $latest)) return null;
        return [
            'current' => $current,
            'version' => $latest,
            'available' => version_compare($latest, $current, '>'),
            'url' => Route::has('filament.admin.resources.plugins.index') ? route('filament.admin.resources.plugins.index') : null,
        ];
    }

    private static function manifest(): array
    {
        $path = plugin_path('pelican-simplify', 'plugin.json');
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
        return is_array($data) ? $data : [];
    }
}
