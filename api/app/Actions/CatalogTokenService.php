<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\CatalogRequest;
use App\Models\Vendor;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class CatalogTokenService
{
    /**
     * Genera un token de invitación para un cliente.
     *
     * @param int $customerId
     * @param int|null $sellerId sellers.id (Vendor id); si es null no se asigna vendedor
     * @param int $catalogId
     * @param string $catalogText
     * @param int|null $expirationDaysOverride Días desde el modal; si null, usa catalogsend_time de la compañía.
     * @return array ['token' => string, 'expirationDate' => string]
     */
    public function createToken(int $customerId, ?int $sellerId, int $catalogId, string $catalogText = '', ?int $expirationDaysOverride = null): array
    {
        $customer = Customer::where('customer_id', $customerId)->firstOrFail();
        $company = $customer->company;
        $dateNow = Carbon::now();

        $days = ($expirationDaysOverride !== null && $expirationDaysOverride > 0)
            ? $expirationDaysOverride
            : (int) ($company->catalogsend_time ?: 15);

        // catalog_request.user_id es FK a users.id; sellerId es sellers.id → resolver a users.id
        $userIdForRequest = null;
        if ($sellerId) {
            $vendor = Vendor::find($sellerId);
            if ($vendor && isset($vendor->user_id)) {
                $userIdForRequest = (int) $vendor->user_id;
            }
        }

        $catalogRequest = new CatalogRequest();
        $catalogRequest->customer_id = $customer->customer_id;
        $catalogRequest->user_id = $userIdForRequest;
        $catalogRequest->catalog_id = $catalogId;
        $catalogRequest->access_count = 0;
        $catalogRequest->save();

        $tokenData = [
            'catalog_request_id' => $catalogRequest->request_id,
            'customer_id'       => $customerId,
            'seller_id'         => $sellerId,
            'catalog_id'        => $catalogId,
            'catalog_text'      => $catalogText,
            'expirationDate'    => $dateNow->copy()->addDays($days)->toIso8601String(),
            'created'           => $dateNow->toIso8601String(),
        ];

        $tokenCrypt = Crypt::encryptString(json_encode($tokenData));
        $catalogRequest->token = $tokenCrypt;
        $catalogRequest->save();

        return [
            'token' => $tokenCrypt,
            'expirationDate' => $tokenData['expirationDate'],
        ];
    }
}
