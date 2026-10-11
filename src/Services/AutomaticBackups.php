<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use App\Models\Server;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
final class AutomaticBackups
{
    private static function path(Server $s): string { return 'simplify/backups/'.$s->uuid.'.json'; }
    public static function settings(Server $s): array
    {
        $raw=Storage::disk('local')->get(self::path($s));
        return array_replace(['enabled'=>true,'interval_minutes'=>30,'retention'=>2,'last_attempt'=>0],$raw?json_decode($raw,true,512,JSON_THROW_ON_ERROR):[]);
    }
    public static function save(Server $s,array $input): void
    {
        abort_unless(user()?->isRootAdmin(),403);
        $data=validator($input,['enabled'=>'required|boolean','interval_minutes'=>'required|integer|min:30|max:10080','retention'=>'required|integer|min:1|max:100'])->validate();
        Storage::disk('local')->put(self::path($s),json_encode(array_replace(self::settings($s),$data),JSON_THROW_ON_ERROR));
    }
    public static function tick(Server $s): void
    {
        if (!$s->installed_at || $s->status!==null) return;
        Cache::lock('simplify.backups.'.$s->uuid,60)->block(1,function()use($s):void {
            $options=self::settings($s);if (!$options['enabled']) return;
            $finished=$s->backups()->where('is_successful',true)->whereNotNull('completed_at')->orderByDesc('created_at')->get();
            foreach($finished->slice((int)$options['retention']) as $old) {
                if (!$old->is_locked) app(\App\Services\Backups\DeleteBackupService::class)->handle($old);
            }
            if ($s->backups()->whereNull('completed_at')->exists()) return;
            if (!$s->node->backupHosts()->exists() && !\App\Models\BackupHost::doesntHave('nodes')->exists()) return;
            if ($s->backups()->where('is_locked',true)->where('is_successful',true)->count()>=(int)$options['retention']) return;
            $latest=$s->backups()->where('is_successful',true)->max('created_at');
            $last=max((int)$options['last_attempt'],$latest?strtotime($latest):0);
            if (time()-$last<(int)$options['interval_minutes']*60) return;
            $options['last_attempt']=time();Storage::disk('local')->put(self::path($s),json_encode($options,JSON_THROW_ON_ERROR));
            // Reserve one temporary slot so a failed backup never destroys either
            // existing recovery point. Prune only completed successful backups.
            $backupServer=clone $s;$backupServer->backup_limit=$s->backups()->nonFailed()->count()+1;
            app(\App\Services\Backups\InitiateBackupService::class)->setIsScheduled(true)->setIsLocked(false)->handle($backupServer,'Automatic · '.now()->format('Y-m-d H:i'));
        });
    }
}
