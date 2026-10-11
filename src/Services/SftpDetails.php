<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Server;
use App\Enums\SubuserPermission;
final class SftpDetails
{
    public static function forServer(Server $server): ?array
    {
        if(!user()?->can(SubuserPermission::FileSftp,$server))return null;
        return ['url'=>$server->getSftpUrl(),'username'=>user()->username.'.'.$server->uuid_short];
    }
}
