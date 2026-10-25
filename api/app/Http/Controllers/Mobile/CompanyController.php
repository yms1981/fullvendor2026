<?php

namespace App\Http\Controllers\Mobile;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends MobileController
{
    /**
     * Listar compañías del usuario
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $companies = $user->companiesForRole('manager')
            ->select('companies.company_id', 'name', 'slug')
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->company_id,
                    'name' => $company->name,
                    'slug' => $company->slug ?? null,
                ];
            });
        
        return $this->success($companies);
    }
    
    /**
     * Obtener información de una compañía
     * 
     * @param Request $request
     * @param int $companyId
     * @return JsonResponse
     */
    public function show(Request $request, int $companyId): JsonResponse
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            return $this->error('Compañía no encontrada', null, 404);
        }
        
        return $this->success([
            'id' => $company->company_id,
            'name' => $company->name,
            'slug' => $company->slug ?? null,
            'currency' => [
                'code' => $company->currencyModel?->code ?? null,
                'symbol' => $company->currencyModel?->symbol ?? null,
            ],
        ]);
    }
    
    /**
     * Obtener catálogos de una compañía
     * 
     * @param Request $request
     * @param int $companyId
     * @return JsonResponse
     */
    public function catalogs(Request $request, int $companyId): JsonResponse
    {
        // Implementar lógica para obtener catálogos
        return $this->success([]);
    }
    
    /**
     * Obtener un catálogo específico
     * 
     * @param Request $request
     * @param int $companyId
     * @param int $catalogId
     * @return JsonResponse
     */
    public function catalog(Request $request, int $companyId, int $catalogId): JsonResponse
    {
        // Implementar lógica para obtener catálogo
        return $this->success([]);
    }
}

