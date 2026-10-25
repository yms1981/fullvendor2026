<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Invoice::class);
    }

    public function rules(): array
    {
        return [];
    }
}
