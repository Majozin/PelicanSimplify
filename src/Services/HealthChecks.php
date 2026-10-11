<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;

/** Latest stored results of Pelican's health checks (Spatie), for the admin tools icons. Reads only; never runs checks. */
final class HealthChecks
{
    public static function latest(): array
    {
        if (!user()?->isAdmin()) return [];
        try {
            $results = app(\Spatie\Health\ResultStores\ResultStore::class)->latestResults();
        } catch (\Throwable) {
            return [];
        }
        if (!$results) return [];
        return $results->storedCheckResults->map(fn ($result): array => [
            'label' => (string) $result->label,
            'status' => strtolower((string) $result->status),
            'message' => trim((string) ($result->notificationMessage ?: $result->shortSummary)),
        ])->values()->all();
    }
}
