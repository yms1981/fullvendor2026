<?php

namespace App\Http\Controllers\Orders;

use App\Actions\CatalogTokenService;
use App\Actions\SendCatalogService;
use App\Http\Requests\InvitationTokenRequest;
use App\Models\CatalogRequest;
use App\Models\Customer;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

trait ManagesInvitationsToken
{
    /**
     * @param string $encryptedToken
     * @return array
     */
    protected function getInvitationFromToken($encryptedToken): mixed
    {
        return json_decode(Crypt::decryptString($encryptedToken), true);
    }

    function createInvitationToken(InvitationTokenRequest $request, CatalogTokenService $tokenService, SendCatalogService $sendCatalogService)
    {
        $customerId = (int) $request->get('customer_id');
        $catalogId = (int) $request->get('catalog_id');
        $sellerId = $request->get('seller_id') !== null && $request->get('seller_id') !== ''
            ? (int) $request->get('seller_id')
            : null;
        $catalogText = is_string($request->get('catalog_text')) ? $request->get('catalog_text') : '';

        $result = $tokenService->createToken(
            $customerId,
            $sellerId,
            $catalogId,
            $catalogText
        );

        $customer = Customer::query()->with('company')->findOrFail($customerId);
        $company = $customer->company;
        $seller = $sellerId ? Vendor::query()->find($sellerId) : null;

        if ($company !== null) {
            $sendCatalogService->ensureCatalogCartRow(
                $company,
                $customer,
                $catalogId,
                (string) ($result['token'] ?? ''),
                (string) ($result['expirationDate'] ?? ''),
                $catalogText,
                $seller
            );
        }

        return response()->json($result);
    }
}
