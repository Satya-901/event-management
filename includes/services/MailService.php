<?php
/**
 * Utsavam MailService
 * PHPMailer integration with graceful offline/dev fallback and audit logging.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage/DataStoreFactory.php';

class MailService {
    public static function sendBookingConfirmation(array $booking, array $event): bool {
        $customerName = $booking['customer_name'];
        $customerEmail = $booking['email'];
        $bookingNumber = $booking['booking_number'];
        $eventName = $event['name'];
        $eventDate = formatDate($event['start_date']) . ' at ' . formatTime($event['start_time']);
        $venue = $event['venue_name'] . ', ' . $event['city'];
        $qrToken = $booking['qr_token'];
        $verifyUrl = appUrl('/verify/' . $qrToken);

        $subject = "Your Pass for {$eventName} - Booking #{$bookingNumber}";

        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #eaeaea; border-radius: 8px; overflow: hidden;'>
            <div style='background: linear-gradient(135deg, #7c2d12, #c2410c); padding: 25px; text-align: center; color: #ffffff;'>
                <h1 style='margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 1px;'>UTSAVAM</h1>
                <p style='margin: 5px 0 0; opacity: 0.9; font-size: 13px;'>A place for celebrations and events</p>
            </div>
            
            <div style='padding: 25px;'>
                <h2 style='color: #1f2937; margin-top: 0;'>Booking Confirmed! 🎉</h2>
                <p>Namaste <strong>" . e($customerName) . "</strong>,</p>
                <p>Your booking for <strong>" . e($eventName) . "</strong> has been confirmed. Below are your official event pass details:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr style='border-bottom: 1px solid #f3f4f6;'>
                        <td style='padding: 8px 0; color: #6b7280;'>Booking Number:</td>
                        <td style='padding: 8px 0; font-weight: bold; color: #111827;'>" . e($bookingNumber) . "</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f3f4f6;'>
                        <td style='padding: 8px 0; color: #6b7280;'>Passes:</td>
                        <td style='padding: 8px 0; font-weight: bold; color: #111827;'>" . (int)($booking['pass_count'] ?? 1) . " Entry Pass(es)</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f3f4f6;'>
                        <td style='padding: 8px 0; color: #6b7280;'>Date & Time:</td>
                        <td style='padding: 8px 0; font-weight: bold; color: #111827;'>" . e($eventDate) . "</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f3f4f6;'>
                        <td style='padding: 8px 0; color: #6b7280;'>Venue:</td>
                        <td style='padding: 8px 0; font-weight: bold; color: #111827;'>" . e($venue) . "</td>
                    </tr>
                </table>

                <div style='text-align: center; margin: 30px 0; padding: 20px; background: #fff7ed; border-radius: 8px; border: 1px dashed #fdba74;'>
                    <p style='margin: 0 0 10px; font-size: 14px; font-weight: 600; color: #9a3412;'>Your Digital QR Pass</p>
                    <p style='margin: 0 0 15px; font-size: 12px; color: #7c2d12;'>Show this QR code at the venue gate for direct check-in</p>
                    <div style='margin-bottom: 15px;'>
                        <img src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($verifyUrl) . "' alt='Pass QR Code' style='display:inline-block; border-radius: 6px; border: 2px solid #ea580c;' />
                    </div>
                    <a href='" . e($verifyUrl) . "' style='background: #ea580c; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>View & Download Pass</a>
                </div>

                <div style='background: #f9fafb; padding: 15px; border-radius: 6px; font-size: 13px; color: #4b5563;'>
                    <strong>Important Instructions:</strong>
                    <ul style='margin: 8px 0 0; padding-left: 20px;'>
                        <li>Please carry a valid photo ID matching the pass name.</li>
                        <li>Entry gates open 45 minutes prior to event start time.</li>
                        <li>This QR pass is strictly single-use and non-transferable.</li>
                    </ul>
                </div>
            </div>

            <div style='background: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #9ca3af;'>
                Sent via Utsavam Platform • Questions? Contact " . e($event['contact_email'] ?? 'support@utsavam.com') . "
            </div>
        </div>";

        // Record in notifications log for delivery tracking & audit
        self::logNotification([
            'to_email' => $customerEmail,
            'customer_name' => $customerName,
            'subject' => $subject,
            'booking_id' => $booking['id'],
            'status' => (!empty(MAIL_HOST) && !empty(MAIL_USERNAME)) ? 'queued' : 'saved_locally',
            'html_body' => $htmlBody,
            'sent_at' => date('Y-m-d H:i:s')
        ]);

        // If SMTP configured, dispatch via PHPMailer
        if (!empty(MAIL_HOST) && !empty(MAIL_USERNAME)) {
            return self::sendSmtpEmail($customerEmail, $customerName, $subject, $htmlBody);
        }

        return true;
    }

    protected static function sendSmtpEmail(string $toEmail, string $toName, string $subject, string $html): bool {
        // Standalone PHPMailer loader
        if (file_exists(__DIR__ . '/../phpmailer/PHPMailer.php')) {
            require_once __DIR__ . '/../phpmailer/Exception.php';
            require_once __DIR__ . '/../phpmailer/PHPMailer.php';
            require_once __DIR__ . '/../phpmailer/SMTP.php';

            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = MAIL_PORT;

                $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
                $mail->addAddress($toEmail, $toName);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $html;

                return $mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer error: " . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    protected static function logNotification(array $data): void {
        $file = JSON_STORAGE_PATH . '/notifications.json';
        $notifications = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $notifications = json_decode($content, true) ?: [];
        }
        $data['id'] = generateId('notif');
        $notifications[] = $data;
        file_put_contents($file, json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
