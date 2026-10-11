<?php

declare(strict_types=1);

namespace Simplify\Panel\Filament\App\Pages;

/** Same wizard and creation guard in the authenticated client panel. */
final class CreateServer extends \Simplify\Panel\Filament\Admin\Pages\CreateMinecraftServer
{
    protected static bool $shouldRegisterNavigation = false;
}
