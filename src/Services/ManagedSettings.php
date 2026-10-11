<?php

declare(strict_types=1);

namespace Simplify\Panel\Services;

use App\Enums\SubuserPermission;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use App\Repositories\Daemon\DaemonServerRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class ManagedSettings
{
    public static function owns(Server $server): bool
    {
        return (bool) user() && ((int) $server->owner_id === (int) user()->id || user()->isRootAdmin());
    }
    public static function minecraft(Server $server): bool
    {
        return preg_match('/minecraft|vanilla|paper|purpur|spigot|modrinth|curseforge|ftb|tekkit|hexxit|blightfall|b-team|forge|fabric|pack/i', (string) $server->egg?->name) === 1;
    }
    public static function content(Server $server): array
    {
        $data=ServerMetadata::read($server);
        return is_array($data['modpack']??null)?$data['modpack']:[];
    }
}
