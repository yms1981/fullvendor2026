<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CompanyDashboardCatalogStatsService
{
    /**
     * Cuenta catálogos enviados a clientes (equivalente a DashboardModel::send_catalogs / send_catalogs_bystatus).
     */
    public function sendCatalogCount(int $companyId, Request $request): array
    {
        if (! Schema::hasTable('catalog_cart')) {
            return ['total_send_catalog' => 0];
        }

        $query = DB::table('catalog_cart as c')
            ->where('c.type_cart', 'to_customer')
            ->where('c.company_id', $companyId);

        $statusCart = $request->input('status_cart');
        if ($statusCart !== null && $statusCart !== '') {
            if (Schema::hasTable('status_cart')) {
                $query->join('status_cart as s', 's.cod', '=', 'c.status_cart')
                    ->where('s.cod', (int) $statusCart);
            } else {
                $query->where('c.status_cart', (int) $statusCart);
            }
        }

        $this->applyCreatedDateFilters($query, $request);

        return [
            'total_send_catalog' => (int) $query->count('c.cart_id'),
        ];
    }

    private function applyCreatedDateFilters($query, Request $request): void
    {
        $fechaDesdePordefecto = $request->input('fecha_desde_pordefecto');

        if ($fechaDesdePordefecto !== null && $fechaDesdePordefecto !== '') {
            if ($fechaDesdePordefecto === 'personalizado') {
                $desdeFecha = (string) $request->input('desde_fecha', '');
                $hastaFecha = (string) $request->input('hasta_fecha', '');

                if ($desdeFecha !== '' && $desdeFecha !== 'Invalid date') {
                    $desde = date('Y-m-d', strtotime($desdeFecha . ' - 1 days'));
                    $query->where('c.created', '>', $desde);
                }

                if ($hastaFecha !== '' && $hastaFecha !== 'Invalid date') {
                    $hasta = date('Y-m-d', strtotime($hastaFecha . ' + 1 days'));
                    $query->where('c.created', '<', $hasta);
                }
            }

            $fecha = date('Y-m-d', strtotime($fechaDesdePordefecto . ' - 1 days'));
            $fecha = date('Y-m-d', strtotime($fecha . ' -1 days'));
            $query->where('c.created', '>', $fecha);

            if ($fechaDesdePordefecto === date('Y-m-01', strtotime('first day of last month'))) {
                $hastaFecha = date('Y-m-01');
                $query->where('c.created', '<', $hastaFecha);
            }
        } else {
            $fecha = date('Y-m-01');
            $fecha = date('Y-m-d', strtotime($fecha . ' -1 days'));
            $query->where('c.created', '>', $fecha);
        }
    }
}
