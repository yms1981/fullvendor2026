<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Subscription::class);
    }

    public function rules(): array
    {
        return [];
    }
}
