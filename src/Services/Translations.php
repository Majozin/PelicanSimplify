<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
/**
 * Simplify's translations: lang/<locale>.json, keyed by the English text, which is also the fallback.
 * PHP and Blade use __() (SimplifyProvider adds the folder to Laravel's JSON translations); the
 * browser scripts get the same table through window.simplifyI18n and Simplify.t().
 */
final class Translations
{
    private static array $tables = [];

    /** The table for the panel language, or [] for English and languages without a file. */
    public static function forJs(?string $locale = null): array
    {
        $locale = $locale ?? (string) app()->getLocale();
        if (!preg_match('/^[A-Za-z]{2,3}(?:_[A-Za-z]{2,4})?$/', $locale) || str_starts_with($locale, 'en')) return [];
        if (array_key_exists($locale, self::$tables)) return self::$tables[$locale];
        $path = plugin_path('pelican-simplify', 'lang', $locale.'.json');
        try {
            $table = is_file($path) ? json_decode((string) file_get_contents($path), true, 4, JSON_THROW_ON_ERROR) : [];
        } catch (\Throwable $exception) {
            report($exception);
            $table = [];
        }
        return self::$tables[$locale] = is_array($table) ? $table : [];
    }
}
