<?php

namespace App\Actions;

use App\Models\Customer;

class CustomerDeleteService
{
    public function delete(Customer $customer): ?string
    {
        if ($customer->orders()->exists()) {
            return  "El cliente id: {$customer->customer_id} tiene órdenes asociadas";
        }

        if ($customer->catalog_requests()->where('status', 1)->exists()) {
            return "El cliente id: {$customer->customer_id} tiene solicitudes de catálogo pendientes";
        }

        //TODO when create the new entity "returns?"
        // if ($customer->returns()->exists()) {
        //     return "El cliente id: {$customer->customer_id} tiene devoluciones asociadas";
        // }

        $customer->forceDelete();

        return null;
    }
}

