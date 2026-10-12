<?php
declare(strict_types=1);
namespace Simplify\Panel\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Simplify\Panel\Services\AppearanceSettings;
use Simplify\Panel\Services\BrandSettings;

final class LogoController
{
    public function __invoke()
    {
        $logo = AppearanceSettings::all()['brand']['logo'] ?? null;
        abort_unless(is_string($logo), 404);
        $ext = pathinfo($logo, PATHINFO_EXTENSION);
        return response(Storage::disk('local')->get(BrandSettings::LOGO_DIR.$logo), 200, [
            'Content-Type' => BrandSettings::LOGO_TYPES[$ext] ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
            'Cache-Control' => request()->query('v') ? 'private, max-age=31536000, immutable' : 'private, max-age=300',
        ]);
    }
}
