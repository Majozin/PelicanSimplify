<?php

declare(strict_types=1);

namespace Simplify\Panel\Filament\Profile;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Validation\ValidationException;
use Simplify\Panel\Services\SteamAccount;

/**
 * "Steam" tab on the profile page. Linking uses Pelican's own Steam sign-in (OAuth); creating game server
 * login tokens needs the account's Web API key, because Steam offers no permission flow for that.
 */
final class SteamProfileTab
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered || !class_exists(\App\Filament\Pages\Auth\EditProfile::class) || !enum_exists(\App\Enums\TabPosition::class)) return;
        self::$registered = true;
        \App\Filament\Pages\Auth\EditProfile::registerCustomTabs(\App\Enums\TabPosition::After, self::make());
    }

    public static function make(): Tab
    {
        return Tab::make('steam')->label('Steam')->icon('tabler-brand-steam')->schema(fn (): array => self::schema());
    }

    private static function steamSignInEnabled(): bool
    {
        try {
            foreach (app(\App\Extensions\OAuth\OAuthService::class)->getEnabled() as $schema) if ($schema->getId() === 'steam') return true;
        } catch (\Throwable) {
        }
        return false;
    }

    private static function schema(): array
    {
        $user = user();
        if ($user === null) return [];
        $status = SteamAccount::status($user);
        $linked = $status['linked'];
        $linkText = $linked !== null
            ? __('Linked to Steam account :account.', ['account' => $linked])
            : (self::steamSignInEnabled() ? __('Not linked yet. Use "Link Steam" on the OAuth tab.') : __('Steam sign-in is not enabled on this panel. An administrator can turn it on under Admin → Settings → OAuth.'));
        $keyText = $status['connected']
            ? ($status['steamid'] ? __('Steam Web API key ending in :hint (Steam account :account).', ['hint' => $status['hint'], 'account' => $status['steamid']]) : __('Steam Web API key ending in :hint.', ['hint' => $status['hint']])).' '.($status['auto'] ? __('Automatic tokens are on.') : __('Automatic tokens are off.'))
            : __('No key connected. Games that need a token show an empty field in the creator.');

        return [
            Section::make(__('Steam account'))->description(__('Linking proves which Steam account is yours. A key you connect below must belong to that same account.'))->schema([
                \Filament\Schemas\Components\Text::make($linkText),
            ]),
            Section::make(__('Game server login tokens'))
                ->description(__('Games such as CS2, Unturned and Insurgency: Sandstorm need a token per server. Steam only lets the account itself create them, through its Web API key, so connect your key once. Simplify stores it encrypted, uses it only for your own servers and never shows it again.'))
                ->schema([
                    \Filament\Schemas\Components\Text::make($keyText),
                    Actions::make([
                        Action::make('simplify_steam_connect')->button()->label($status['connected'] ? __('Replace key') : __('Connect key'))->icon('tabler-key')
                            ->modalHeading(__('Connect your Steam Web API key'))
                            ->modalDescription(__('Create or copy your key at steamcommunity.com/dev/apikey (any domain name works). Steam requires a phone-verified account to create tokens.'))
                            ->schema([TextInput::make('key')->label(__('Steam Web API key'))->password()->required()->maxLength(32)->autocomplete(false)])
                            ->modalSubmitActionLabel(__('Connect'))
                            ->action(function (array $data, Action $action): void {
                                try {
                                    SteamAccount::save(user(), (string) $data['key']);
                                    Notification::make()->title(__('Steam key connected'))->success()->send();
                                } catch (ValidationException $exception) {
                                    Notification::make()->title(__('Key not connected'))->body(collect($exception->errors())->flatten()->first())->danger()->send();
                                    $action->halt();
                                }
                            }),
                        Action::make('simplify_steam_auto')->button()->label($status['auto'] ? __('Turn automatic tokens off') : __('Turn automatic tokens on'))->icon('tabler-wand')
                            ->visible($status['connected'])
                            ->tooltip(__('Automatic: a new server for a game that needs a token gets one by itself when you leave the field empty.'))
                            ->action(function () use ($status): void {
                                SteamAccount::setAuto(user(), !$status['auto']);
                                Notification::make()->title($status['auto'] ? __('Automatic tokens off') : __('Automatic tokens on'))->success()->send();
                            }),
                        Action::make('simplify_steam_disconnect')->button()->label(__('Disconnect'))->icon('tabler-unlink')->color('danger')
                            ->visible($status['connected'])->requiresConfirmation()
                            ->modalDescription(__('Simplify forgets your key. Tokens already created keep working; manage them on steamcommunity.com/dev/managegameservers.'))
                            ->action(function (): void {
                                SteamAccount::remove(user());
                                Notification::make()->title(__('Steam key disconnected'))->success()->send();
                            }),
                    ]),
                ]),
        ];
    }
}
