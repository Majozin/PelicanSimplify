<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
final class AppearanceSettings
{
    public const FILE='simplify/appearance.json';
    private ?array $loaded=null;
    public static function defaults(): array { return ['accent_color'=>'#878787','individual_colors'=>true,'interface_scale'=>100,'layout_scale'=>0,'layout_scale_host'=>0,'brand'=>BrandSettings::defaults()]; }
    public static function all(): array { return app(self::class)->load(); }
    private function load(): array
    {
        if($this->loaded!==null)return $this->loaded;
        $disk=Storage::disk('local');
        try{$saved=$disk->exists(self::FILE)?json_decode($disk->get(self::FILE),true,16,JSON_THROW_ON_ERROR):[];}
        catch(\Throwable $e){report($e);$saved=[];}
        $saved=is_array($saved)?$saved:[];
        return $this->loaded=['accent_color'=>is_string($saved['accent_color']??null)&&preg_match('/^#[0-9a-fA-F]{6}$/',$saved['accent_color'])?strtolower($saved['accent_color']):self::defaults()['accent_color'],'individual_colors'=>array_key_exists('individual_colors',$saved)?$saved['individual_colors']===true:true,'interface_scale'=>in_array($saved['interface_scale']??null,InterfaceScale::OPTIONS,true)?$saved['interface_scale']:100,'layout_scale'=>in_array($saved['layout_scale']??null,InterfaceScale::LAYOUT_OPTIONS,true)?$saved['layout_scale']:0,'layout_scale_host'=>in_array($saved['layout_scale_host']??null,InterfaceScale::LAYOUT_OPTIONS,true)?$saved['layout_scale_host']:0,'brand'=>BrandSettings::normalize($saved['brand']??[])];
    }
    public static function save(array $input): void
    {
        abort_unless(user()?->isRootAdmin(),403);
        $data=validator($input,['accent_color'=>'required|regex:/^#[0-9a-fA-F]{6}$/','individual_colors'=>'required|boolean','interface_scale'=>'nullable|in:'.implode(',',InterfaceScale::OPTIONS),'layout_scale'=>'nullable|in:'.implode(',',InterfaceScale::LAYOUT_OPTIONS),'layout_scale_host'=>'nullable|in:'.implode(',',InterfaceScale::LAYOUT_OPTIONS)])->validate();
        $current=self::all()['brand']['logo']??null;
        $brand=isset($input['brand'])?validator((array)$input['brand'],BrandSettings::rules())->validate():self::all()['brand'];
        $next=['accent_color'=>strtolower($data['accent_color']),'individual_colors'=>(bool)$data['individual_colors'],'interface_scale'=>isset($data['interface_scale'])?(int)$data['interface_scale']:self::all()['interface_scale'],'layout_scale'=>isset($data['layout_scale'])?(int)$data['layout_scale']:self::all()['layout_scale'],'layout_scale_host'=>isset($data['layout_scale_host'])?(int)$data['layout_scale_host']:self::all()['layout_scale_host'],'brand'=>BrandSettings::normalize([...$brand,'logo'=>$current],$current)];
        Cache::lock('simplify.appearance',10)->block(3,function()use($next):void{
            if ($next['individual_colors']) ServerAppearance::assignMissingColors();
            if(!Storage::disk('local')->put(self::FILE,json_encode($next,JSON_THROW_ON_ERROR)))throw new \RuntimeException('Could not save appearance settings.');
            app(self::class)->loaded=$next;
        });
    }
    /** Replaces (or with null removes) the uploaded logo; the old file is deleted. */
    public static function setLogo(?string $name): void
    {
        abort_unless(user()?->isRootAdmin(),403);
        Cache::lock('simplify.appearance',10)->block(3,function()use($name):void{
            $all=self::all();
            $old=$all['brand']['logo'];
            $all['brand']=BrandSettings::normalize([...$all['brand'],'mode'=>$name?'logo':'text'],$name??'');
            if(!Storage::disk('local')->put(self::FILE,json_encode($all,JSON_THROW_ON_ERROR)))throw new \RuntimeException('Could not save appearance settings.');
            app(self::class)->loaded=$all;
            if($old!==$name)BrandSettings::deleteLogoFile($old);
        });
    }
}
