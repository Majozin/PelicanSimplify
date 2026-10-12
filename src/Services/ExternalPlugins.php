<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Other installed Pelican plugins, as Simplify sees them. A page belongs to a plugin when its class lives in
 * that plugin's namespace (from its plugin.json), so plugin pages can be shown under "External". Plugins
 * known to clash with Simplify are listed in conflicts.json (published next to update.json, so the list can
 * grow without a release); while one is enabled, Simplify's dashboard switches itself off.
 */
final class ExternalPlugins
{
    private const SELF = 'pelican-simplify';
    private static ?array $plugins = null;

    /** Enabled plugins other than Simplify: id => [name, namespace]. */
    public static function plugins(): array
    {
        if (self::$plugins !== null) return self::$plugins;
        $list = [];
        try {
            foreach (\App\Models\Plugin::all() as $plugin) {
                $status = is_object($plugin->status) ? $plugin->status->value : (string) $plugin->status;
                if ($plugin->id === self::SELF || $status !== 'enabled' || !is_string($plugin->namespace) || $plugin->namespace === '') continue;
                $list[$plugin->id] = ['id' => $plugin->id, 'name' => (string) ($plugin->name ?: $plugin->id), 'namespace' => trim($plugin->namespace, '\\').'\\'];
            }
        } catch (\Throwable) {
        }
        return self::$plugins = $list;
    }

    /** The plugin a page or resource class comes from, or null for Pelican's own pages. */
    public static function owner(string $class): ?array
    {
        $class = ltrim($class, '\\');
        foreach (self::plugins() as $plugin) if (str_starts_with($class, $plugin['namespace'])) return $plugin;
        return null;
    }

    /** Known conflicts: [{id?, name?, reason}] from the published list, with the bundled copy as fallback. */
    public static function conflictList(): array
    {
        return Cache::remember('simplify.conflicts', 3600, function (): array {
            $bundled = json_decode((string) @file_get_contents(dirname(__DIR__, 2).'/conflicts.json'), true);
            $list = is_array($bundled['plugins'] ?? null) ? $bundled['plugins'] : [];
            try {
                $url = 'https://raw.githubusercontent.com/jerajerajerajera/PelicanSimplify/main/conflicts.json';
                $remote = Http::timeout(4)->connectTimeout(2)->get($url)->throw()->json();
                if (is_array($remote['plugins'] ?? null)) $list = $remote['plugins'];
            } catch (\Throwable) {
            }
            return array_values(array_filter($list, 'is_array'));
        });
    }

    /** Enabled plugins that clash with Simplify, with the reason: [{id, name, reason}]. */
    public static function conflicts(): array
    {
        $found = [];
        foreach (self::plugins() as $plugin) {
            foreach (self::conflictList() as $entry) {
                $byId = isset($entry['id']) && strcasecmp((string) $entry['id'], $plugin['id']) === 0;
                $byName = isset($entry['name']) && strcasecmp(trim((string) $entry['name']), trim($plugin['name'])) === 0;
                if ($byId || $byName) {
                    $found[] = ['id' => $plugin['id'], 'name' => $plugin['name'], 'reason' => (string) ($entry['reason'] ?? 'It changes the same parts of the panel as Simplify.')];
                    break;
                }
            }
        }
        return $found;
    }
}
