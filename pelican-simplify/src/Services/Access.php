<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Server;
use App\Enums\SubuserPermission;
final class Access
{
    public static function feature(string $key): bool
    {
        if(array_key_exists($key,FeatureSettings::definitions())&&!FeatureSettings::enabled($key))return false;
        if(user()?->isAdmin())return true;
        $userKey='user_'.$key;
        return !array_key_exists($userKey,FeatureSettings::definitions())||FeatureSettings::enabled($userKey);
    }
    public static function overviewQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query=Server::with(['allocation','egg','node'])->withExists(['subusers as simplify_assigned'=>fn($q)=>$q->where('user_id',user()->id)])->orderBy('name');
        if(!user()->isAdmin()&&!FeatureSettings::enabled('friends_mode')){
            $query->where(fn($q)=>$q->where('owner_id',user()->id)->orWhereHas('subusers',fn($sub)=>$sub->where('user_id',user()->id)));
        }
        return $query;
    }
    public static function linked(Server $s): bool
    {
        $u=user();
        return $u && ($u->isAdmin() || (int)$s->owner_id===(int)$u->id || $s->subusers()->where('user_id',$u->id)->exists());
    }
    public static function server(string $uuid): Server
    {
        $s=Server::with(['egg','node','allocation','variables'])->where('uuid',$uuid)->firstOrFail();
        abort_unless(self::linked($s),403);
        return $s;
    }
    public static function permission(Server $s, SubuserPermission $p): void { abort_unless(self::linked($s) && user()->can($p,$s),403); }
    public static function owner(Server $s): void { abort_unless(self::linked($s) && (user()->isRootAdmin() || (int)$s->owner_id===(int)user()->id),403); }
    public static function offline(Server $s): void
    {
        abort_unless($s->installed_at && $s->status===null,409,'Wait until installation or recovery has finished.');
        $state=app(\App\Repositories\Daemon\DaemonServerRepository::class)->setServer($s)->getDetails()['state']??'unknown';
        abort_unless($state==='offline',409,'Stop the server before performing this action.');
    }
}
