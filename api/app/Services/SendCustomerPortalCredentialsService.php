<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Country;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envía al usuario portal: link ecommerce (si hay), Customer Portal (/customer), email y password.
 * Canales: email | sms | whatsapp (Telecom).
 */
class SendCustomerPortalCredentialsService
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    /** @var list<string> */
    public const CHANNELS = [
        self::CHANNEL_EMAIL,
        self::CHANNEL_SMS,
        self::CHANNEL_WHATSAPP,
    ];

    public function __construct(
        private readonly TelecomMessagingClient $telecom,
        private readonly EnsureCustomerPortalUserService $ensurePortalUser,
    ) {}

    /**
     * @param  array{phonecode?:?string, phone?:?string}  $hints  Prefijo/teléfono E.164 del UI (fallback)
     * @return array{
     *   channel:string,
     *   email:string,
     *   phone:?string,
     *   ecommerce_url:?string,
     *   customer_portal_url:string
     * }
     */
    public function send(
        Customer $customer,
        User $user,
        string $channel = self::CHANNEL_EMAIL,
        array $hints = []
    ): array {
        $channel = strtolower(trim($channel));
        if (! in_array($channel, self::CHANNELS, true)) {
            $channel = self::CHANNEL_EMAIL;
        }

        $email = mb_strtolower(trim((string) ($user->email ?? '')), 'UTF-8');
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('portal_credentials_no_email');
        }

        // Asegurar vínculo + password (vacío → 12345678).
        $ensured = $this->ensurePortalUser->ensure($customer);
        if ($ensured !== null && (int) $ensured->id === (int) $user->id) {
            $user = $ensured;
        } else {
            $user->refresh();
            $plain = trim((string) ($user->password_plain ?? ''));
            $hash = trim((string) ($user->password ?? ''));
            if ($plain === '' || $hash === '') {
                app(UserPasswordService::class)->apply($user, EnsureCustomerPortalUserService::DEFAULT_PASSWORD);
                $user->save();
            }
        }

        $password = trim((string) ($user->password_plain ?? ''));
        if ($password === '') {
            $password = EnsureCustomerPortalUserService::DEFAULT_PASSWORD;
            app(UserPasswordService::class)->apply($user, $password);
            $user->save();
        }

        $company = Company::query()->find((int) $customer->company_id);
        $ecommerceUrl = null;
        if ($company && (bool) ($company->ecommerce_enabled ?? false)) {
            $ecommerceUrl = trim((string) ($company->ecommerce_url ?? ''));
            $ecommerceUrl = $ecommerceUrl !== '' ? rtrim($ecommerceUrl, '/') : null;
        }

        $customerPortalUrl = $this->resolveWebBase().'/customer';

        $name = trim((string) ($user->name ?? ''));
        if ($name === '') {
            $name = trim((string) ($customer->name ?? $customer->business_name ?? ''));
        }

        $lang = $this->cmpClienteMessages();

        if (! $this->telecom->isConfigured()) {
            Log::warning('SendCustomerPortalCredentialsService: telecom not configured');
            throw new RuntimeException('portal_credentials_send_failed');
        }

        $companyId = (int) ($customer->company_id ?? 0) ?: null;
        $phone = null;

        if ($channel === self::CHANNEL_EMAIL) {
            $subject = (string) ($lang['portal_credentials_email_subject'] ?? 'Portal access');
            $html = $this->buildHtml($lang, $name, $email, $password, $ecommerceUrl, $customerPortalUrl);
            $this->telecom->sendEmail([$email], $subject, $html, true, $companyId);
        } else {
            $hintPhonecode = $this->telecom->normalizePhone((string) ($hints['phonecode'] ?? ''));
            $hintPhone = $this->telecom->normalizePhone((string) ($hints['phone'] ?? ''));
            $phone = $this->resolveDestinationPhone($customer, $user, $company, $hintPhonecode, $hintPhone);
            if ($phone === '') {
                throw new RuntimeException('portal_credentials_no_phone');
            }
            $text = $this->buildPlainText($lang, $name, $email, $password, $ecommerceUrl, $customerPortalUrl);
            if ($channel === self::CHANNEL_SMS) {
                $this->telecom->sendSms($phone, $text, $companyId);
            } else {
                $this->telecom->sendWhatsApp($phone, $text, $companyId);
            }
        }

        return [
            'channel' => $channel,
            'email' => $email,
            'phone' => $phone,
            'ecommerce_url' => $ecommerceUrl,
            'customer_portal_url' => $customerPortalUrl,
        ];
    }

    /**
     * @param array<string, string> $lang
     */
    private function buildHtml(
        array $lang,
        string $name,
        string $email,
        string $password,
        ?string $ecommerceUrl,
        string $customerPortalUrl
    ): string {
        $greeting = str_replace(
            ':name',
            htmlspecialchars($name !== '' ? $name : $email, ENT_QUOTES, 'UTF-8'),
            (string) ($lang['portal_credentials_email_greeting'] ?? 'Hello :name,')
        );
        $intro = htmlspecialchars((string) ($lang['portal_credentials_email_intro'] ?? ''), ENT_QUOTES, 'UTF-8');
        $labelEmail = htmlspecialchars((string) ($lang['email'] ?? 'Email'), ENT_QUOTES, 'UTF-8');
        $labelPassword = htmlspecialchars((string) ($lang['password'] ?? 'Password'), ENT_QUOTES, 'UTF-8');
        $labelEcommerce = htmlspecialchars((string) ($lang['portal_credentials_link_ecommerce'] ?? 'ECommerce Portal'), ENT_QUOTES, 'UTF-8');
        $labelCustomer = htmlspecialchars((string) ($lang['portal_credentials_link_customer'] ?? 'Customer Portal'), ENT_QUOTES, 'UTF-8');
        $footer = htmlspecialchars((string) ($lang['portal_credentials_email_footer'] ?? ''), ENT_QUOTES, 'UTF-8');

        $linksHtml = '';
        if ($ecommerceUrl !== null && $ecommerceUrl !== '') {
            $safeEcom = htmlspecialchars($ecommerceUrl, ENT_QUOTES, 'UTF-8');
            $linksHtml .= '<p><strong>'.$labelEcommerce.':</strong><br><a href="'.$safeEcom.'">'.$safeEcom.'</a></p>';
        }
        $safeCustomer = htmlspecialchars($customerPortalUrl, ENT_QUOTES, 'UTF-8');
        $linksHtml .= '<p><strong>'.$labelCustomer.':</strong><br><a href="'.$safeCustomer.'">'.$safeCustomer.'</a></p>';

        return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;color:#222;line-height:1.45">'
            .'<p>'.$greeting.'</p>'
            .'<p>'.$intro.'</p>'
            .$linksHtml
            .'<p><strong>'.$labelEmail.':</strong> '.htmlspecialchars($email, ENT_QUOTES, 'UTF-8').'<br>'
            .'<strong>'.$labelPassword.':</strong> '.htmlspecialchars($password, ENT_QUOTES, 'UTF-8').'</p>'
            .($footer !== '' ? '<p style="color:#666;font-size:12px">'.$footer.'</p>' : '')
            .'</body></html>';
    }

    /**
     * Mensaje corto para SMS / WhatsApp.
     *
     * @param array<string, string> $lang
     */
    private function buildPlainText(
        array $lang,
        string $name,
        string $email,
        string $password,
        ?string $ecommerceUrl,
        string $customerPortalUrl
    ): string {
        $greeting = str_replace(
            ':name',
            $name !== '' ? $name : $email,
            (string) ($lang['portal_credentials_email_greeting'] ?? 'Hello :name,')
        );
        $intro = trim((string) ($lang['portal_credentials_email_intro'] ?? ''));
        $labelEmail = trim((string) ($lang['email'] ?? 'Email'));
        $labelPassword = trim((string) ($lang['password'] ?? 'Password'));
        $labelEcommerce = trim((string) ($lang['portal_credentials_link_ecommerce'] ?? 'ECommerce Portal'));
        $labelCustomer = trim((string) ($lang['portal_credentials_link_customer'] ?? 'Customer Portal'));

        $lines = array_filter([
            $greeting,
            $intro,
            $ecommerceUrl ? $labelEcommerce.': '.$ecommerceUrl : null,
            $labelCustomer.': '.$customerPortalUrl,
            $labelEmail.': '.$email,
            $labelPassword.': '.$password,
        ], static fn ($line) => is_string($line) && trim($line) !== '');

        return implode("\n", $lines);
    }

    /**
     * Teléfono E.164 en dígitos: usuario/cliente + prefijo país (obligatorio).
     * $hintPhone puede venir ya con prefijo desde el UI.
     */
    private function resolveDestinationPhone(
        Customer $customer,
        User $user,
        ?Company $company,
        string $hintPhonecode = '',
        string $hintPhone = ''
    ): string {
        $phonecode = $this->resolveCustomerPhonecode($customer, $company);
        if ($phonecode === '' && $hintPhonecode !== '') {
            $phonecode = $hintPhonecode;
        }
        if ($phonecode === '') {
            throw new RuntimeException('portal_credentials_no_phonecode');
        }

        // Si el UI envió E.164 con prefijo válido, usarlo.
        if ($hintPhone !== '') {
            if (str_starts_with($hintPhone, $phonecode) && strlen($hintPhone) > strlen($phonecode)) {
                return $hintPhone;
            }

            return $this->telecom->withCountryPrefix($hintPhone, $phonecode);
        }

        $raw = trim((string) ($user->cellphone ?? ''));
        if ($raw === '') {
            $raw = trim((string) ($user->phone ?? ''));
        }
        if ($raw === '') {
            $raw = trim((string) ($customer->cell_phone ?? ''));
        }
        if ($raw === '') {
            $raw = trim((string) ($customer->phone ?? ''));
        }

        $national = $this->telecom->normalizePhone($raw);
        if ($national === '') {
            return '';
        }

        return $this->telecom->withCountryPrefix($national, $phonecode);
    }

    private function resolveCustomerPhonecode(Customer $customer, ?Company $company = null): string
    {
        // Fuente de verdad: customers.commercial_country (evita pivot legacy con otro país).
        $countryKey = trim((string) ($customer->commercial_country ?? ''));
        $code = $this->phonecodeFromCountryRef($countryKey);
        if ($code !== '') {
            return $code;
        }

        if (! $customer->relationLoaded('commercialAddresses')) {
            $customer->loadMissing('commercialAddresses.country');
        }

        $matched = null;
        $fallback = null;
        foreach ($customer->commercialAddresses as $commercial) {
            if ($commercial->relationLoaded('country') === false) {
                $commercial->loadMissing('country');
            }
            if (! $commercial->country || (int) ($commercial->country->phonecode ?? 0) < 1) {
                continue;
            }
            $fallback ??= $commercial;
            $name = trim((string) ($commercial->country->name ?? ''));
            $sort = trim((string) ($commercial->country->sortname ?? ''));
            if ($countryKey !== '' && (
                strcasecmp($name, $countryKey) === 0
                || strcasecmp($sort, $countryKey) === 0
            )) {
                $matched = $commercial;
                break;
            }
        }

        $pick = $matched ?? (($countryKey === '') ? $fallback : null);
        if ($pick && $pick->country && (int) ($pick->country->phonecode ?? 0) > 0) {
            return (string) (int) $pick->country->phonecode;
        }

        // Compañía solo si el cliente no tiene país comercial.
        if ($countryKey === '' && $company !== null) {
            return $this->phonecodeFromCountryRef(trim((string) ($company->country ?? '')));
        }

        return '';
    }

    /**
     * Acepta id numérico, nombre o sortname del país.
     */
    private function phonecodeFromCountryRef(string $countryRef): string
    {
        if ($countryRef === '') {
            return '';
        }

        if (ctype_digit($countryRef)) {
            $byId = Country::query()->find((int) $countryRef, ['id', 'phonecode']);
            if ($byId && (int) ($byId->phonecode ?? 0) > 0) {
                return (string) (int) $byId->phonecode;
            }
        }

        $upper = function_exists('mb_strtoupper')
            ? mb_strtoupper($countryRef, 'UTF-8')
            : strtoupper($countryRef);

        $country = Country::query()
            ->where(function ($q) use ($upper) {
                $q->whereRaw('UPPER(name) = ?', [$upper])
                    ->orWhereRaw('UPPER(sortname) = ?', [$upper]);
            })
            ->first(['id', 'phonecode']);

        if ($country && (int) ($country->phonecode ?? 0) > 0) {
            return (string) (int) $country->phonecode;
        }

        return '';
    }

    /**
     * Base pública del MVC (/customer). Prefiere Origin/Referer (p. ej. http://localhost/mvc).
     */
    private function resolveWebBase(): string
    {
        $origin = trim((string) request()->headers->get('Origin', ''));
        if ($origin !== '' && filter_var($origin, FILTER_VALIDATE_URL)) {
            return rtrim($origin, '/');
        }

        $referer = trim((string) request()->headers->get('Referer', ''));
        if ($referer !== '') {
            $parts = parse_url($referer);
            if (! empty($parts['scheme']) && ! empty($parts['host'])) {
                $base = $parts['scheme'].'://'.$parts['host'];
                if (! empty($parts['port'])) {
                    $base .= ':'.$parts['port'];
                }
                $path = (string) ($parts['path'] ?? '');
                if (preg_match('#^(/[^/]+)#', $path, $m)
                    && in_array(strtolower($m[1]), ['/mvc', '/app', '/web'], true)
                ) {
                    $base .= $m[1];
                }

                return $base;
            }
        }

        return rtrim((string) config('fullvendor.web_url', config('app.url', 'http://localhost')), '/');
    }

    /**
     * @return array<string, string>
     */
    private function cmpClienteMessages(): array
    {
        $locale = strtolower((string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en')));
        $file = match (true) {
            str_contains($locale, 'es') => 'es-ES.json',
            str_contains($locale, 'pt') => 'pt-PT.json',
            str_contains($locale, 'it') => 'it-IT.json',
            str_contains($locale, 'de') => 'de-DE.json',
            str_contains($locale, 'ru') => 'ru-RU.json',
            str_contains($locale, 'zh') => 'zh-CN.json',
            default => 'en-EN.json',
        };
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data['cmp-cliente'] ?? null) ? $data['cmp-cliente'] : [];
    }
}
