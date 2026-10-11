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
    public static function canEdit(Server $server): bool
    {
        return self::owns($server) || (user()?->can(SubuserPermission::FileReadContent, $server)
            && user()?->can(SubuserPermission::FileUpdate, $server));
    }
    public static function minecraft(Server $server): bool
    {
        return preg_match('/minecraft|vanilla|paper|purpur|spigot|modrinth|curseforge|ftb|tekkit|hexxit|blightfall|b-team|forge|fabric|pack/i', (string) $server->egg?->name) === 1;
    }
    public static function saveMinecraft(Server $server, array $state, string $expectedHash): void
    {
        abort_unless(self::canEdit($server) && self::minecraft($server), 403);
        if (!empty($state['server_icon'])) {
            abort_unless(self::owns($server) || user()?->can(SubuserPermission::FileCreate, $server), 403);
        }
        // FileUpload keeps a single file in an array before form dehydration.
        if (is_array($state['server_icon'] ?? null)) {
            $state['server_icon'] = array_values($state['server_icon'])[0] ?? null;
        }
        $state['properties_enabled'] = true;
        $prepared = ServerProperties::prepare($state, false);
        Cache::lock('ssc.manage.' . $server->uuid, 30)->block(5, function () use ($server, $prepared, $expectedHash): void {
            $server->refresh();
            abort_unless(self::canEdit($server), 403);
            if (!$server->installed_at || $server->status !== null) {
                throw ValidationException::withMessages(['data.motd' => 'Wait until installation has finished.']);
            }
            $daemon = app(DaemonServerRepository::class)->setServer($server);
            if (($daemon->getDetails()['state'] ?? '') !== 'offline') {
                throw ValidationException::withMessages(['data.motd' => 'Stop the server before saving Minecraft settings, then start it again.']);
            }
            $files = app(DaemonFileRepository::class)->setServer($server);
            $original = $files->getContent('/server.properties', 1048576);
            if (!hash_equals(hash('sha256', $original), $expectedHash)) {
                throw ValidationException::withMessages(['data.motd' => 'The settings changed since you opened this page. Reload it before saving.']);
            }
            $values = $prepared['properties'];
            $old = PropertiesReader::parse($original);
            foreach (['difficulty' => ['peaceful','easy','normal','hard'], 'gamemode' => ['survival','creative','adventure','spectator']] as $key => $options) {
                if (isset($old[$key]) && preg_match('/^[0-3]$/', $old[$key])) {
                    $values[$key] = (string) array_search($values[$key], $options, true);
                }
            }
            $disk = Storage::disk('local');
            $backup = 'ssc/settings-backups/' . $server->uuid . '/' . (string) \Illuminate\Support\Str::uuid() . '.properties';
            if (!$disk->put($backup, $original)) { throw new \RuntimeException('Could not back up server.properties.'); }
            $files->putContent('/server.properties', ServerProperties::merge($original, $values))->throw();
            if (!empty($prepared['icon'])) {
                $files->putContent('/server-icon.png', base64_decode($prepared['icon'], true))->throw();
            }
        });
    }
    public static function content(Server $server): array
    {
        $data=ServerMetadata::read($server);
        return is_array($data['modpack']??null)?$data['modpack']:[];
    }
}
