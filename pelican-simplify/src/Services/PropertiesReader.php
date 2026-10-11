<?php

declare(strict_types=1);

namespace Simplify\Panel\Services;

/** Java properties decoding, including continuation lines and escaped MOTDs. */
final class PropertiesReader
{
    public static function decode(string $value): string
    {
        return preg_replace_callback('/\\\\((?:u[a-fA-F0-9]{4}(?:\\\\u[a-fA-F0-9]{4})*)|.)/s', static function (array $match): string {
            if (preg_match('/^u[a-fA-F0-9]{4}/', $match[1])) {
                preg_match_all('/u([a-fA-F0-9]{4})/', $match[1], $units);
                $binary = implode('', array_map(static fn ($hex) => pack('n', hexdec($hex)), $units[1]));
                return mb_convert_encoding($binary, 'UTF-8', 'UTF-16BE');
            }
            return match ($match[1]) { 'n' => "\n", 'r' => "\r", 't' => "\t", 'f' => "\f", default => $match[1] };
        }, $value);
    }
    public static function parse(string $content): array
    {
        $rows = []; $pending = '';
        foreach (preg_split('/\r\n|\n|\r/', $content) as $line) {
            $line = $pending !== '' ? $pending . ltrim($line) : $line;
            $pending = '';
            if (preg_match('/^\s*[#!]/', $line)) { continue; }
            $slashes = strlen($line) - strlen(rtrim($line, '\\'));
            if ($slashes % 2 === 1) { $pending = substr($line, 0, -1); continue; }
            if (preg_match('/^\s*([^\s=:]+)\s*[=:]?\s*(.*)$/', $line, $m)) {
                $rows[$m[1]] = self::decode($m[2]);
            }
        }
        return $rows;
    }
}
