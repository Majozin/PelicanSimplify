<?php
declare(strict_types=1);
namespace Simplify\Panel\Http\Controllers;
final class FaviconController
{
    public function __invoke()
    {
        return response()->file(plugin_path('pelican-simplify','resources/branding/favicon.svg'),[
            'Content-Type'=>'image/svg+xml','Cache-Control'=>'public, max-age=86400',
        ]);
    }
}
