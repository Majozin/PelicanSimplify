<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
final class BuildInfo
{
    public static function version(): ?string
    {
        $path = plugin_path('pelican-simplify', 'plugin.json');
        $manifest = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
        $version = is_array($manifest) ? ($manifest['version'] ?? null) : null;
        return is_string($version) && preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) ? $version : null;
    }
}
