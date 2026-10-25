<?php

namespace App\Http\Requests;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'inventory';
    }

    public function authorize(): bool
    {
        $companyId = $this->route('companyId');
        $productId = $this->route('productId');
        $item = Inventory::query()
            ->where('company_id',$companyId)
            ->where('product_id',$productId)->sole();

        return \Auth::user()->can('update', $item);
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
