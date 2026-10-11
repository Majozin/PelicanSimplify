<?php
declare(strict_types=1);
namespace Simplify\Panel\Http\Controllers;
use App\Models\Server;
use Simplify\Panel\Services\{ServerBanners,Access,FeatureSettings};
final class BannerController
{
    public function __invoke(string $id)
    {
        abort_unless(user(),403);
        $data=ServerBanners::metadata($id);abort_unless($data,404);
        $server=Server::query()->where('uuid',$data['server_uuid'])->first();
        abort_unless($server && (Access::linked($server)||FeatureSettings::enabled('friends_mode')),403);
        return response()->file(ServerBanners::path($id),['Cache-Control'=>'private, max-age=300','X-Content-Type-Options'=>'nosniff']);
    }
}
