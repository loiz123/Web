<?php
// Gửi email bằng Resend HTTPS API hoặc SMTP; log không chứa link đặt lại mật khẩu.
class Mailer
{
    private array $smtpConfig;
    private array $mailConfig;
    private string $lastError = '';

    public function __construct(?array $smtpConfig = null, ?array $mailConfig = null)
    {
        $this->smtpConfig = $smtpConfig ?? (array)config('smtp', []);
        $this->mailConfig = $mailConfig ?? (array)config('mail', []);
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function isEnabled(): bool
    {
        if ($this->transport() === 'resend') {
            return trim((string)($this->mailConfig['resend_api_key'] ?? '')) !== ''
                && (bool)filter_var($this->mailConfig['from_email'] ?? '', FILTER_VALIDATE_EMAIL);
        }
        if ($this->transport() !== 'smtp') return false;
        return !empty($this->smtpConfig['enabled'])
            && !empty($this->smtpConfig['host'])
            && !empty($this->smtpConfig['username'])
            && !empty($this->smtpConfig['password']);
    }

    /**
     * true nghĩa là nhà cung cấp đã nhận thư, chưa bảo đảm thư vào Inbox.
     */
    public function send(string $toEmail, string $subject, string $htmlBody, string $toName = '', array $meta = []): bool
    {
        $this->lastError = '';
        $toEmail = trim($toEmail);

        if (!$this->isEnabled()) {
            $this->lastError = 'Dịch vụ gửi email chưa đủ cấu hình Resend hoặc SMTP.';
            $this->writeMailLog($toEmail, $subject, $htmlBody, 'NOT_CONFIGURED', $this->lastError, $meta);
            return false;
        }
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Địa chỉ email nhận không hợp lệ.';
            return false;
        }

        try {
            $sent = $this->transport() === 'resend'
                ? $this->sendViaResend($toEmail, $subject, $htmlBody)
                : $this->sendViaSmtpSocket($toEmail, $toName, $subject, $htmlBody);
            if ($sent) {
                $this->writeMailLog($toEmail, $subject, $htmlBody, 'ACCEPTED_' . strtoupper($this->transport()), 'OK', $meta);
                return true;
            }
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
        }

        $this->writeMailLog($toEmail, $subject, $htmlBody, 'DELIVERY_ERROR', $this->lastError, $meta);
        return false;
    }

    /**
     * Gửi email khôi phục mật khẩu với giao diện HTML chuẩn chỉnh.
     */
    public function sendPasswordResetEmail(string $toEmail, string $fullName, string $resetLink, int $ttlMinutes = 30): bool
    {
        $subject = '[' . config('app_name', 'BĐS Việt') . '] Yêu cầu đặt lại mật khẩu tài khoản';
        $html = self::buildPasswordResetHtml($fullName, $resetLink, $ttlMinutes);
        return $this->send($toEmail, $subject, $html, $fullName, ['type' => 'password_reset']);
    }

    private function transport(): string
    {
        $transport = strtolower(trim((string)($this->mailConfig['transport'] ?? 'auto')));
        return $transport === 'auto'
            ? (!empty($this->mailConfig['resend_api_key']) ? 'resend' : 'smtp')
            : $transport;
    }

    private function sendViaResend(string $toEmail, string $subject, string $htmlBody): bool
    {
        $fromEmail = trim((string)$this->mailConfig['from_email']);
        $fromName = trim((string)($this->mailConfig['from_name'] ?? config('app_name', 'BĐS Việt')));
        $response = $this->resendRequest([
            'from' => $fromName !== '' ? $fromName . ' <' . $fromEmail . '>' : $fromEmail,
            'to' => [$toEmail],
            'subject' => $subject,
            'html' => $htmlBody,
        ]);
        if ($response['status'] >= 200 && $response['status'] < 300 && !empty($response['data']['id'])) return true;
        $this->lastError = 'Resend không chấp nhận thư (HTTP ' . $response['status'] . '). Kiểm tra API key, domain gửi và giới hạn người nhận trong Resend.';
        return false;
    }

    /** Ranh giới HTTP riêng để kiểm thử phản hồi mà không gửi mail thật. */
    protected function resendRequest(array $payload): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('PHP cần bật extension cURL để gửi email qua HTTPS.');
        $curl = curl_init('https://api.resend.com/emails');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => max(1, (int)($this->mailConfig['timeout'] ?? 15)),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . trim((string)$this->mailConfig['resend_api_key']),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
        try {
            $body = curl_exec($curl);
            if ($body === false) throw new RuntimeException('Không kết nối được Resend qua HTTPS (cURL ' . curl_errno($curl) . ').');
            return ['status' => (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'data' => json_decode($body, true)];
        } finally {
            curl_close($curl);
        }
    }

    /**
     * Tạo nội dung HTML đẹp mắt cho email đặt lại mật khẩu, có nút "Đặt lại mật khẩu" trỏ về /auth/reset?token=...
     */
    public static function buildPasswordResetHtml(string $fullName, string $resetLink, int $ttlMinutes = 30): string
    {
        $appName = htmlspecialchars((string)config('app_name', 'BĐS Việt'), ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($fullName !== '' ? $fullName : 'Quý khách', ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đặt lại mật khẩu - {$appName}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(15,23,42,0.08);">
          <tr>
            <td style="background:linear-gradient(135deg,#1d4ed8,#2563eb);padding:28px 32px;text-align:center;color:#ffffff;">
              <h1 style="margin:0;font-size:22px;font-weight:700;letter-spacing:0.3px;">{$appName}</h1>
              <p style="margin:6px 0 0;font-size:14px;opacity:0.9;">Hệ thống Giao dịch &amp; Quản lý Bất động sản</p>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <h2 style="margin:0 0 16px;font-size:19px;color:#0f172a;">Xin chào {$safeName},</h2>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">
                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản gắn với địa chỉ email này trên hệ thống <strong>{$appName}</strong>.
              </p>
              <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#334155;">
                Vui lòng nhấn vào nút bên dưới để tiến hành thiết lập mật khẩu mới (liên kết có hiệu lực trong vòng <strong>{$ttlMinutes} phút</strong>):
              </p>
              <div style="text-align:center;margin:28px 0;">
                <a href="{$safeLink}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:14px 32px;border-radius:999px;box-shadow:0 4px 12px rgba(37,99,235,0.3);">
                  Đặt lại mật khẩu
                </a>
              </div>
              <p style="margin:24px 0 8px;font-size:13px;line-height:1.6;color:#64748b;">
                Nếu nút bấm không hoạt động, bạn có thể sao chép và dán đường dẫn sau vào trình duyệt:
              </p>
              <p style="margin:0 0 24px;padding:12px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;word-break:break-all;font-size:13px;color:#2563eb;">
                <a href="{$safeLink}" style="color:#2563eb;text-decoration:underline;">{$safeLink}</a>
              </p>
              <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
              <p style="margin:0;font-size:13px;line-height:1.5;color:#94a3b8;">
                Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này. Mật khẩu hiện tại của bạn vẫn được bảo mật an toàn.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background-color:#f8fafc;padding:16px 32px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;">
              &copy; {$appName} — Email tự động, vui lòng không trả lời thư này.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    /**
     * Thực thi kết nối socket SMTP (hỗ trợ STARTTLS port 587 hoặc SSL port 465, AUTH LOGIN).
     */
    private function sendViaSmtpSocket(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $host     = trim((string)($this->smtpConfig['host'] ?? 'smtp.gmail.com'));
        $port     = (int)($this->smtpConfig['port'] ?? 587);
        $username = trim((string)($this->smtpConfig['username'] ?? ''));
        $password = (string)($this->smtpConfig['password'] ?? '');
        $fromName = trim((string)($this->smtpConfig['from_name'] ?? config('app_name', 'Bất Động Sản')));
        $timeout  = (int)($this->smtpConfig['timeout'] ?? 15);

        $remote = ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($socket)) {
            $this->lastError = "Không thể kết nối máy chủ SMTP ({$remote}): [{$errno}] {$errstr}";
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expectSmtpCode($socket, [220]);
            $localDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $localDomain = preg_replace('/[^a-zA-Z0-9.\-]/', '', (string)$localDomain) ?: 'localhost';

            $this->sendSmtpCommand($socket, "EHLO {$localDomain}", [250]);

            // Nếu dùng cổng 587 (STARTTLS), nâng cấp kết nối lên TLS
            if ($port === 587) {
                $this->sendSmtpCommand($socket, 'STARTTLS', [220]);
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    throw new RuntimeException('Không thể thiết lập mã hóa STARTTLS với máy chủ SMTP.');
                }
                $this->sendSmtpCommand($socket, "EHLO {$localDomain}", [250]);
            }

            // Xác thực AUTH LOGIN
            $this->sendSmtpCommand($socket, 'AUTH LOGIN', [334]);
            $this->sendSmtpCommand($socket, base64_encode($username), [334]);
            $this->sendSmtpCommand($socket, base64_encode($password), [235]);

            // Khai báo người gửi & người nhận
            $this->sendSmtpCommand($socket, "MAIL FROM:<{$username}>", [250]);
            $this->sendSmtpCommand($socket, "RCPT TO:<{$toEmail}>", [250, 251]);
            $this->sendSmtpCommand($socket, 'DATA', [354]);

            // Đóng gói tiêu đề MIME và nội dung HTML (mã hóa Base64 chuẩn RFC 2045)
            $encodedSubject  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
            $toHeader        = $toName !== ''
                ? ('=?UTF-8?B?' . base64_encode($toName) . "?= <{$toEmail}>")
                : "<{$toEmail}>";

            $headers = [
                'Date: ' . date('r'),
                "From: {$encodedFromName} <{$username}>",
                "To: {$toHeader}",
                "Subject: {$encodedSubject}",
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $localDomain . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'X-Mailer: BDS-Viet-PHP-Mailer',
            ];

            $payload = implode("\r\n", $headers)
                . "\r\n\r\n"
                . chunk_split(base64_encode($htmlBody), 76, "\r\n")
                . "\r\n.";

            $this->sendSmtpCommand($socket, $payload, [250]);
            $this->sendSmtpCommand($socket, 'QUIT', [221, 250]);
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            @fclose($socket);
            return false;
        }
    }

    private function sendSmtpCommand($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expectSmtpCode($socket, $expectedCodes);
    }

    private function expectSmtpCode($socket, array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException("Phản hồi SMTP không hợp lệ (mã {$code}): " . trim($response));
        }
        return $response;
    }

    /**
     * Ghi nhật ký gửi thư vào storage/logs/mail.log
     */
    private function writeMailLog(string $toEmail, string $subject, string $htmlBody, string $status, string $detail = '', array $meta = []): void
    {
        // Không lưu token/link hoặc toàn bộ nội dung thư vào nhật ký.
        unset($meta['reset_link'], $meta['token']);
        $logDir = defined('ROOT') ? (ROOT . '/storage/logs') : (__DIR__ . '/../../storage/logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/mail.log';
        $entry = sprintf(
            "[%s] STATUS=%s | TO=%s | SUBJECT=%s | DETAIL=%s | META=%s\n",
            date('Y-m-d H:i:s'),
            $status,
            $toEmail,
            $subject,
            $detail,
            json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
