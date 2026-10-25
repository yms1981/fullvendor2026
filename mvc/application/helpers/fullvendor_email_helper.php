<?php
require_once dirname(BASEPATH).'/vendor/autoload.php';

class FullvendorEmailHelper {

    protected $bcc = "develop@fullvendor.com";

    protected $smtp_config;

    public function __construct()
    {
        $this->smtp_config = self::getSmtpConfig();
    }

    protected static function getSmtpConfig(): array
    {
        $env = getenv('CI_ENV') ?: (defined('ENVIRONMENT') ? ENVIRONMENT : 'development');

        if ($env === 'production' || getenv('MAIL_HOST') === 'smtp.sendgrid.net') {
            $password = trim((string) (getenv('MAIL_PASSWORD') ?: ''));
            if ($password === '' || $password === '${SENDGRID_API_KEY}') {
                $password = trim((string) (getenv('SENDGRID_API_KEY') ?: ''));
            }

            return [
                'host'     => getenv('MAIL_HOST') ?: 'smtp.sendgrid.net',
                'port'     => (int) (getenv('MAIL_PORT') ?: 587),
                'auth'     => true,
                'secure'   => getenv('MAIL_ENCRYPTION') ?: 'tls',
                'username' => getenv('MAIL_USERNAME') ?: 'apikey',
                'password' => $password,
            ];
        }

        return [
            'host'     => getenv('MAIL_HOST') ?: 'mailpit',
            'port'     => (int) (getenv('MAIL_PORT') ?: 1025),
            'auth'     => filter_var(getenv('MAIL_AUTH') ?: false, FILTER_VALIDATE_BOOLEAN),
            'secure'   => getenv('MAIL_ENCRYPTION') ?: '',
            'username' => getenv('MAIL_USERNAME') ?: '',
            'password' => getenv('MAIL_PASSWORD') ?: '',
        ];
    }

    public static function sendMailFromNoReply($to, $subject, $htmlContent): bool
    {
        $instance = new static();
        $from = trim((string) (getenv('MAIL_FROM_ADDRESS') ?: ''));
        if ($from === '') {
            $from = 'info@fullvendor.com';
        }
        $fromName = trim((string) (getenv('MAIL_FROM_NAME') ?: ''));
        if ($fromName === '') {
            $fromName = 'Fullvendor';
        }

        return $instance->sendPhpmailerSmtpMail($to, $subject, $htmlContent, $from, $fromName);
    }

    /**
     * @param $to
     * @param $subject
     * @param $htmlContent
     * @param string $from
     * @param string $fromName
     * @return bool
     */
    public static function sendMail($to, $subject, $htmlContent, string $from = 'info@fullvendor.com', string $fromName = "FullVendor"): bool
    {
        $instance = new static();
        return $instance->sendPhpmailerSmtpMail($to, $subject, $htmlContent, $from, $fromName);
    }

    /**
     * @param $to
     * @param $subject
     * @param $htmlContent
     * @param string $from
     * @param string $fromName
     * @return bool
     */
    public function sendPhpmailerSmtpMail($to, $subject, $htmlContent, string $from = 'info@fullvendor.com', string $fromName = "FullVendor"): bool
    {
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mailer->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;

            $mailer->setFrom($from, $fromName);
            $mailer->addBCC($this->bcc);
            $mailer->addAddress($to);
            $mailer->isHTML(true);

            $mailer->Subject = $subject;
            $mailer->Body = $htmlContent;

            $mailer->isSMTP();
            $mailer->Host       = $this->smtp_config['host'];
            $mailer->Port       = $this->smtp_config['port'];
            $mailer->SMTPAuth   = $this->smtp_config['auth'];

            if (!empty($this->smtp_config['secure'])) {
                $mailer->SMTPSecure = $this->smtp_config['secure'];
            }

            if ($this->smtp_config['auth']) {
                $mailer->Username = $this->smtp_config['username'];
                $mailer->Password = $this->smtp_config['password'];
            }

            if (($this->smtp_config['host'] ?? '') === 'smtp.sendgrid.net') {
                $mailer->addCustomHeader(
                    'X-SMTPAPI',
                    json_encode([
                        'filters' => [
                            'clicktrack' => ['settings' => ['enable' => 0]],
                            'opentrack' => ['settings' => ['enable' => 0]],
                        ],
                    ])
                );
            }

            return $mailer->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $detail = $e->getMessage();
            if ($mailer->ErrorInfo) {
                $detail .= ' | ' . $mailer->ErrorInfo;
            }
            log_message('error', 'FullvendorEmailHelper: ' . $detail);
        }

        return false;
    }
}
