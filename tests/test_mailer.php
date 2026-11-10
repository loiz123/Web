<?php
declare(strict_types=1);

$repo = dirname(__DIR__);
define('ROOT', sys_get_temp_dir() . '/bds-mail-test-' . bin2hex(random_bytes(6)));
require $repo . '/app/helpers/functions.php';
require $repo . '/app/helpers/Mailer.php';
$GLOBALS['config'] = ['app_name' => 'BĐS Việt'];

class FakeResendMailer extends Mailer
{
    public array $payloads = [];
    public array $response = ['status' => 200, 'data' => ['id' => 'test-message']];
    public bool $timeout = false;
    protected function resendRequest(array $payload): array
    {
        $this->payloads[] = $payload;
        if ($this->timeout) throw new RuntimeException('Simulated HTTPS timeout');
        return $this->response;
    }
}
$checks = 0;
function check(bool $ok, string $message): void {
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
$settings = ['transport' => 'resend', 'resend_api_key' => 'test-secret-key', 'from_email' => 'no-reply@example.com', 'from_name' => 'BĐS Việt'];
try {
    $mailer = new FakeResendMailer([], $settings);
    check($mailer->isEnabled(), 'Resend should be enabled with valid configuration');
    $link = 'https://example.com/auth/reset?token=private-reset-token';
    check($mailer->sendPasswordResetEmail('recipient@example.com', '<User>', $link), 'Provider acceptance should succeed');
    $payload = $mailer->payloads[0];
    check($payload['to'] === ['recipient@example.com'], 'Use the requested recipient');
    check($payload['from'] === 'BĐS Việt <no-reply@example.com>', 'Use the configured sender');
    check(str_contains($payload['html'], $link), 'Include reset link in the email');
    check(str_contains($payload['html'], '&lt;User&gt;'), 'Escape the name in HTML');
    foreach ([401, 403, 429, 503] as $status) {
        $mailer->response = ['status' => $status, 'data' => ['message' => 'test-secret-key private-reset-token']];
        check(!$mailer->sendPasswordResetEmail('recipient@example.com', 'User', $link), 'Provider rejection must not claim success');
        check(str_contains($mailer->getLastError(), (string)$status), 'Diagnostics should include HTTP status');
    }
    $mailer->response = ['status' => 200, 'data' => []];
    check(!$mailer->sendPasswordResetEmail('recipient@example.com', 'User', $link), 'Missing message ID must not claim acceptance');
    $mailer->timeout = true;
    check(!$mailer->sendPasswordResetEmail('recipient@example.com', 'User', $link), 'Handle a transport timeout');
    $disabled = new FakeResendMailer([], array_replace($settings, ['resend_api_key' => '']));
    check(!$disabled->sendPasswordResetEmail('recipient@example.com', 'User', $link), 'Missing key must not claim success');
    check(count($disabled->payloads) === 0, 'No external request without credentials');
    check(!(new FakeResendMailer([], array_replace($settings, ['from_email' => 'invalid'])))->isEnabled(), 'Reject an invalid sender');
    check((new FakeResendMailer([], array_replace($settings, ['transport' => 'auto'])))->isEnabled(), 'Auto-select Resend when key is provided');
    check(!(new Mailer(['enabled' => false], ['transport' => 'smtp']))->isEnabled(), 'Disabled SMTP remains disabled');
    $log = file_get_contents(ROOT . '/storage/logs/mail.log');
    check(!str_contains($log, 'private-reset-token') && !str_contains($log, 'test-secret-key'), 'Logs must not contain reset tokens, API keys or the provider response body');
    echo "Mailer: {$checks} checks passed (no real email sent)\n";
} finally {
    if (is_file(ROOT . '/storage/logs/mail.log')) unlink(ROOT . '/storage/logs/mail.log');
    if (is_dir(ROOT . '/storage/logs')) rmdir(ROOT . '/storage/logs');
    if (is_dir(ROOT . '/storage')) rmdir(ROOT . '/storage');
    if (is_dir(ROOT)) rmdir(ROOT);
}
