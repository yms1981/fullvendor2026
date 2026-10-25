<?php

namespace App\Http\Controllers;

use App\Services\MarketplaceCustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceCustomerController extends Controller
{
    public function lookupByEmail(Request $request, MarketplaceCustomerAuthService $auth): JsonResponse
    {
        $email = trim((string) $request->input('email', $request->input('username', '')));
        if ($email === '') {
            return response()->json(['message' => 'email_required', 'error' => true], 422);
        }

        return response()->json([
            'data' => [
                'customers' => $auth->lookupActiveCustomersByEmail($email)->values()->all(),
            ],
        ]);
    }

    public function signIn(Request $request, MarketplaceCustomerAuthService $auth): JsonResponse
    {
        $email = trim((string) $request->input('username', $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $companyId = $request->input('company');
        $customerId = $request->input('customer');

        if ($email === '' || $password === '') {
            return response()->json(['message' => 'credentials_required', 'error' => true], 422);
        }

        $result = $auth->signIn(
            $email,
            $password,
            $companyId !== null && $companyId !== '' ? (int) $companyId : null,
            $customerId !== null && $customerId !== '' ? (int) $customerId : null
        );

        return response()->json(['data' => $result]);
    }

    public function registerPassword(Request $request, MarketplaceCustomerAuthService $auth): JsonResponse
    {
        $companyId = (int) $request->input('company', 0);
        $customerId = (int) $request->input('customer', 0);
        $password = (string) $request->input('password', '');

        if ($companyId < 1 || $customerId < 1 || $password === '') {
            return response()->json(['message' => 'invalid_payload', 'error' => true], 422);
        }

        if (! $auth->updatePassword($companyId, $customerId, $password)) {
            return response()->json(['message' => 'customer_not_found', 'error' => true], 404);
        }

        return response()->json(['data' => ['updated' => true]]);
    }

    public function resetPassword(Request $request, MarketplaceCustomerAuthService $auth): JsonResponse
    {
        $companyId = (int) $request->input('company', 0);
        $customerId = (int) $request->input('customer', 0);

        if ($companyId < 1 || $customerId < 1) {
            return response()->json(['message' => 'invalid_payload', 'error' => true], 422);
        }

        $plain = $auth->resetPassword($companyId, $customerId);
        if ($plain === null) {
            return response()->json(['message' => 'customer_not_found', 'error' => true], 404);
        }

        return response()->json(['data' => ['password' => $plain]]);
    }

    public function company(int $companyId, MarketplaceCustomerAuthService $auth): JsonResponse
    {
        $company = $auth->findCompany($companyId);

        return $company === null
            ? response()->json(['message' => 'not_found', 'error' => true], 404)
            : response()->json(['data' => $company]);
    }

    public function customer(int $companyId, int $customerId, MarketplaceCustomerAuthService $auth): JsonResponse
    {
        $customer = $auth->findCustomer($companyId, $customerId);

        return $customer === null
            ? response()->json(['message' => 'not_found', 'error' => true], 404)
            : response()->json(['data' => $customer]);
    }
}
