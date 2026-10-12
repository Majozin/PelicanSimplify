<?php

declare(strict_types=1);

namespace Simplify\Panel\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * A person's own Steam Web API key, used to create Game Server Login Tokens (GSLT) for games that need
 * one (CS2, Unturned, Insurgency: Sandstorm…). The key is stored encrypted per user, is only ever used
 * for that user's own requests, and is never shown again after saving (only its last four characters).
 */
final class SteamAccount
{
    private const DIR = 'simplify/steam/';
    private const API = 'https://api.steampowered.com/IGameServersService/';

    /** Egg variables that take a Game Server Login Token. */
    public const TOKEN_VARIABLES = ['STEAM_ACC', 'GSLT', 'GSLTT', 'GSLT_TOKEN', 'STEAM_LOGIN_TOKEN', 'TOWER_LOGIN_TOKEN', 'STEAM_TOKEN', 'SRCDS_TOKEN', 'STEAM_GSLT'];

    public static function status(object $user): array
    {
        $data = self::read($user);
        return ['connected' => $data !== null, 'hint' => $data['hint'] ?? null, 'saved_at' => $data['saved_at'] ?? null];
    }

    public static function connected(?object $user): bool
    {
        return $user !== null && self::read($user) !== null;
    }

    /** Checks the key with Steam before storing it. */
    public static function save(object $user, string $key): array
    {
        $key = strtoupper(trim($key));
        if (!preg_match('/^[A-F0-9]{32}$/', $key)) throw ValidationException::withMessages(['key' => 'A Steam Web API key has 32 characters (0-9, A-F).']);
        try {
            $response = Http::timeout(8)->connectTimeout(3)->get(self::API.'GetAccountList/v1/', ['key' => $key]);
        } catch (Throwable) {
            throw ValidationException::withMessages(['key' => 'Steam could not be reached. Try again in a moment.']);
        }
        if (in_array($response->status(), [401, 403], true)) throw ValidationException::withMessages(['key' => 'Steam did not accept this key.']);
        if (!$response->successful()) throw ValidationException::withMessages(['key' => 'Steam answered with an error ('.$response->status().'). Try again later.']);
        $data = ['key' => Crypt::encryptString($key), 'hint' => substr($key, -4), 'saved_at' => now()->toIso8601String()];
        if (!Storage::disk('local')->put(self::path($user), json_encode($data, JSON_THROW_ON_ERROR))) throw new \RuntimeException('Could not save the Steam key.');
        return self::status($user);
    }

    public static function remove(object $user): void
    {
        Storage::disk('local')->delete(self::path($user));
    }

    /**
     * Creates a GSLT for a game. $appId is the game's own app (CS2: 730), not the dedicated-server app.
     * Steam limits how many tokens an account may hold and requires a phone-verified, non-limited account.
     */
    public static function createToken(object $user, int $appId, string $memo): string
    {
        $data = self::read($user);
        if ($data === null) throw ValidationException::withMessages(['token' => 'Connect your Steam account first (account menu → Steam).']);
        try {
            $key = Crypt::decryptString($data['key']);
        } catch (Throwable) {
            self::remove($user);
            throw ValidationException::withMessages(['token' => 'Your saved Steam key could not be read. Connect your Steam account again.']);
        }
        try {
            $response = Http::asForm()->timeout(10)->connectTimeout(3)->post(self::API.'CreateAccount/v1/', ['key' => $key, 'appid' => $appId, 'memo' => mb_substr(trim($memo) ?: 'Pelican server', 0, 32)]);
        } catch (Throwable) {
            throw ValidationException::withMessages(['token' => 'Steam could not be reached. Try again in a moment.']);
        }
        $token = $response->successful() ? $response->json('response.login_token') : null;
        if (!is_string($token) || !preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            throw ValidationException::withMessages(['token' => in_array($response->status(), [401, 403], true)
                ? 'Steam refused the request. Your key may have been revoked; connect your Steam account again.'
                : 'Steam did not create a token. Your account may need a verified phone number, or has reached its token limit.']);
        }
        return $token;
    }

    /** Token for a game picked in the creator: the game's Steam app is looked up by name. */
    public static function createTokenForGame(object $user, string $gameName, string $memo): string
    {
        $art = SteamArt::find($gameName);
        if (!$art) throw ValidationException::withMessages(['token' => 'This game could not be found on Steam. Create a token on steamcommunity.com/dev/managegameservers instead.']);
        return self::createToken($user, (int) $art['appid'], $memo);
    }

    private static function read(object $user): ?array
    {
        $disk = Storage::disk('local');
        $path = self::path($user);
        if (!$disk->exists($path)) return null;
        $data = json_decode((string) $disk->get($path), true);
        return is_array($data) && is_string($data['key'] ?? null) ? $data : null;
    }

    private static function path(object $user): string
    {
        return self::DIR.((int) $user->id).'.json';
    }
}
