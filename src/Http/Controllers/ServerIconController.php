<?php
declare(strict_types=1);
namespace Simplify\Panel\Http\Controllers;
use App\Models\Server;
use Illuminate\Http\Request;
use Simplify\Panel\Services\Access;
use Simplify\Panel\Services\ServerArtwork;
/** The public server icon for every server the user can see on the overview. Versioned URLs cache for a year. */
final class ServerIconController
{
    public function __invoke(Request $request, string $uuid): mixed
    {
        abort_unless(user() && preg_match('/^[a-f0-9-]{36}$/', $uuid), 404);
        $server = Access::overviewQuery()->where('uuid', $uuid)->first();
        abort_unless($server instanceof Server, 404);
        $bytes = ServerArtwork::iconBytes($server);
        abort_unless($bytes !== null, 404);
        $etag = '"'.substr(hash('sha256', $bytes), 0, 12).'"';
        $headers = ['Content-Type' => 'image/png', 'Cache-Control' => 'private, max-age=31536000, immutable', 'ETag' => $etag, 'X-Content-Type-Options' => 'nosniff'];
        if (trim((string) $request->header('If-None-Match')) === $etag) return response('', 304, $headers);
        return response($bytes, 200, $headers);
    }
}
