<?php

namespace App\Http\Controllers\Erp\Concerns;

use Illuminate\Http\Request;

trait ResolvesErpCompany
{
    protected function companyId(Request $request): int
    {
        $id = (int) ($request->input('company_id')
            ?? $request->query('company_id')
            ?? $request->header('X-Company-Id')
            ?? 0);

        return max(0, $id);
    }

    protected function requireCompanyId(Request $request): int
    {
        $id = $this->companyId($request);
        if ($id <= 0) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'status' => '0',
                    'success' => false,
                    'error' => true,
                    'message' => 'company_id is required',
                ], 422)
            );
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function erpOk(array $data = [], string $message = 'OK', int $status = 200)
    {
        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function erpFail(string $message, int $status = 422, ?array $data = null)
    {
        return response()->json([
            'status' => '0',
            'success' => false,
            'error' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
