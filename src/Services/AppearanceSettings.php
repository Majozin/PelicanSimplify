<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
final class AppearanceSettings
{
    public const FILE='simplify/appearance.json';
    private ?array $loaded=null;
    public static function defaults(): array { return ['accent_color'=>'#878787','individual_colors'=>true]; }
    public static function all(): array { return app(self::class)->load(); }
    private function load(): array
    {
        if($this->loaded!==null)return $this->loaded;
        $disk=Storage::disk('local');
        try{$saved=$disk->exists(self::FILE)?json_decode($disk->get(self::FILE),true,16,JSON_THROW_ON_ERROR):[];}
        catch(\Throwable $e){report($e);$saved=[];}
        $saved=is_array($saved)?$saved:[];
        return $this->loaded=['accent_color'=>is_string($saved['accent_color']??null)&&preg_match('/^#[0-9a-fA-F]{6}$/',$saved['accent_color'])?strtolower($saved['accent_color']):self::defaults()['accent_color'],'individual_colors'=>array_key_exists('individual_colors',$saved)?$saved['individual_colors']===true:true];
    }
    public static function save(array $input): void
    {
        abort_unless(user()?->isRootAdmin(),403);
        $data=validator($input,['accent_color'=>'required|regex:/^#[0-9a-fA-F]{6}$/','individual_colors'=>'required|boolean'])->validate();
        $next=['accent_color'=>strtolower($data['accent_color']),'individual_colors'=>(bool)$data['individual_colors']];
        Cache::lock('simplify.appearance',10)->block(3,function()use($next):void{
            if(!Storage::disk('local')->put(self::FILE,json_encode($next,JSON_THROW_ON_ERROR)))throw new \RuntimeException('Could not save appearance settings.');
            app(self::class)->loaded=$next;
        });
    }
}
