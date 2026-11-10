<?php
declare(strict_types=1);

$repo = dirname(__DIR__);
define('ROOT', sys_get_temp_dir() . '/bds-brevo-test-' . bin2hex(random_bytes(6)));
require $repo . '/app/helpers/functions.php';
require $repo . '/app/helpers/Mailer.php';
$GLOBALS['config'] = ['app_name' => 'BĐS Việt'];

class FakeBrevoMailer extends Mailer
{
    public array $payloads = [];
    public array $response = ['status' => 201, 'data' => ['messageId' => '<test-message@brevo.invalid>']];
    public bool $timeout = false;
    protected function brevoRequest(array $payload): array
    {
        $this->payloads[] = $payload;
        if ($this->timeout) throw new RuntimeException('Simulated HTTPS timeout');
        return $this->response;
    }
    protected function resendRequest(array $payload): array
    {
        throw new RuntimeException('Brevo must not send using the Resend key');
    }
}
$checks = 0;
function check(bool $ok, string $message): void {
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
$settings = ['transport' => 'brevo', 'brevo_api_key' => 'test-brevo-secret', 'resend_api_key' => 'test-resend-secret', 'from_email' => 'sender@example.com', 'from_name' => 'BĐS Việt'];
try {
    $mailer = new FakeBrevoMailer([], $settings);
    check($mailer->isEnabled(), 'Verified sender and Brevo key should enable sending');
    $link = 'https://example.com/auth/reset?token=private-reset-token';
    check($mailer->sendPasswordResetEmail('recipient@example.com', '<User>', $link), 'Accept a Brevo 201 response with messageId');
    $payload = $mailer->payloads[0];
    check($payload['sender'] === ['email' => 'sender@example.com', 'name' => 'BĐS Việt'], 'Use the configured sender');
    check($payload['to'] === [['email' => 'recipient@example.com', 'name' => '<User>']], 'Send to the requesting user, not the sender');
    check(str_contains($payload['htmlContent'], $link), 'Include reset URL in email HTML');
    check(str_contains($payload['htmlContent'], '&lt;User&gt;'), 'Escape HTML in the greeting');
    foreach ([400, 401, 403, 429, 503] as $status) {
        $mailer->response = ['status' => $status, 'data' => ['message' => 'test-brevo-secret private-reset-token']];
        check(!$mailer->sendPasswordResetEmail('recipient@example.com', 'User', $link), 'A rejected request must fail');
        check(str_contains($mailer->getLastError(), (string)$status), 'Report HTTP code without logging provider body');
    }
    $mailer->response = ['status' => 201, 'data' => []];
    check(!$mailer->sendPasswordResetEmail('recipient@example.com', 'User', $link), 'Missing messageId is not acceptance');
    $mailer->timeout = true;
    check(!$mailer->sendPasswordResetEmail('recipient@example.com', 'User', $link), 'Timeout must fail');
    $missingKey = new FakeBrevoMailer(['enabled' => true, 'host' => 'smtp.invalid', 'username' => 'test', 'password' => 'test'], array_replace($settings, ['brevo_api_key' => '']));
    check(!$missingKey->isEnabled(), 'Explicit Brevo must not fall back to another provider when its key is missing');
    check(!$missingKey->sendPasswordResetEmail('recipient@example.com', 'User', $link) && $missingKey->payloads === [], 'Never contact provider without a Brevo key');
    check(!(new FakeBrevoMailer([], array_replace($settings, ['from_email' => 'invalid'])))->isEnabled(), 'Reject an invalid sender');
    $auto = new FakeBrevoMailer([], array_replace($settings, ['transport' => 'auto']));
    check($auto->sendPasswordResetEmail('recipient@example.com', 'User', $link), 'Auto mode should prefer Brevo when both keys are present');
    $before = count($auto->payloads);
    check(!$auto->send('not-an-email', 'Test', '<p>Test</p>') && count($auto->payloads) === $before, 'Reject invalid recipient before contacting Brevo');
    $log = file_get_contents(ROOT . '/storage/logs/mail.log');
    check(!str_contains($log, 'test-brevo-secret') && !str_contains($log, 'test-resend-secret') && !str_contains($log, 'private-reset-token'), 'Do not log API keys, reset links or provider response bodies');
    echo "Brevo mailer: {$checks} checks passed (no real email sent)\n";
} finally {
    if (is_file(ROOT . '/storage/logs/mail.log')) unlink(ROOT . '/storage/logs/mail.log');
    if (is_dir(ROOT . '/storage/logs')) rmdir(ROOT . '/storage/logs');
    if (is_dir(ROOT . '/storage')) rmdir(ROOT . '/storage');
    if (is_dir(ROOT)) rmdir(ROOT);
}
