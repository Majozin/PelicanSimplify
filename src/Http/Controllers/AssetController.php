<?php
declare(strict_types=1);
namespace Simplify\Panel\Http\Controllers;
final class AssetController
{
    public function __invoke(string $asset): mixed
    {
        $files=['global.css'=>['creator.css','global.css'],'dashboard.css'=>['dashboard.css'],'theme.js'=>['theme.js'],'dashboard.js'=>['dashboard.js'],'setup-progress.js'=>['setup-progress.js']];
        abort_unless(isset($files[$asset]),404);
        $content='';foreach($files[$asset] as $file)$content.=file_get_contents(plugin_path('pelican-simplify','resources/views/'.$file))."\n";
        return response($content)->header('Content-Type',str_ends_with($asset,'.css')?'text/css; charset=utf-8':'application/javascript; charset=utf-8')
            ->header('Cache-Control','public, max-age=86400')->header('X-Content-Type-Options','nosniff');
    }
}
