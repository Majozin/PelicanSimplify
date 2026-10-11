<?php

declare(strict_types=1);

namespace Simplify\Panel\Services;

use App\Models\Egg;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class MinecraftCatalog
{
    public static function eggForType(string $type): ?Egg
    {
        $needles = match ($type) {
            'vanilla' => ['vanilla minecraft', 'minecraft vanilla', 'vanilla'],
            'plus' => ['paper'],
            'modded' => ['modrinth generic', 'modrinth'],
            default => [],
        };

        foreach ($needles as $needle) {
            $egg = Egg::query()->whereRaw('LOWER(name) = ?', [$needle])->first();
            if ($egg) {
                return $egg;
            }
        }

        return null;
    }

    /** @return array<string,string> */
    public static function versions(): array
    {
        try {
            return Cache::remember('ssc.minecraft.releases', 21600, function (): array {
                $response = Http::timeout(6)->get('https://piston-meta.mojang.com/mc/game/version_manifest_v2.json');
                if (!$response->successful()) {
                    return self::fallbackVersions();
                }

                $releases = collect($response->json('versions', []))
                    ->filter(fn ($item) => ($item['type'] ?? '') === 'release')
                    ->take(70)
                    ->pluck('id', 'id')
                    ->all();

                return $releases ?: self::fallbackVersions();
            });
        } catch (Throwable) {
            return self::fallbackVersions();
        }
    }

    /** @return array<string,string> */
    private static function fallbackVersions(): array
    {
        return array_combine(
            ['1.21.3', '1.21.1', '1.20.6', '1.20.4', '1.20.1', '1.19.4', '1.18.2', '1.16.5'],
            ['1.21.3', '1.21.1', '1.20.6', '1.20.4', '1.20.1', '1.19.4', '1.18.2', '1.16.5']
        );
    }

    /**
     * Popular Modrinth modpacks with short descriptions for the SSC discovery cards.
     * Returned project IDs are validated again when a server is created.
     *
     * @return array<int,array{project_id:string,title:string,description:string,icon_url:?string,slug:string}>
     */
    public static function safeIconUrl(mixed $url): ?string
    {
        if (!is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'cdn.modrinth.com') {
            return null;
        }
        return $url;
    }

    public static function featuredModpacks(): array
    {
        try {
            return Cache::remember('ssc.modrinth.featured.v2', 1800, function (): array {
                $response = Http::acceptJson()->timeout(8)
                    ->withHeaders(['User-Agent' => 'PelicanSimplify/0.2.0 (Pelican plugin)'])
                    ->get('https://api.modrinth.com/v2/search', [
                        'query' => '',
                        'index' => 'downloads',
                        'limit' => 3,
                        'facets' => '[["project_type:modpack"]]',
                    ]);

                if (!$response->successful()) {
                    return [];
                }

                return collect($response->json('hits', []))
                    ->filter(fn ($hit) => !empty($hit['project_id']) && !empty($hit['title']))
                    ->take(3)
                    ->map(fn ($hit) => [
                        'project_id' => (string) $hit['project_id'],
                        'icon_url' => self::safeIconUrl($hit['icon_url'] ?? null),
                        'slug' => preg_match('/^[a-zA-Z0-9_-]{1,128}$/', (string) ($hit['slug'] ?? '')) ? (string) $hit['slug'] : (string) $hit['project_id'],
                        'title' => mb_substr((string) $hit['title'], 0, 60),
                        'description' => mb_substr(trim((string) ($hit['description'] ?? 'Minecraft modpack')), 0, 170),
                    ])
                    ->values()->all();
            });
        } catch (Throwable) {
            // If Modrinth is unavailable, normal manual search remains usable.
            return [];
        }
    }

    /** @return array<string,string> */
    public static function searchModrinth(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        try {
            $response = Http::acceptJson()->timeout(8)
                ->withHeaders(['User-Agent' => 'PelicanSimplify/0.2.0 (Pelican plugin)'])
                ->get('https://api.modrinth.com/v2/search', [
                    'query' => mb_substr($search, 0, 80),
                    'limit' => 15,
                    'facets' => '[["project_type:modpack"]]',
                ]);

            if (!$response->successful()) {
                return [];
            }

            return collect($response->json('hits', []))
                ->filter(fn ($hit) => !empty($hit['project_id']))
                ->mapWithKeys(fn ($hit) => [
                    (string) $hit['project_id'] => (string) ($hit['title'] ?? $hit['slug'] ?? $hit['project_id'])
                        . ' (' . ($hit['slug'] ?? $hit['project_id']) . ')',
                ])->all();
        } catch (Throwable) {
            return [];
        }
    }

    public static function modpackLabel(?string $projectId): ?string
    {
        if (!$projectId || !preg_match('/^[a-zA-Z0-9_-]{2,64}$/', $projectId)) {
            return null;
        }

        try {
            return Cache::remember('ssc.modrinth.label.' . $projectId, 3600, function () use ($projectId): string {
                $response = Http::timeout(6)->get('https://api.modrinth.com/v2/project/' . rawurlencode($projectId));
                return $response->successful() ? (string) ($response->json('title') ?? $projectId) : $projectId;
            });
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string,string> */
    public static function modpackVersions(?string $projectId): array
    {
        if (!$projectId || !preg_match('/^[a-zA-Z0-9_-]{2,64}$/', $projectId)) {
            return [];
        }

        try {
            $response = Http::acceptJson()->timeout(8)
                ->get('https://api.modrinth.com/v2/project/' . rawurlencode($projectId) . '/version');

            if (!$response->successful()) {
                return [];
            }

            return collect($response->json())
                ->take(60)
                ->filter(fn ($item) => !empty($item['id']))
                ->mapWithKeys(fn ($item) => [
                    (string) $item['id'] => (string) ($item['version_number'] ?? $item['name'] ?? $item['id'])
                        . ' · Minecraft ' . implode(', ', array_slice($item['game_versions'] ?? [], 0, 3)),
                ])->all();
        } catch (Throwable) {
            return [];
        }
    }

    public static function modpackVersionIsValid(string $projectId, string $versionId): bool
    {
        // Validate against the live project's listed versions, not just the user's form data.
        return array_key_exists($versionId, self::modpackVersions($projectId));
    }

    /** @return array<string,string> */
    public static function eggVariables(Egg $egg, string $type, ?string $minecraftVersion, ?string $projectId, ?string $versionId): array
    {
        $env = [];
        foreach ($egg->variables as $variable) {
            $env[(string) $variable->env_variable] = (string) ($variable->default_value ?? '');
        }

        if ($type === 'modded') {
            if (array_key_exists('PROJECT_ID', $env)) {
                $env['PROJECT_ID'] = (string) $projectId;
            }
            if (array_key_exists('VERSION_ID', $env)) {
                $env['VERSION_ID'] = (string) $versionId;
            }
        } else {
            foreach (['MINECRAFT_VERSION', 'VANILLA_VERSION', 'MC_VERSION'] as $key) {
                if (array_key_exists($key, $env)) {
                    $env[$key] = (string) $minecraftVersion;
                }
            }
        }

        return $env;
    }
}
