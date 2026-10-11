<?php

declare(strict_types=1);

namespace Simplify\Panel\Filament\Admin\Pages;

use Simplify\Panel\Services\CreatorAccess;

final class CreateServerLegacy extends \App\Filament\Admin\Resources\Servers\Pages\CreateServer
{
    public function getTitle(): string
    {
        return 'Create server (legacy)';
    }

    protected function authorizeAccess(): void
    {
        abort_unless(CreatorAccess::admin(), 403);
        parent::authorizeAccess();
    }
}
