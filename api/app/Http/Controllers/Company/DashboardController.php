<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\CompanyDashboardStatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Métricas del tablero company (sustituye SQL directo en DashboardModel del MVC).
     */
    public function filterData(int $companyId, Request $request, CompanyDashboardStatsService $service)
    {
        return response()->json($service->filterData($companyId, $request));
    }
}
