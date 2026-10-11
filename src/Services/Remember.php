<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
use Illuminate\Support\Facades\Cache;
/**
 * Cache::remember for slow lookups (Wings, Minecraft pings, catalogues) that also remembers
 * misses: null results and failures are cached for $missTtl seconds instead of retried on
 * every request.
 */
final class Remember
{
    public static function value(string $key, int $ttl, \Closure $resolve, ?int $missTtl = null): mixed
    {
        $stored = Cache::get($key);
        if (is_array($stored) && array_key_exists('value', $stored)) return $stored['value'];
        try {
            $value = $resolve();
        } catch (\Throwable) {
            $value = null;
        }
        Cache::put($key, ['value' => $value], $value === null ? ($missTtl ?? $ttl) : $ttl);
        return $value;
    }

    public static function forget(string $key): void { Cache::forget($key); }
}
