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
        if (in_array($asset, BuildInfo::FONTS, true)) return $this->font($request, $asset);
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
        if (str_ends_with($asset, '.css')) $content = \Simplify\Panel\Services\InterfaceScale::css($content);
        return response($content, 200, $headers);
    }

    /** Fonts never change under the same name, so they are cached for a year. */
    private function font(Request $request, string $asset): mixed
    {
        $path = plugin_path('pelican-simplify', 'resources/fonts/'.$asset);
        abort_unless(is_file($path), 404);
        $etag = '"'.substr(hash_file('sha256', $path), 0, 16).'"';
        $headers = ['Content-Type' => 'font/woff2', 'Cache-Control' => 'public, max-age=31536000, immutable', 'ETag' => $etag, 'X-Content-Type-Options' => 'nosniff'];
        if (trim((string) $request->header('If-None-Match')) === $etag) return response('', 304, $headers);
        return response((string) file_get_contents($path), 200, $headers);
    }
}
