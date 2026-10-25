<?php

namespace App\Actions;

use App\Mail\CatalogInvitationMail;
use App\Models\CatalogRequest;
use App\Models\CatalogSentMail;
use App\Models\Company;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Catalog;
use App\Services\CompanySendTemplateService;
use App\Services\TelecomMessagingClient;
use App\Support\DisplayDateFormat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendCatalogService
{
    protected CatalogTokenService $tokenService;

    protected TelecomMessagingClient $telecom;

    protected CompanySendTemplateService $templates;

    public function __construct(
        CatalogTokenService $tokenService,
        TelecomMessagingClient $telecom,
        CompanySendTemplateService $templates
    ) {
        $this->tokenService = $tokenService;
        $this->telecom = $telecom;
        $this->templates = $templates;
    }

    /**
     * Token + catalog_cart + envío por Telecom (email / SMS / WhatsApp).
     *
     * @throws \Exception
     */
    public function execute(Company $company, Customer $customer, int $catalogId, array $request): void
    {
        $type = strtolower(trim((string) ($request['type'] ?? 'email')));
        if (! in_array($type, ['email', 'sms', 'whatsapp'], true)) {
            $type = 'email';
        }

        if (! $this->telecom->isConfigured()) {
            throw new \Exception('Telecom API is not configured (TELECOM_API_BASE_URL).');
        }

        $formatDate = DisplayDateFormat::resolvePhpFormat();
        $seller = $this->getEffectiveSeller($customer, $company);

        $catalogTitle = isset($request['catalog_title']) && is_string($request['catalog_title'])
            ? $request['catalog_title']
            : '';

        $expirationDays = isset($request['expiration_days']) ? (int) $request['expiration_days'] : null;
        if ($expirationDays !== null && $expirationDays < 1) {
            $expirationDays = null;
        }

        ['token' => $token, 'expirationDate' => $expirationDate] = $this->tokenService->createToken(
            $customer->customer_id,
            $seller?->id,
            $catalogId,
            $catalogTitle,
            $expirationDays
        );

        $this->ensureCatalogCartRow($company, $customer, $catalogId, $token, (string) $expirationDate, $catalogTitle, $seller);

        $emailTextRaw = $request['email_text'] ?? null;
        $emailText = is_string($emailTextRaw) ? $emailTextRaw : '';
        $fechaEx = date($formatDate, strtotime($expirationDate));
        $catalogLink = rtrim((string) config('fullvendor.web_url'), '/') . '/catalogo/producto?token=' . rawurlencode($token);

        $catalogName = $catalogTitle !== ''
            ? $catalogTitle
            : (string) (Catalog::query()->where('catalog_id', $catalogId)->value('catalog_name') ?? '');

        try {
            if ($type === 'email') {
                $this->deliverViaEmail(
                    $company,
                    $customer,
                    $seller,
                    $token,
                    $emailText,
                    $catalogId,
                    $fechaEx,
                    $catalogLink,
                    $catalogName
                );
            } elseif ($type === 'whatsapp') {
                $this->deliverViaWhatsApp(
                    $company,
                    $customer,
                    $seller,
                    $emailText,
                    $catalogLink,
                    $fechaEx,
                    $catalogName
                );
            } else {
                $this->deliverViaSms(
                    $company,
                    $customer,
                    $seller,
                    $emailText,
                    $catalogLink,
                    $fechaEx,
                    $catalogName
                );
            }
        } catch (\Throwable $e) {
            throw new \Exception(
                "Error enviando catálogo ({$type}) al cliente {$customer->customer_id}: " . $e->getMessage()
            );
        }

        $this->createCatalogSentMailRecord($token, $company, $customer, $catalogId);
    }

    /**
     * Envía HTML de invitación por Telecom Email API (sin Laravel Mail / PHPMailer).
     */
    public function deliverHtmlEmailViaTelecom(string $toEmail, string $subject, string $htmlBody, ?int $companyId = null): void
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '') {
            throw new \RuntimeException('Customer has no email address');
        }
        $this->telecom->sendEmail([$toEmail], $subject, $htmlBody, true, $companyId);
    }

    private function deliverViaEmail(
        Company $company,
        Customer $customer,
        ?Vendor $seller,
        string $token,
        string $emailText,
        int $catalogId,
        string $fechaEx,
        string $catalogLink,
        string $catalogName
    ): void {
        $toEmail = trim((string) ($customer->email ?? ''));
        if ($toEmail === '') {
            throw new \RuntimeException("Cliente {$customer->customer_id} no tiene email asignado.");
        }

        $rendered = $this->renderCatalogTemplate(
            $company,
            $customer,
            'email',
            $catalogName,
            $catalogLink,
            $emailText
        );

        if ($rendered !== null && trim($rendered['body']) !== '') {
            $subject = trim($rendered['subject']) !== ''
                ? $rendered['subject']
                : (trim((string) ($company->name ?? 'Catalog')) . ' Product Catalog');
            $this->telecom->sendEmail([$toEmail], $subject, $rendered['body'], true, (int) $company->company_id);

            return;
        }

        $mailable = new CatalogInvitationMail(
            token: $token,
            company: $company,
            customer: $customer,
            seller: $seller,
            emailText: $emailText,
            catalogId: $catalogId,
            fecha_ex: $fechaEx
        );

        $html = $mailable->render();
        $subject = trim((string) ($company->name ?? 'Catalog')) . ' Product Catalog';
        $this->telecom->sendEmail([$toEmail], $subject, $html, true, (int) $company->company_id);
    }

    private function deliverViaWhatsApp(
        Company $company,
        Customer $customer,
        ?Vendor $seller,
        string $emailText,
        string $catalogLink,
        string $fechaEx,
        string $catalogName
    ): void {
        $phone = $this->resolveCustomerPhone($customer, $company);
        if ($phone === '') {
            throw new \RuntimeException("Cliente {$customer->customer_id} no tiene teléfono asignado.");
        }
        $this->telecom->sendWhatsApp(
            $phone,
            $this->resolvePlainCatalogBody($company, $customer, $seller, $emailText, $catalogLink, $fechaEx, $catalogName, 'whatsapp'),
            (int) $company->company_id
        );
    }

    private function deliverViaSms(
        Company $company,
        Customer $customer,
        ?Vendor $seller,
        string $emailText,
        string $catalogLink,
        string $fechaEx,
        string $catalogName
    ): void {
        $phone = $this->resolveCustomerPhone($customer, $company);
        if ($phone === '') {
            throw new \RuntimeException("Cliente {$customer->customer_id} no tiene teléfono asignado.");
        }
        $this->telecom->sendSms(
            $phone,
            $this->resolvePlainCatalogBody($company, $customer, $seller, $emailText, $catalogLink, $fechaEx, $catalogName, 'sms'),
            (int) $company->company_id
        );
    }

    /**
     * @return array{subject: string, body: string}|null
     */
    private function renderCatalogTemplate(
        Company $company,
        Customer $customer,
        string $channel,
        string $catalogName,
        string $catalogLink,
        string $message
    ): ?array {
        $customerName = trim((string) ($customer->name ?? ''));
        if ($customerName === '') {
            $customerName = trim((string) ($customer->business_name ?? ''));
        }

        return $this->templates->render(
            (int) $company->company_id,
            'catalog',
            $channel,
            $this->templates->catalogVars($company, $customerName, $catalogName, $catalogLink, $message),
            (int) ($company->language_id ?? 0) ?: null
        );
    }

    private function resolvePlainCatalogBody(
        Company $company,
        Customer $customer,
        ?Vendor $seller,
        string $emailText,
        string $catalogLink,
        string $fechaEx,
        string $catalogName,
        string $channel
    ): string {
        $rendered = $this->renderCatalogTemplate(
            $company,
            $customer,
            $channel,
            $catalogName,
            $catalogLink,
            $emailText
        );
        if ($rendered !== null && trim($rendered['body']) !== '') {
            return trim($rendered['body']);
        }

        return $this->buildPlainMessage($company, $customer, $seller, $emailText, $catalogLink, $fechaEx);
    }

    /**
     * Mismo contenido textual que el email de invitación (catalog_invitation), en texto plano.
     */
    private function buildPlainMessage(
        Company $company,
        Customer $customer,
        ?Vendor $seller,
        string $emailText,
        string $catalogLink,
        string $fechaEx
    ): string {
        $lang = $this->loadCatalogLangTexts($company, $customer);

        $business = trim((string) ($customer->business_name ?? ''));
        $contact = trim((string) ($customer->name ?? ''));
        $customerLabel = trim($business . ($contact !== '' ? ' (' . $contact . ')' : ''));
        if ($customerLabel === '') {
            $customerLabel = $contact !== '' ? $contact : $business;
        }

        $lines = [];

        $saludo = trim((string) ($lang['saludo'] ?? ''));
        $lines[] = trim($saludo . ' ' . $customerLabel);

        if (trim($emailText) !== '') {
            $lines[] = trim($emailText);
        }

        $asiSon = trim((string) ($lang['asi_son'] ?? ''));
        if ($asiSon !== '') {
            $lines[] = $asiSon;
        }

        $podras = trim((string) ($lang['podras'] ?? ''));
        $enlace = trim((string) ($lang['enlace'] ?? ''));
        $linkIntro = trim($podras . ($enlace !== '' ? ' ' . $enlace : ''));
        if ($linkIntro !== '') {
            $lines[] = $linkIntro . ':';
        }
        $lines[] = $catalogLink;

        $gracias = trim((string) ($lang['gracias'] ?? ''));
        if ($gracias !== '') {
            $lines[] = $gracias;
        }

        if ($seller !== null) {
            $seller->loadMissing('user');
            $sellerName = trim((string) ($seller->user->name ?? ''));
            $sellerPhone = trim((string) ($seller->phone_number ?? ''));
            $sellerEmail = trim((string) ($seller->user->email ?? ''));

            $sellerLabel = trim((string) ($lang['seller'] ?? ''));
            $phoneLabel = trim((string) ($lang['phone'] ?? ''));

            $sellerBlock = [];
            if ($sellerLabel !== '' || $sellerName !== '') {
                $sellerBlock[] = trim($sellerLabel . ($sellerLabel !== '' ? ': ' : '') . $sellerName);
            }
            if ($phoneLabel !== '' || $sellerPhone !== '') {
                $sellerBlock[] = trim($phoneLabel . ($phoneLabel !== '' ? ': ' : '') . $sellerPhone);
            }
            if ($sellerEmail !== '') {
                $sellerBlock[] = 'Email: ' . $sellerEmail;
            }
            if ($sellerBlock !== []) {
                $lines[] = implode("\n", $sellerBlock);
            }
        }

        $validLabel = trim((string) ($lang['valido_hasta'] ?? ''));
        if ($fechaEx !== '') {
            $lines[] = trim($validLabel . ($validLabel !== '' ? ': ' : '') . $fechaEx);
        }

        $auto = trim((string) ($lang['mensaje_automatizado'] ?? ''));
        if ($auto !== '') {
            $lines[] = $auto;
        }

        return implode("\n\n", array_filter($lines, static fn ($p) => trim((string) $p) !== ''));
    }

    /**
     * Textos cmp-catalog según idioma de la compañía (sistema) o del cliente.
     *
     * @return array<string, string>
     */
    private function loadCatalogLangTexts(Company $company, Customer $customer): array
    {
        $languageId = (int) ($company->language_id ?? 0);
        if ($languageId <= 0) {
            $languageId = (int) ($customer->language_id ?? 0);
        }

        $defs = \App\Support\Languages\SupportedLanguages::definitions();
        $jsonFile = $defs[$languageId]['json_file'] ?? 'en-EN.json';
        $path = base_path('lang/' . $jsonFile);
        if (! is_readable($path)) {
            $path = base_path('lang/en-EN.json');
        }
        if (! is_readable($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || ! isset($decoded['cmp-catalog']) || ! is_array($decoded['cmp-catalog'])) {
            return [];
        }

        $out = [];
        foreach ($decoded['cmp-catalog'] as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * Teléfono E.164 en dígitos (prefijo país + número nacional) para SMS/WhatsApp.
     */
    private function resolveCustomerPhone(Customer $customer, ?Company $company = null): string
    {
        $raw = trim((string) ($customer->cell_phone ?? ''));
        if ($raw === '') {
            $raw = trim((string) ($customer->phone ?? ''));
        }
        $national = $this->telecom->normalizePhone($raw);
        if ($national === '') {
            return '';
        }

        $phonecode = $this->resolveCustomerPhonecode($customer, $company);
        if ($phonecode === '') {
            throw new \RuntimeException(
                "Cliente {$customer->customer_id}: falta el prefijo telefónico del país (país comercial)."
            );
        }

        return $this->telecom->withCountryPrefix($national, $phonecode);
    }

    private function resolveCustomerPhonecode(Customer $customer, ?Company $company = null): string
    {
        if (! $customer->relationLoaded('commercialAddresses')) {
            $customer->loadMissing('commercialAddresses.country');
        }

        $commercial = $customer->commercialAddresses->first();
        if ($commercial && $commercial->relationLoaded('country') === false) {
            $commercial->loadMissing('country');
        }
        if ($commercial && $commercial->country && (int) ($commercial->country->phonecode ?? 0) > 0) {
            return (string) (int) $commercial->country->phonecode;
        }

        $countryName = trim((string) ($customer->commercial_country ?? ''));
        $code = $this->phonecodeFromCountryName($countryName);
        if ($code !== '') {
            return $code;
        }

        if ($company !== null) {
            return $this->phonecodeFromCountryName(trim((string) ($company->country ?? '')));
        }

        return '';
    }

    private function phonecodeFromCountryName(string $countryName): string
    {
        if ($countryName === '') {
            return '';
        }

        $upper = function_exists('mb_strtoupper')
            ? mb_strtoupper($countryName, 'UTF-8')
            : strtoupper($countryName);

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
     * Garantiza que exista catalog_cart.token_kor para el enlace público catalogo/producto?token=…
     */
    public function ensureCatalogCartRow(
        Company $company,
        Customer $customer,
        int $catalogId,
        string $token,
        string $expirationDateIso,
        string $catalogText = '',
        ?Vendor $seller = null
    ): void {
        if ($token === '' || ! Schema::hasTable('catalog_cart')) {
            return;
        }

        if (DB::table('catalog_cart')->where('token_kor', $token)->exists()) {
            return;
        }

        $userId = ($seller !== null && isset($seller->user_id)) ? (int) $seller->user_id : null;

        try {
            $expiredAt = Carbon::parse($expirationDateIso)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            $expiredAt = Carbon::now()->addDays(15)->format('Y-m-d H:i:s');
        }

        DB::table('catalog_cart')->insert([
            'customer_id'  => $customer->customer_id,
            'created'      => now()->format('Y-m-d H:i:s'),
            'user_id'      => $userId,
            'company_id'   => $company->company_id,
            'catalog_id'   => $catalogId,
            'expired_date' => $expiredAt,
            'token_kor'    => $token,
            'order_sent'   => 1,
            'catalog_text' => $catalogText,
            'status_cart'  => 1,
            'type_cart'    => 'to_customer',
            'cart_number'  => $this->generateCatalogCartNumber(),
        ]);
    }

    private function generateCatalogCartNumber(int $length = 6): string
    {
        $characters = '123456789';
        $randomString = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }

        return $randomString;
    }

    private function getEffectiveSeller(Customer $customer, Company $company): ?Vendor
    {
        $seller = $customer->sellerAssignCatalog
            ?? $customer->sellers->first()
            ?? $company->defaultSeller;

        if ($seller) {
            $seller->loadMissing('user');
        }

        return $seller;
    }

    private function createCatalogSentMailRecord(string $token, Company $company, Customer $customer, int $catalogId): void
    {
        $catalogRequest = CatalogRequest::where('token', $token)->select(['request_id'])->sole();

        CatalogSentMail::create([
            'catlog_reqest_id' => $catalogRequest->request_id,
            'company_id'         => $company->company_id,
            'catalog_id'         => $catalogId,
            'customer_id'        => $customer->customer_id,
            'hide_price'         => 0,
        ]);
    }
}
