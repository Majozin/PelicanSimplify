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
        return response()->file($path,['Cache-Control'=>'public, max-age=300']);
    }
}
