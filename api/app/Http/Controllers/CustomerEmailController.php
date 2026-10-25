<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Services\PlanLimitLang;
use App\Support\CompanyRequestLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerEmailController extends Controller
{
    /**
     * Lista correos del cliente. Si no hay filas y el cliente tiene email, lo sincroniza como primario.
     * No se llama index() para no chocar con Controller::index().
     */
    public function listForCustomer(Customer $customer): JsonResponse
    {
        $this->ensurePrimaryFromCustomerColumn($customer);

        $emails = CustomerEmail::query()
            ->where('customer_id', $customer->customer_id)
            ->orderByDesc('is_primary')
            ->orderBy('email')
            ->get();

        return response()->json(['data' => $emails]);
    }

    public function storeForCustomer(Request $request, Customer $customer): JsonResponse
    {
        $languageId = $this->applyCompanyLocale($customer);

        $validated = $request->validate(
            [
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('customer_emails', 'email')->where(
                        static fn ($q) => $q->where('customer_id', $customer->customer_id)
                    ),
                ],
                'is_primary' => ['nullable', 'boolean'],
            ],
            $this->emailValidationMessages($languageId)
        );

        $email = mb_strtolower(trim((string) $validated['email']), 'UTF-8');
        if ($this->emailUsedByOtherCustomer($customer, $email)) {
            throw ValidationException::withMessages([
                'email' => [$this->langCliente($languageId, 'email_already_in_use')],
            ]);
        }

        $makePrimary = (bool) ($validated['is_primary'] ?? false);
        $hasAny = CustomerEmail::query()->where('customer_id', $customer->customer_id)->exists();
        if (!$hasAny) {
            $makePrimary = true;
        }

        $row = DB::transaction(function () use ($customer, $email, $makePrimary) {
            if ($makePrimary) {
                CustomerEmail::query()
                    ->where('customer_id', $customer->customer_id)
                    ->update(['is_primary' => false]);
            }

            $row = CustomerEmail::query()->create([
                'customer_id' => $customer->customer_id,
                'email' => $email,
                'is_primary' => $makePrimary,
            ]);

            if ($makePrimary) {
                $this->syncCustomerPrimaryEmail($customer, $email);
            }

            return $row;
        });

        return response()->json(['data' => $row], Response::HTTP_CREATED);
    }

    public function updateForCustomer(Request $request, Customer $customer, CustomerEmail $customerEmail): JsonResponse
    {
        $this->assertEmailBelongsToCustomer($customer, $customerEmail);
        $languageId = $this->applyCompanyLocale($customer);

        $validated = $request->validate(
            [
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('customer_emails', 'email')
                        ->where(static fn ($q) => $q->where('customer_id', $customer->customer_id))
                        ->ignore($customerEmail->id),
                ],
                'is_primary' => ['nullable', 'boolean'],
            ],
            $this->emailValidationMessages($languageId)
        );

        $email = mb_strtolower(trim((string) $validated['email']), 'UTF-8');
        if ($this->emailUsedByOtherCustomer($customer, $email, $customerEmail->id)) {
            throw ValidationException::withMessages([
                'email' => [$this->langCliente($languageId, 'email_already_in_use')],
            ]);
        }

        $makePrimary = array_key_exists('is_primary', $validated)
            ? (bool) $validated['is_primary']
            : (bool) $customerEmail->is_primary;

        $row = DB::transaction(function () use ($customer, $customerEmail, $email, $makePrimary) {
            if ($makePrimary) {
                CustomerEmail::query()
                    ->where('customer_id', $customer->customer_id)
                    ->where('id', '!=', $customerEmail->id)
                    ->update(['is_primary' => false]);
            }

            $wasPrimary = (bool) $customerEmail->is_primary;
            $customerEmail->email = $email;
            $customerEmail->is_primary = $makePrimary;
            $customerEmail->save();

            if ($makePrimary) {
                $this->syncCustomerPrimaryEmail($customer, $email);
            } elseif ($wasPrimary) {
                $this->ensureOnePrimary($customer);
            }

            return $customerEmail->fresh();
        });

        return response()->json(['data' => $row]);
    }

    public function destroyForCustomer(Customer $customer, CustomerEmail $customerEmail): JsonResponse
    {
        $this->assertEmailBelongsToCustomer($customer, $customerEmail);

        DB::transaction(function () use ($customer, $customerEmail) {
            $wasPrimary = (bool) $customerEmail->is_primary;
            $customerEmail->delete();

            if ($wasPrimary) {
                $this->ensureOnePrimary($customer);
            }
        });

        return response()->json(['message' => 'ok']);
    }

    public function setPrimary(Customer $customer, CustomerEmail $customerEmail): JsonResponse
    {
        $this->assertEmailBelongsToCustomer($customer, $customerEmail);

        DB::transaction(function () use ($customer, $customerEmail) {
            CustomerEmail::query()
                ->where('customer_id', $customer->customer_id)
                ->update(['is_primary' => false]);

            $customerEmail->is_primary = true;
            $customerEmail->save();

            $this->syncCustomerPrimaryEmail($customer, (string) $customerEmail->email);
        });

        return response()->json(['data' => $customerEmail->fresh()]);
    }

    private function applyCompanyLocale(Customer $customer): int
    {
        return CompanyRequestLocale::applyFromCompanyId((int) $customer->company_id);
    }

    /**
     * @return array<string, string>
     */
    private function emailValidationMessages(int $languageId): array
    {
        return [
            'email.required' => $this->langCliente($languageId, 'email_invalid_format'),
            'email.email' => $this->langCliente($languageId, 'email_invalid_format'),
            'email.unique' => $this->langCliente($languageId, 'emails_already_registered'),
        ];
    }

    private function langCliente(int $languageId, string $key): string
    {
        $msg = PlanLimitLang::get($languageId, 'cmp-cliente', $key);
        if ($msg !== $key) {
            return $msg;
        }

        return PlanLimitLang::get($languageId, 'cmp-clientes', $key);
    }

    private function assertEmailBelongsToCustomer(Customer $customer, CustomerEmail $customerEmail): void
    {
        if ((int) $customerEmail->customer_id !== (int) $customer->customer_id) {
            abort(404);
        }
    }

    private function syncCustomerPrimaryEmail(Customer $customer, string $email): void
    {
        $customer->email = $email;
        $customer->save();
    }

    private function ensureOnePrimary(Customer $customer): void
    {
        $next = CustomerEmail::query()
            ->where('customer_id', $customer->customer_id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        if ($next === null) {
            $customer->email = '';
            $customer->save();

            return;
        }

        if (! $next->is_primary) {
            $next->is_primary = true;
            $next->save();
        }

        $this->syncCustomerPrimaryEmail($customer, (string) $next->email);
    }

    /**
     * Migra el email de la columna customers.email a customer_emails si aún no hay filas.
     */
    private function ensurePrimaryFromCustomerColumn(Customer $customer): void
    {
        $hasRows = CustomerEmail::query()->where('customer_id', $customer->customer_id)->exists();
        if ($hasRows) {
            return;
        }

        $email = trim((string) ($customer->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        CustomerEmail::query()->create([
            'customer_id' => $customer->customer_id,
            'email' => mb_strtolower($email, 'UTF-8'),
            'is_primary' => true,
        ]);
    }

    private function emailUsedByOtherCustomer(Customer $customer, string $email, ?int $ignoreId = null): bool
    {
        $companyId = (int) $customer->company_id;

        $inColumn = Customer::query()
            ->where('company_id', $companyId)
            ->where('customer_id', '!=', $customer->customer_id)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->exists();

        if ($inColumn) {
            return true;
        }

        $query = CustomerEmail::query()
            ->whereHas('customer', static function ($q) use ($companyId, $customer) {
                $q->where('company_id', $companyId)
                    ->where('customer_id', '!=', $customer->customer_id);
            })
            ->whereRaw('LOWER(email) = ?', [$email]);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
