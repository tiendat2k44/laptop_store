<?php
/**
 * Lightweight mail helper using PHP mail()
 * Falls back to error_log if mail() is unavailable.
 */

function send_mail($toEmail, $subject, $htmlBody, $fromEmail = MAIL_FROM_EMAIL, $fromName = MAIL_FROM_NAME) {
    $toEmail = trim((string)$toEmail);
    if ($toEmail === '' || !isValidEmail($toEmail)) {
        return false;
    }

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $from = $fromName ? sprintf('%s <%s>', $fromName, $fromEmail) : $fromEmail;
    $headers[] = 'From: ' . $from;
    $headers[] = 'Reply-To: ' . $fromEmail;

    // Normalize line endings
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    try {
        $ok = @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
        if (!$ok) {
            error_log('[MAIL] failed to ' . $toEmail . ' | ' . strip_tags($subject));
        }
        return $ok;
    } catch (Throwable $e) {
        error_log('[MAIL][exception] ' . $e->getMessage());
        return false;
    }
}
