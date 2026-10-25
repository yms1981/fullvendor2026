<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Valida suscripción/plan y límites de companies al ingresar al panel company.
 *
 * @property CI_Controller $CI
 */
class Company_plan_validator
{
    /** @var CI_Controller */
    protected $CI;

    /** @var string|null */
    protected $subscriptionsPkCache = null;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Cadenas de api/lang/*.json → sección login-languaje.
     *
     * @return array<string, mixed>
     */
    public function loadLoginLangMessages(int $languageId): array
    {
        static $map = [
            1 => 'en-EN.json',
            2 => 'es-ES.json',
            3 => 'pt-PT.json',
            4 => 'it-IT.json',
            5 => 'ru-RU.json',
            6 => 'de-DE.json',
            7 => 'zh-CN.json',
        ];

        static $cache = [];
        if (isset($cache[$languageId])) {
            return $cache[$languageId];
        }

        $file = $map[$languageId] ?? 'en-EN.json';
        $path = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file;
        if (! is_readable($path)) {
            return $cache[$languageId] = [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $cache[$languageId] = [];
        }
        $data = json_decode($raw, true);
        if (! is_array($data) || ! isset($data['login-languaje']) || ! is_array($data['login-languaje'])) {
            return $cache[$languageId] = [];
        }

        return $cache[$languageId] = $data['login-languaje'];
    }

    /**
     * @param int $companyId
     * @param array<string, mixed> $msgs
     * @return string Mensaje de error o cadena vacía si pasa
     */
    public function validateForLogin(int $companyId, array $msgs): string
    {
        if ($companyId < 1) {
            return '';
        }

        $data = $this->fetchPlanValidationFromApi($companyId);
        if ($data === null) {
            // Si la API no responde, no bloquear el acceso (fail-open) y registrar.
            log_message('error', 'Company_plan_validator: sin respuesta de la API para company ' . $companyId);
            return '';
        }

        $blocks = [];

        if (! empty($data['has_subscription_tables'])) {
            $subscription = $data['subscription'] ?? null;
            if ($subscription === null) {
                $blocks[] = (string) ($msgs['subscription_missing']
                    ?? 'Your company has no active subscription. Please contact the administrator.');
            } else {
                $plan = $data['plan'] ?? null;
                if ($plan === null) {
                    $blocks[] = (string) ($msgs['subscription_plan_missing']
                        ?? 'The plan linked to your subscription was not found. Please contact the administrator.');
                } else {
                    if ((int) ($plan['status'] ?? 1) === 0) {
                        $blocks[] = (string) ($msgs['subscription_plan_disabled']
                            ?? 'The plan linked to your subscription is disabled. Please contact the administrator.');
                    }

                    $neverExpire = (int) ($plan['neverexpire'] ?? 0) === 1;
                    $endDate = isset($subscription['end_period_date']) ? (string) $subscription['end_period_date'] : '';
                    $today = date('Y-m-d');
                    if (! $neverExpire && $endDate !== '' && $endDate !== '0000-00-00' && $endDate < $today) {
                        $template = (string) ($msgs['subscription_expired']
                            ?? 'Your subscription expired on %s. Please renew it to continue.');
                        $blocks[] = sprintf($template, $endDate);
                    }
                }
            }
        }

        $limits = $data['limits'] ?? [];

        $maxSellers = (int) ($limits['sellers']['max'] ?? 0);
        if ($maxSellers > 0) {
            $sellersCount = (int) ($limits['sellers']['current'] ?? 0);
            if ($sellersCount > $maxSellers) {
                $template = (string) ($msgs['limit_sellers_exceeded']
                    ?? 'Sellers limit exceeded: %d active; company configuration allows up to %d.');
                $blocks[] = sprintf($template, $sellersCount, $maxSellers);
            }
        }

        $maxCustomers = (int) ($limits['customers']['max'] ?? 0);
        if ($maxCustomers > 0) {
            $customersCount = (int) ($limits['customers']['current'] ?? 0);
            if ($customersCount > $maxCustomers) {
                $template = (string) ($msgs['limit_customers_exceeded']
                    ?? 'Customers limit exceeded: %d registered; company configuration allows up to %d.');
                $blocks[] = sprintf($template, $customersCount, $maxCustomers);
            }
        }

        $maxOrders = (int) ($limits['orders_month']['max'] ?? 0);
        if ($maxOrders > 0) {
            $ordersThisMonth = (int) ($limits['orders_month']['current'] ?? 0);
            if ($ordersThisMonth > $maxOrders) {
                $template = (string) ($msgs['limit_orders_exceeded']
                    ?? 'Monthly orders limit exceeded: %d this month; company configuration allows up to %d.');
                $blocks[] = sprintf($template, $ordersThisMonth, $maxOrders);
            }
        }

        if ($blocks === []) {
            return '';
        }

        if (count($blocks) === 1) {
            return $blocks[0];
        }

        $title = (string) ($msgs['validation_limits_title']
            ?? 'Access is not allowed because the following limits were exceeded:');

        return $title . "\n\n" . implode("\n", $blocks);
    }

    /**
     * Devuelve el plan asociado a la suscripción activa de la empresa (o null), vía API.
     *
     * @return object|null
     */
    public function getActivePlanForCompany(int $companyId)
    {
        if ($companyId < 1) {
            return null;
        }
        $data = $this->fetchPlanValidationFromApi($companyId);
        if ($data === null || empty($data['plan'])) {
            return null;
        }

        return (object) $data['plan'];
    }

    /**
     * Obtiene del endpoint company/{id}/plan-validation los datos de suscripción/plan y límites.
     *
     * @return array<string, mixed>|null
     */
    protected function fetchPlanValidationFromApi(int $companyId)
    {
        if ($companyId < 1) {
            return null;
        }
        if (! is_object($this->CI) || ! method_exists($this->CI, 'getDataFromApi')) {
            return null;
        }

        try {
            $response = $this->CI->getDataFromApi("company/{$companyId}/plan-validation");
        } catch (\Throwable $e) {
            log_message('error', 'Company_plan_validator API error: ' . $e->getMessage());
            return null;
        }

        if (! is_array($response) || isset($response['error'])) {
            return null;
        }

        return $response;
    }
}
