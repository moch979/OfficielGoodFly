<?php

/**
 * Sends a plain-text UTF-8 email through the SMTP server configured in the
 * admin, or PHP mail() when no SMTP host is set. Throws on failure.
 */
function send_mail(string $to, string $subject, string $body, string $replyTo = ''): void
{
    $from = trim(s('mail_from'));
    if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        $from = 'no-reply@' . ($host ?: 'localhost');
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('عنوان المستلم غير صالح.');
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromName = '=?UTF-8?B?' . base64_encode(s('about_card_title', 'Good Fly Travel')) . '?=';
    $headers = [
        'From' => $fromName . ' <' . $from . '>',
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => 'base64',
        'Date' => date('r'),
        'Message-ID' => '<' . bin2hex(random_bytes(12)) . '@' . substr(strrchr($from, '@'), 1) . '>',
    ];
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers['Reply-To'] = $replyTo;
    }
    $encodedBody = chunk_split(base64_encode($body));

    if (trim(s('smtp_host')) === '') {
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        if (!@mail($to, $encodedSubject, $encodedBody, implode("\r\n", $headerLines))) {
            throw new RuntimeException('فشل إرسال البريد عبر خادم الموقع. أضف إعدادات SMTP من صفحة التنبيهات.');
        }
        return;
    }

    smtp_send($from, $to, array_merge($headers, ['To' => $to, 'Subject' => $encodedSubject]), $encodedBody);
}

function smtp_send(string $from, string $to, array $headers, string $body): void
{
    $host = trim(s('smtp_host'));
    $port = (int) s('smtp_port', '587') ?: 587;
    $secure = strtolower(trim(s('smtp_secure', 'tls')));
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

    $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        throw new RuntimeException('تعذر الاتصال بخادم SMTP: ' . $errstr);
    }
    stream_set_timeout($socket, 15);

    $read = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };
    $command = static function (?string $line, array $expect) use ($socket, $read): string {
        if ($line !== null) {
            fwrite($socket, $line . "\r\n");
        }
        $response = $read();
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expect, true)) {
            throw new RuntimeException('رفض خادم SMTP الطلب: ' . trim($response));
        }
        return $response;
    };

    try {
        $command(null, [220]);
        $hostname = preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
        $command('EHLO ' . $hostname, [250]);
        if ($secure === 'tls') {
            $command('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('تعذر تفعيل TLS مع خادم SMTP.');
            }
            $command('EHLO ' . $hostname, [250]);
        }
        $user = trim(s('smtp_user'));
        if ($user !== '') {
            $command('AUTH LOGIN', [334]);
            $command(base64_encode($user), [334]);
            $command(base64_encode(s('smtp_pass')), [235]);
        }
        $command('MAIL FROM:<' . $from . '>', [250]);
        $command('RCPT TO:<' . $to . '>', [250, 251]);
        $command('DATA', [354]);
        $message = '';
        foreach ($headers as $name => $value) {
            $message .= $name . ': ' . $value . "\r\n";
        }
        $message .= "\r\n" . str_replace("\n.", "\n..", $body);
        $command($message . "\r\n.", [250]);
        $command('QUIT', [221]);
    } finally {
        fclose($socket);
    }
}

/** Never lets a mail problem break the visitor's booking. */
function notify_new_booking(int $bookingId, array $lines): void
{
    $to = trim(s('notify_email'));
    if ($to === '') {
        return;
    }
    $admin = site_url() . '/admin/booking.php?id=' . $bookingId;
    $body = implode("\n", $lines) . "\n\n" . 'فتح الطلب في لوحة التحكم: ' . $admin . "\n";
    try {
        send_mail($to, 'طلب حجز جديد #' . $bookingId . ' - Good Fly', $body);
    } catch (Throwable $e) {
        log_error('Booking mail #' . $bookingId . ': ' . $e->getMessage());
    }
}
