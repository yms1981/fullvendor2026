<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticación legacy del marketplace MVC (tabla customers.password).
 */
final class MarketplaceCustomerAuthService
{
    public function __construct(
        private readonly CustomerPortalSessionCartService $sessionCart
    ) {
    }

    /**
     * @return Collection<int, object>
     */
    public function lookupActiveCustomersByEmail(string $email): Collection
    {
        $email = trim($email);
        if ($email === '') {
            return collect();
        }

        return Customer::query()
            ->join('companies as com', 'customers.company_id', '=', 'com.company_id')
            ->where('customers.email', $email)
            ->where('customers.customer_status', 1)
            ->select([
                'com.company_id',
                'customers.password',
                'com.name as company_name',
                'com.language_id as lang',
                'com.image',
                'com.small_image',
                'customers.email',
                'customers.name',
                'customers.business_name',
                'customers.customer_id',
                'com.status as company_status',
                'customers.customer_status',
            ])
            ->get()
            ->map(static fn ($row) => (object) $row->getAttributes());
    }

    public function findCompany(int $companyId): ?Company
    {
        return $companyId > 0 ? Company::query()->find($companyId) : null;
    }

    public function findCustomer(int $companyId, int $customerId): ?Customer
    {
        if ($companyId < 1 || $customerId < 1) {
            return null;
        }

        return Customer::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function signIn(string $email, string $password, ?int $companyId, ?int $customerId): array
    {
        $matches = $this->lookupActiveCustomersByEmail($email);
        if ($matches->isEmpty()) {
            return ['result' => 'email_not_found'];
        }

        if ($matches->count() > 1) {
            if ($companyId === null || $customerId === null) {
                return [
                    'result' => 'selection_required',
                    'customers' => $this->mapCustomersForSelection($matches),
                    'companies' => $this->mapCompaniesForSelection($matches),
                ];
            }

            $company = $this->findCompany($companyId);
            $customer = $this->findCustomer($companyId, $customerId);
            if ($company === null) {
                return ['result' => 'company_not_found'];
            }
            if (! (int) $company->status) {
                return ['result' => 'company_disabled'];
            }
            if ($customer === null || (int) $customer->customer_status !== 1) {
                return ['result' => 'customer_not_found'];
            }
        } else {
            $row = $matches->first();
            $company = $this->findCompany((int) $row->company_id);
            $customer = $this->findCustomer((int) $row->company_id, (int) $row->customer_id);
        }

        if ($company === null || $customer === null) {
            return ['result' => 'customer_not_found'];
        }

        if (! Hash::check($password, (string) ($customer->password ?? ''))) {
            return [
                'result' => 'invalid_password',
                'repeat_customer' => $matches->count() === 1,
            ];
        }

        $session = $this->sessionCart->ensure((int) $company->company_id, (int) $customer->customer_id);

        return [
            'result' => 'success',
            'repeat_customer' => $matches->count() === 1,
            'company_id' => (int) $company->company_id,
            'customer_id' => (int) $customer->customer_id,
            'language_id' => (int) ($company->language_id ?? 1),
            'session_token' => $session['session_token'],
        ];
    }

    public function updatePassword(int $companyId, int $customerId, string $plainPassword): bool
    {
        $customer = $this->findCustomer($companyId, $customerId);
        if ($customer === null) {
            return false;
        }

        $customer->password = Hash::make($plainPassword);
        $customer->save();

        return true;
    }

    public function resetPassword(int $companyId, int $customerId, int $length = 6): ?string
    {
        $customer = $this->findCustomer($companyId, $customerId);
        if ($customer === null) {
            return null;
        }

        $plain = $this->generatePlainPassword($length);
        $customer->password = Hash::make($plain);
        $customer->save();

        return $plain;
    }

    /**
     * @param Collection<int, object> $matches
     * @return list<array<string, mixed>>
     */
    private function mapCustomersForSelection(Collection $matches): array
    {
        $out = [];
        foreach ($matches as $row) {
            $out[] = [
                'name' => (string) ($row->name ?? ''),
                'id' => (int) $row->customer_id,
                'business_name' => (string) ($row->business_name ?? ''),
                'company_image' => (string) ($row->image ?? ''),
                'company_name' => (string) ($row->company_name ?? ''),
                'id_comp' => (int) $row->company_id,
                'lang' => (int) ($row->lang ?? 1),
            ];
        }

        return $out;
    }

    /**
     * @param Collection<int, object> $matches
     * @return list<array<string, mixed>>
     */
    private function mapCompaniesForSelection(Collection $matches): array
    {
        $seen = [];
        $out = [];
        foreach ($matches as $row) {
            $companyId = (int) $row->company_id;
            if (isset($seen[$companyId])) {
                continue;
            }
            $seen[$companyId] = true;
            $out[] = [
                'company_name' => (string) ($row->company_name ?? ''),
                'company_id' => $companyId,
                'image' => (string) ($row->image ?? ''),
                'small_image' => (string) ($row->small_image ?? ''),
            ];
        }

        usort($out, static fn ($a, $b) => strcmp((string) $a['company_name'], (string) $b['company_name']));

        return $out;
    }

    private function generatePlainPassword(int $length): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = strlen($chars) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }
}
