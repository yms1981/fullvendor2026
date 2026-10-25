<?php

namespace App\Http\Requests;

class UpdateInvoiceRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'invoice';
    }

    public function rules(): array
    {
        return [];
    }
}
