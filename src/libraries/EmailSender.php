<?php

namespace Yllumi\Sayagi\libraries;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender
{
    protected PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(setting('emailer.debug_emailer') === 'yes');
        
        $this->mailer->isSMTP();
        $this->mailer->CharSet = 'UTF-8';

        if (setting('emailer.use_mailcatcher') === 'yes') {
            // Mailcatcher defaults — no auth, no encryption
            $this->mailer->SMTPDebug  = SMTP::DEBUG_SERVER;
            $this->mailer->Host       = 'localhost';
            $this->mailer->Port       = 1025;
            $this->mailer->SMTPAuth   = false;
            $this->mailer->SMTPSecure = false;
        } else {
            $this->mailer->SMTPDebug  = setting('emailer.debug_emailer') === 'yes' ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
            $this->mailer->Host       = setting('emailer.smtp_host')     ?: 'localhost';
            $this->mailer->Port       = (int)(setting('emailer.smtp_port') ?: 587);
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = setting('emailer.smtp_username') ?: '';
            $this->mailer->Password   = setting('emailer.smtp_password') ?: '';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $this->mailer->setFrom(
            setting('emailer.email_from') ?: 'no-reply@example.com',
            setting('emailer.sender_name') ?: 'Panel'
        );
    }

    /**
     * Send an email.
     *
     * @param  string|array $to      Single email string or [email => name, ...]
     * @param  string       $subject
     * @param  string       $body    HTML body
     * @param  string|null  $altBody Plain-text fallback (auto-stripped if null)
     * @return bool
     *
     * @throws Exception
     */
    public function sendEmail(string|array $to, string $subject, string $body, ?string $altBody = null): bool
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();

        if (is_string($to)) {
            $this->mailer->addAddress($to);
        } else {
            foreach ($to as $email => $name) {
                $this->mailer->addAddress(is_int($email) ? $name : $email, is_int($email) ? '' : $name);
            }
        }

        $this->mailer->isHTML(true);
        $this->mailer->Subject = $subject;
        $this->mailer->Body    = $body;
        $this->mailer->AltBody = $altBody ?? strip_tags($body);

        return $this->mailer->send();
    }

    /**
     * Render a simple OTP email template.
     */
    public static function otpTemplate(string $recipientName, string $otp, string $appName = 'Panel'): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f3f5;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <tr><td style="background:linear-gradient(135deg,#5c6bc0,#3f51b5);padding:32px 40px;text-align:center;">
          <h2 style="margin:0;color:#fff;font-size:20px;font-weight:700;letter-spacing:-.3px;">{$appName}</h2>
        </td></tr>
        <tr><td style="padding:40px;">
          <p style="margin:0 0 8px;font-size:15px;color:#495057;">Halo, <strong>{$recipientName}</strong></p>
          <p style="margin:0 0 28px;font-size:14px;color:#868e96;line-height:1.6;">
            Kami menerima permintaan reset password untuk akun Anda. Gunakan kode OTP berikut:
          </p>
          <div style="background:#f8f9fa;border:1.5px dashed #ced4da;border-radius:10px;padding:20px;text-align:center;margin-bottom:28px;">
            <span style="font-size:36px;font-weight:800;letter-spacing:10px;color:#3f51b5;">{$otp}</span>
          </div>
          <p style="margin:0 0 8px;font-size:13px;color:#868e96;line-height:1.6;">
            Kode ini berlaku selama <strong>15 menit</strong>. Jika Anda tidak meminta reset password, abaikan email ini.
          </p>
        </td></tr>
        <tr><td style="padding:20px 40px;background:#f8f9fa;border-top:1px solid #e9ecef;text-align:center;">
          <p style="margin:0;font-size:12px;color:#adb5bd;">&copy; {$appName} &mdash; Email otomatis, jangan dibalas.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
