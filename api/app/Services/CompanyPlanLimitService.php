<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\Order;
use App\Models\StatusOrder;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Límites de creación según companies.max_number_of_* (sincronizados desde el plan).
 * Las órdenes se cuentan dentro de la vigencia de la suscripción activa (no por mes calendario).
 */
final class CompanyPlanLimitService
{
    /**
     * @return array{
     *   customers: array{current: int, max: int, can_create: bool},
     *   orders_month: array{current: int, max: int, can_create: bool, period_start?: string|null, period_end?: string|null},
     *   sellers: array{current: int, max: int, can_create: bool}
     * }
     */
    public function usage(int $companyId): array
    {
        $company = Company::query()->find($companyId);

        $maxCustomers = $company ? (int) ($company->max_number_of_clients ?? 0) : 0;
        $maxOrders = $company ? (int) ($company->max_number_of_orders ?? 0) : 0;
        $maxSellers = $company ? (int) ($company->max_number_of_vendors ?? 0) : 0;

        [$periodStart, $periodEnd] = $this->resolveSubscriptionPeriod($companyId);

        $currentCustomers = $this->countCustomers($companyId);
        $currentOrders = $this->countOrdersInPeriod($companyId, $periodStart, $periodEnd);
        $currentUsers = $this->countCompanyUsers($companyId);

        $orderMetric = $this->metric($currentOrders, $maxOrders);
        $orderMetric['period_start'] = $periodStart?->toDateTimeString();
        $orderMetric['period_end'] = $periodEnd?->toDateTimeString();

        return [
            'customers' => $this->metric($currentCustomers, $maxCustomers),
            'orders_month' => $orderMetric,
            'sellers' => $this->metric($currentUsers, $maxSellers),
        ];
    }

    public function assertCanCreateCustomer(int $companyId): ?JsonResponse
    {
        return $this->assertMetric($companyId, 'customers', 'cmp-cliente', 'plan_limit_create_blocked');
    }

    public function assertCanCreateOrder(int $companyId): ?JsonResponse
    {
        return $this->assertMetric($companyId, 'orders_month', 'cmp-orders', 'plan_limit_create_blocked');
    }

    public function assertCanCreateSeller(int $companyId): ?JsonResponse
    {
        return $this->assertCanCreateUser($companyId);
    }

    public function assertCanCreateUser(int $companyId): ?JsonResponse
    {
        return $this->assertMetric($companyId, 'sellers', 'cmp-user', 'plan_limit_create_blocked');
    }

    private function assertMetric(int $companyId, string $metricKey, string $langSection, string $langKey): ?JsonResponse
    {
        $usage = $this->usage($companyId);
        $metric = $usage[$metricKey] ?? ['can_create' => true];

        if ($metric['can_create'] ?? true) {
            return null;
        }

        $languageId = (int) (Company::query()->whereKey($companyId)->value('language_id') ?? 1);
        $message = PlanLimitLang::format(
            $languageId,
            $langSection,
            $langKey,
            (int) ($metric['current'] ?? 0),
            (int) ($metric['max'] ?? 0),
        );

        return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return array{current: int, max: int, can_create: bool}
     */
    private function metric(int $current, int $max): array
    {
        $canCreate = $max <= 0 || $current < $max;

        return [
            'current' => $current,
            'max' => $max,
            'can_create' => $canCreate,
        ];
    }

    private function countCustomers(int $companyId): int
    {
        return (int) Customer::query()
            ->where('company_id', $companyId)
            ->count();
    }

    private function countOrdersInPeriod(int $companyId, ?Carbon $periodStart, ?Carbon $periodEnd): int
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'created')) {
            return 0;
        }

        $query = Order::query()->where('company_id', $companyId);

        if ($periodStart instanceof Carbon) {
            $query->where('created', '>=', $periodStart->toDateTimeString());
        }
        if ($periodEnd instanceof Carbon) {
            $query->where('created', '<', $periodEnd->toDateTimeString());
        }

        $shoppingStatusId = $this->shoppingStatusId();
        if ($shoppingStatusId !== null) {
            $query->where(function ($q) use ($shoppingStatusId) {
                $q->whereNull('status_id')->orWhere('status_id', '!=', $shoppingStatusId);
            });
        }

        return (int) $query->count();
    }

    /**
     * Cachea el status_id que representa SHOPPING para excluir carritos abiertos del conteo.
     */
    private function shoppingStatusId(): ?int
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached ?: null;
        }
        try {
            $id = StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING);
            $cached = (int) $id;
        } catch (\Throwable $e) {
            $cached = 0;
        }
        return $cached ?: null;
    }

    /**
     * Resuelve la ventana de vigencia de la suscripción activa.
     * Prioridad: Stripe current_period_* → start_period_date / end_period_date.
     * Sin suscripción activa: [null, null] (cuenta todas las órdenes acumuladas).
     *
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function resolveSubscriptionPeriod(int $companyId): array
    {
        if (! Schema::hasTable('subscriptions')) {
            return [null, null];
        }

        $subscription = Subscription::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->orderByDesc('subscription_id')
            ->first();

        if ($subscription === null) {
            return [null, null];
        }

        $start = null;
        $end = null;

        if (! empty($subscription->stripe_current_period_start)) {
            $start = Carbon::parse((string) $subscription->stripe_current_period_start);
        } elseif (! empty($subscription->start_period_date)) {
            $start = Carbon::parse((string) $subscription->start_period_date)->startOfDay();
        }

        if (! empty($subscription->stripe_current_period_end)) {
            $end = Carbon::parse((string) $subscription->stripe_current_period_end);
        } elseif (! empty($subscription->end_period_date)) {
            $end = Carbon::parse((string) $subscription->end_period_date)->endOfDay()->addSecond();
        }

        return [$start, $end];
    }

    /**
     * Cuenta usuarios de la empresa (manager + seller + warehouse + customer-user).
     * Toma como fuente la tabla pivote company_user.
     */
    private function countCompanyUsers(int $companyId): int
    {
        return (int) CompanyUser::query()
            ->where('company_id', $companyId)
            ->count();
    }
}
