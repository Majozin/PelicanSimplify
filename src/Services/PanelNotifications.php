<?php
declare(strict_types=1);
namespace Simplify\Panel\Services;
/**
 * Pelican's database notifications (backups, installs, subusers, plugin jobs) for the Simplify bell.
 * The dashboard hides Filament's topbar, so without this they would only show on legacy pages.
 */
final class PanelNotifications
{
    private const TONES = ['success' => 'ok', 'warning' => 'warn', 'danger' => 'danger', 'info' => 'info'];

    /** Unread Filament notifications of the signed-in user, newest first. */
    public static function unread(int $limit = 20): array
    {
        $user = user();
        if (!$user || !method_exists($user, 'unreadNotifications')) return [];
        try {
            return $user->unreadNotifications()->latest()->limit($limit)->get()
                ->map(fn ($notification): ?array => self::present($notification))->filter()->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function markRead(array $ids): void
    {
        $ids = array_values(array_filter($ids, fn ($id): bool => is_string($id) && preg_match('/^[0-9a-f-]{36}$/i', $id) === 1));
        $user = user();
        if ($ids === [] || !$user || !method_exists($user, 'unreadNotifications')) return;
        $user->unreadNotifications()->whereIn('id', $ids)->update(['read_at' => now()]);
    }

    private static function present(object $notification): ?array
    {
        $data = $notification->data ?? null;
        if (!is_array($data)) return null;
        $title = self::text($data['title'] ?? '');
        $body = self::text($data['body'] ?? '');
        if ($title === '' && $body === '') return null;
        $action = null;
        foreach (is_array($data['actions'] ?? null) ? $data['actions'] : [] as $candidate) {
            $url = is_array($candidate) ? ($candidate['url'] ?? null) : null;
            if (is_string($url) && self::safeUrl($url)) {
                $action = ['label' => self::text($candidate['label'] ?? '') ?: 'Open', 'url' => $url];
                break;
            }
        }
        return [
            'id' => 'pelican-'.$notification->id,
            'key' => (string) $notification->id,
            'tone' => self::TONES[$data['status'] ?? $data['color'] ?? ''] ?? 'info',
            'title' => $title !== '' ? $title : $body,
            'text' => $title !== '' ? $body : '',
            'time' => $notification->created_at?->diffForHumans(),
            'action' => $action,
        ];
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5)) : '';
    }

    /** Relative links or links to this panel only. */
    private static function safeUrl(string $url): bool
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) return true;
        $host = parse_url($url, PHP_URL_HOST);
        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) && is_string($host) && $host === request()->getHost();
    }
}
