<?php

namespace App\Http\Requests;

use App\Models\OrderFlowTransition;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderFlowTransitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', OrderFlowTransition::class);
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
