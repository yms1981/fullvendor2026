<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class BulkProductDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('bulkDelete', Product::class);
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer'],
            'products' => ['required'],
            'excluded_ids' => ['nullable'],
        ];
    }
}
