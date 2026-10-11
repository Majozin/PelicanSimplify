<?php
namespace Simplify\Panel\Services;

final class ModpackSnapshot
{
    public static function fromState(array $state, array $environment = []): array
    {
        if (($state['server_type'] ?? '') !== 'modded') { return []; }
        $key = (string) ($state['modpack_project'] ?? '');
        $parts = ModpackCatalog::split($key);
        if (!$parts) { return []; }
        [$provider, $project] = $parts;
        $project = (string) ($environment[$provider === 'ftb' ? 'FTB_MODPACK_ID' : 'PROJECT_ID'] ?? $project);
        $key = $provider . ':' . $project;
        $pack = ModpackCatalog::pack($key);
        if ($provider === 'ftb') {
            $raw = ModpackCatalog::ftbData($project);
            if ((string) ($raw['id'] ?? '') === $project && !empty($raw['name'])) {
                $pack = ModpackCatalog::card('ftb', $project, $raw);
            }
        }
        if (!$pack) { return []; }
        $release = (string) ($environment[$provider === 'ftb' ? 'FTB_MODPACK_VERSION_ID' : 'VERSION_ID'] ?? $state['modpack_version'] ?? '');
        $versions = ModpackCatalog::versions($key);
        if ($provider === 'ftb' && !isset($versions[$release])) {
            foreach ($raw['versions'] ?? [] as $version) {
                if ((string) ($version['id'] ?? '') === $release && empty($version['private']) && ($version['type'] ?? '') !== 'archived') {
                    $versions[$release] = (string) ($version['name'] ?? $release);
                    break;
                }
            }
        }
        $url = (string) ($pack['url'] ?? '');
        $provider = (string) ($pack['provider'] ?? '');
        return [
            'provider' => $provider, 'project_id' => (string) ($pack['id'] ?? ''),
            'version_id' => $release,
            'modpack_name' => (string) ($pack['title'] ?? ''),
            'modpack_version' => $provider === 'technic' ? (string) ($environment['MODPACK_VERSION'] ?? $pack['default_version'] ?? '') : (string) ($versions[$release] ?? ''),
            'modpack_description' => (string) ($pack['description'] ?? ''),
            'modpack_icon_url' => (string) ($pack['icon_url'] ?? ''),
            'modpack_url' => $url,
            'client_url' => $url . ($provider === 'modrinth' && $release !== '' ? '/version/' . rawurlencode($release) : ''),
        ];
    }
}
