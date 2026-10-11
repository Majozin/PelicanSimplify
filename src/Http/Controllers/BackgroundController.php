<?php
declare(strict_types=1);
namespace Simplify\Panel\Http\Controllers;
use Simplify\Panel\Services\ServerArtwork;
final class BackgroundController
{
    public function __invoke(string $id)
    {
        $name=ServerArtwork::BACKGROUNDS[$id]??null;abort_unless($name,404);
        $path=plugin_path('pelican-simplify','resources/backgrounds/'.$name);
        abort_unless(is_file($path),404);
        // Bundled artwork only changes with a plugin update; URLs carry the build hash.
        return response()->file($path,['Cache-Control'=>request()->query('v')?'public, max-age=31536000, immutable':'public, max-age=86400']);
    }
}
