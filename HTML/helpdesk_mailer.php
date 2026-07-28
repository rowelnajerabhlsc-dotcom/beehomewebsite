<?php
/**
 * helpdesk_mailer.php
 * Sends the finalized case email to a member.
 * Mirrors the pattern in generate_reg_link.php's sendRegLinkEmail():
 * local isMail() rather than SMTP relay, since outbound SMTP ports
 * are blocked on this hosting account. Reuses $reg_mail_config for
 * from_email / from_name only.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendCaseEmail(string $to, string $subject, string $bodyHtml, string $refForLog): bool {
    global $reg_mail_config;

    if (empty($reg_mail_config['from_email'])) {
        error_log("Help desk mail not configured (REG_MAIL_FROM missing). Ref: {$refForLog}");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isMail();
        $mail->setFrom($reg_mail_config['from_email'], $reg_mail_config['from_name']);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Help desk mail failed for ref {$refForLog}: " . $mail->ErrorInfo);
        return false;
    }
}
