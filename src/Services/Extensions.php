<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
final class Extensions
{
    private array $states=[];
    public static function enabled(string $id): bool
    {
        $self=app(self::class);
        if(array_key_exists($id,$self->states))return $self->states[$id];
        $path=base_path('plugins/'.$id.'/plugin.json');
        if(!is_file($path))return $self->states[$id]=false;
        $manifest=json_decode(file_get_contents($path),true);
        return $self->states[$id]=($manifest['id']??'')===$id&&($manifest['meta']['status']??'')==='enabled';
    }
    public static function webInstalled(): bool { return self::enabled('server-directory')&&app()->bound('simplify.web'); }
    public static function web(): bool { return self::webInstalled()&&app('simplify.web')->enabled(); }
    public static function webAdapter(): ?object { return self::web()?app('simplify.web'):null; }
    public static function createGames(): array
    {
        if(!self::canCreate())return [];
        $easy=FeatureSettings::enabled('easy_creator');$games=FeatureSettings::enabled('game_creator');
        if(!$easy&&!$games)return ['native'];
        $minecraft=$easy&&(CreatorAccess::admin()||CreatorAccess::settings()['minecraft_enabled']);
        // "Other game" opens the game creator, or Pelican's own form for administrators when that add-on is off.
        $other=$games||CreatorAccess::admin();
        return array_values(array_filter([$minecraft?'minecraft':null,$other?'other':null]));
    }
    public static function createUrl(): string
    {
        return FeatureSettings::enabled('easy_creator')||FeatureSettings::enabled('game_creator')
            ?\Simplify\Panel\Filament\App\Pages\CreateServer::getUrl(panel:'app')
            :\App\Filament\Admin\Resources\Servers\ServerResource::getUrl('create',panel:'admin');
    }
    public static function canCreate(): bool
    {
        return FeatureSettings::enabled('easy_creator')||FeatureSettings::enabled('game_creator')?CreatorAccess::canCreate():\App\Filament\Admin\Resources\Servers\ServerResource::canCreate();
    }
}
