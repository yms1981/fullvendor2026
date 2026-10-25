<?php

namespace App\Http\Requests\SellerPortal;

use App\Http\Requests\StoreCustomerRequest;

class SellerPortalStoreCustomerRequest extends StoreCustomerRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('seller_portal_vendor') !== null;
    }
}
