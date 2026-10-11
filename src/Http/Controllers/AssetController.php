<?php
declare(strict_types=1);
namespace Simplify\Panel\Http\Controllers;
use Illuminate\Http\Request;
use Simplify\Panel\Services\BuildInfo;
/** Serves the Simplify CSS/JS bundles. Versioned URLs are cached by browsers for a year. */
final class AssetController
{
    public function __invoke(Request $request, string $asset): mixed
    {
        $files = BuildInfo::files($asset);
        abort_unless($files !== null, 404);
        $hash = BuildInfo::hash($asset);
        $etag = '"'.$hash.'"';
        $headers = [
            'Content-Type' => str_ends_with($asset, '.css') ? 'text/css; charset=utf-8' : 'application/javascript; charset=utf-8',
            'Cache-Control' => $request->query('v') === $hash ? 'public, max-age=31536000, immutable' : 'public, max-age=300',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];
        if (trim((string) $request->header('If-None-Match')) === $etag) return response('', 304, $headers);
        $content = '';
        foreach ($files as $file) $content .= (string) file_get_contents($file)."\n";
        return response($content, 200, $headers);
    }
}
