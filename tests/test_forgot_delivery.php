<?php
declare(strict_types=1);

// Real controller with fake boundaries: no database or email provider required.
class Controller {
    protected function isPost(): bool { return true; }
    protected function verifyCsrf(): void {}
    protected function view(string $name, array $data): void {}
}
class Auth { public static function check(): bool { return false; } }
class User {
    public function findByEmail(string $email): ?array { $GLOBALS['lookups']++; return $GLOBALS['user']; }
    public function createPasswordResetToken(string $email): string { $GLOBALS['created']++; return 'test-token'; }
    public function deletePasswordResetToken(string $token): void { $GLOBALS['removed'][] = $token; }
}
class Mailer {
    public function isEnabled(): bool { return $GLOBALS['enabled']; }
    public function sendPasswordResetEmail(string $email, string $name, string $url, int $ttl): bool { $GLOBALS['sent']++; return $GLOBALS['accepted']; }
    public function getLastError(): string { return 'Simulated delivery failure'; }
}
function full_url(string $path): string { return 'https://example.com/' . $path; }
function flash(string $type, string $text): void { $GLOBALS['flashes'][] = [$type, $text]; }
require dirname(__DIR__) . '/app/controllers/AuthController.php';

function attempt(bool $enabled, ?array $user, bool $accepted): array {
    $GLOBALS['enabled'] = $enabled;
    $GLOBALS['user'] = $user;
    $GLOBALS['accepted'] = $accepted;
    foreach (['lookups', 'created', 'sent'] as $key) $GLOBALS[$key] = 0;
    $GLOBALS['flashes'] = $GLOBALS['removed'] = [];
    $_POST = ['email' => 'person@example.com'];
    $_SESSION = [];
    (new AuthController())->forgot();
    return ['lookups' => $GLOBALS['lookups'], 'created' => $GLOBALS['created'], 'removed' => $GLOBALS['removed'], 'sent' => $GLOBALS['sent'], 'flash' => $GLOBALS['flashes'][0]];
}
function verify(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$disabledCase = attempt(false, ['status' => 'active'], false);
verify($disabledCase['lookups'] === 0 && $disabledCase['created'] === 0 && $disabledCase['sent'] === 0, 'Unconfigured service must not look up users or create a token');
verify($disabledCase['flash'][0] === 'warning', 'Unconfigured service must not announce success');
$acceptedCase = attempt(true, ['status' => 'active'], true);
verify($acceptedCase['created'] === 1 && $acceptedCase['sent'] === 1 && $acceptedCase['removed'] === [], 'Accepted reset should retain the token');
$failedCase = attempt(true, ['status' => 'active'], false);
verify($failedCase['removed'] === ['test-token'], 'Revoke only the token whose delivery failed');
$unknownCase = attempt(true, null, true);
$lockedCase = attempt(true, ['status' => 'locked'], true);
verify($unknownCase['created'] === 0 && $lockedCase['created'] === 0, 'Missing/locked accounts must not get tokens');
verify($acceptedCase['flash'] === $failedCase['flash'] && $acceptedCase['flash'] === $unknownCase['flash'] && $acceptedCase['flash'] === $lockedCase['flash'], 'Do not reveal account existence or delivery outcome');
echo "Forgot-password delivery: 6 checks passed\n";
