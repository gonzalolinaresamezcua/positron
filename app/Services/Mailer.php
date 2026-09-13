<?php

declare(strict_types=1);

namespace Positrom\Services;

use Positrom\Core\Config;
use Positrom\Models\EmailLog;

final class Mailer
{
    public function send(
        string $to,
        string $subject,
        string $html,
        string $type = 'generic',
        ?int $userId = null
    ): bool {
        $host = (string) Config::get('smtp.host');
        if ($host === '' || Config::isPlaceholderSecret($host)) {
            EmailLog::write($userId, $type, $to, $subject, 'skipped', 'SMTP no configurado');
            $this->logFallback($to, $subject, $html);
            return false;
        }
        try {
            $this->smtpSend($to, $subject, $html);
            EmailLog::write($userId, $type, $to, $subject, 'sent');
            return true;
        } catch (\Throwable $e) {
            EmailLog::write($userId, $type, $to, $subject, 'error', mb_substr($e->getMessage(), 0, 250));
            $this->logFallback($to, $subject, $html);
            return false;
        }
    }

    public function paymentReminder(array $user, array $sub): bool
    {
        $when = $sub['next_payment_date'] ?? 'próximamente';
        $html = $this->wrap(
            'Recordatorio de cobro POSITROM',
            '<p>Hola ' . e($user['name']) . ',</p>
            <p>Te recordamos que tu suscripción POSITROM (12 €/mes) se renovará el <strong>' . e((string) $when) . '</strong>.</p>
            <p>Si el cargo no se completa, el acceso al chat se pausará hasta regularizar el pago.</p>
            <p><a href="' . e(url('/cuenta')) . '">Ver tu cuenta</a></p>'
        );
        return $this->send($user['email'], 'POSITROM: recordatorio de cobro mensual', $html, 'payment_reminder', (int) $user['id']);
    }

    public function paymentReceived(array $user, string $amount): bool
    {
        $html = $this->wrap(
            'Pago recibido',
            '<p>Hola ' . e($user['name']) . ',</p>
            <p>Hemos registrado un pago de <strong>' . e($amount) . '</strong> a tu suscripción POSITROM.</p>'
        );
        return $this->send($user['email'], 'POSITROM: pago recibido', $html, 'payment_received', (int) $user['id']);
    }

    public function subscriptionActivated(array $user): bool
    {
        $html = $this->wrap(
            'Suscripción activa',
            '<p>Hola ' . e($user['name']) . ',</p>
            <p>Tu órbita POSITROM está activa. Ya puedes hablar con el modelo composer-2.5.</p>
            <p><a href="' . e(url('/chat')) . '">Abrir el chat</a></p>'
        );
        return $this->send($user['email'], 'POSITROM: suscripción activada', $html, 'activated', (int) $user['id']);
    }

    private function wrap(string $title, string $inner): string
    {
        return '<!doctype html><html lang="es"><body style="background:#040812;color:#d7f0ff;font-family:sans-serif;padding:24px">
        <div style="max-width:560px;margin:auto;background:#0a1a33;border:1px solid #1a6bff;padding:24px;border-radius:16px">
        <h1 style="color:#00ff9c;font-size:20px">' . e($title) . '</h1>
        ' . $inner . '
        <p style="color:#7aa0b8;font-size:12px;margin-top:24px">POSITROM · suscripción 12 €/mes</p>
        </div></body></html>';
    }

    private function smtpSend(string $to, string $subject, string $html): void
    {
        $host = (string) Config::get('smtp.host');
        $port = (int) Config::get('smtp.port', 587);
        $enc = strtolower((string) Config::get('smtp.encryption', 'tls'));
        $user = (string) Config::get('smtp.user');
        $pass = (string) Config::get('smtp.pass');
        $from = (string) Config::get('smtp.from');
        $fromName = (string) Config::get('smtp.from_name', 'POSITROM');

        $remote = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 20);
        if ($fp === false) {
            throw new \RuntimeException('SMTP conexión: ' . $errstr);
        }
        stream_set_timeout($fp, 20);
        $this->expect($fp, 220);
        $this->cmd($fp, 'EHLO positrom.local', 250);
        if ($enc === 'tls') {
            $this->cmd($fp, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('No se pudo iniciar TLS SMTP.');
            }
            $this->cmd($fp, 'EHLO positrom.local', 250);
        }
        if ($user !== '') {
            $this->cmd($fp, 'AUTH LOGIN', 334);
            $this->cmd($fp, base64_encode($user), 334);
            $this->cmd($fp, base64_encode($pass), 235);
        }
        $this->cmd($fp, 'MAIL FROM:<' . $from . '>', 250);
        $this->cmd($fp, 'RCPT TO:<' . $to . '>', 250);
        $this->cmd($fp, 'DATA', 354);
        $headers = [
            'From: ' . $this->encodeHeader($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Date: ' . date(DATE_RFC2822),
        ];
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.";
        $this->cmd($fp, $payload, 250);
        $this->cmd($fp, 'QUIT', 221);
        fclose($fp);
    }

    /** @param resource $fp */
    private function cmd($fp, string $cmd, int $expect): void
    {
        fwrite($fp, $cmd . "\r\n");
        $this->expect($fp, $expect);
    }

    /** @param resource $fp */
    private function expect($fp, int $code): void
    {
        $line = '';
        while (($chunk = fgets($fp, 512)) !== false) {
            $line = $chunk;
            if (isset($chunk[3]) && $chunk[3] === ' ') {
                break;
            }
        }
        if (!str_starts_with($line, (string) $code)) {
            throw new \RuntimeException('SMTP inesperado: ' . trim($line));
        }
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function logFallback(string $to, string $subject, string $html): void
    {
        $file = POSITROM_STORAGE . '/logs/mail-' . date('Y-m') . '.log';
        $entry = '[' . now() . '] to=' . $to . ' subject=' . $subject . PHP_EOL;
        @file_put_contents($file, $entry, FILE_APPEND);
        unset($html);
    }
}
