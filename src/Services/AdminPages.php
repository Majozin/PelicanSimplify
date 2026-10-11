<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;

/**
 * Pelican's admin pages that Simplify does not replace, grouped for the dashboard's admin menu. Read from the
 * admin panel itself, so plugin pages appear too and each item honours that page's own access check.
 */
final class AdminPages
{
    /** Basename => [group, icon]; anything else lands in "Other". */
    private const KNOWN = [
        'ServerResource' => ['Game servers', 'host'], 'EggResource' => ['Game servers', 'egg'], 'MountResource' => ['Game servers', 'files'],
        'NodeResource' => ['Infrastructure', 'monitor'], 'DatabaseHostResource' => ['Infrastructure', 'database'], 'BackupHostResource' => ['Infrastructure', 'backups'],
        'UserResource' => ['People & access', 'players'], 'RoleResource' => ['People & access', 'lock'], 'ApiKeyResource' => ['People & access', 'key'],
        'PluginResource' => ['Panel', 'plugins'], 'Settings' => ['Panel', 'gear'], 'WebhookResource' => ['Panel', 'link'], 'Health' => ['Panel', 'heart'], 'ListLogs' => ['Panel', 'activity'],
    ];
    private const GROUPS = ['Game servers', 'Infrastructure', 'People & access', 'Panel', 'Other'];
    /** Pages Simplify replaces or that need parameters. */
    private const SKIP = ['Dashboard', 'ViewLogs', 'SimplifySettings', 'CreateMinecraftServer'];

    public static function menu(): array
    {
        if (!user()?->isAdmin()) return [];
        try {
            $panel = \Filament\Facades\Filament::getPanel('admin');
        } catch (\Throwable) {
            return [];
        }
        $groups = array_fill_keys(self::GROUPS, []);
        foreach ([...$panel->getResources(), ...$panel->getPages()] as $class) {
            $name = class_basename($class);
            if (in_array($name, self::SKIP, true)) continue;
            try {
                $isResource = is_subclass_of($class, \Filament\Resources\Resource::class);
                if ($isResource ? !$class::canViewAny() : !$class::canAccess()) continue;
                if (!$isResource && method_exists($class, 'shouldRegisterNavigation') && !$class::shouldRegisterNavigation() && !isset(self::KNOWN[$name])) continue;
                $url = $isResource ? $class::getUrl('index', panel: 'admin') : $class::getUrl(panel: 'admin');
                $label = (string) $class::getNavigationLabel();
            } catch (\Throwable) {
                continue;
            }
            [$group, $icon] = self::KNOWN[$name] ?? ['Other', 'legacy'];
            $groups[$group][] = ['key' => strtolower($name), 'label' => $label, 'url' => $url, 'icon' => $icon];
        }
        $menu = [];
        foreach ($groups as $label => $items) {
            if (!$items) continue;
            usort($items, fn (array $a, array $b): int => strnatcasecmp($a['label'], $b['label']));
            $menu[] = ['label' => $label, 'items' => $items];
        }
        return $menu;
    }
}
