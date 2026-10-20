<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
/**
 * Interface size (Appearance → Interface size). Text sizes in Simplify's CSS are fixed in px, so the
 * served bundles multiply every px value of font-size, font, line-height and the --ps-text tokens by
 * var(--ps-scale). Pelican's own pages are rem-based and scale with the root font size. CSS zoom is
 * not used: it stretches the 100dvh layouts beyond the window.
 */
final class InterfaceScale
{
    public const OPTIONS = [100, 110, 125, 150];

    public static function percent(): int
    {
        $value = (int) (AppearanceSettings::all()['interface_scale'] ?? 100);
        return in_array($value, self::OPTIONS, true) ? $value : 100;
    }

    /** Inline <style> for the page head; empty at 100%. */
    public static function style(): string
    {
        $percent = self::percent();
        if ($percent === 100) return '';
        $factor = rtrim(rtrim(number_format($percent / 100, 2, '.', ''), '0'), '.');
        return ':root{--ps-scale:'.$factor.'}html{font-size:'.$percent.'%}';
    }

    public static function css(string $css): string
    {
        return (string) preg_replace_callback(
            '/(?<![\w-])(font-size|font|line-height|--ps-text(?:-[a-z]+)?)(\s*:\s*)([^;{}]+)/',
            fn (array $m): string => $m[1].$m[2].preg_replace('/(?<![\w.-])(\d*\.?\d+)px\b/', 'calc($1px * var(--ps-scale, 1))', $m[3]),
            $css
        );
    }
}
