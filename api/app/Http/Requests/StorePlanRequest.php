<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Plan::class);
    }

    public function rules(): array
    {
        return [];
    }
}
