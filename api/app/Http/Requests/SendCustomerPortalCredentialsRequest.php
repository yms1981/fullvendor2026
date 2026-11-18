<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class SendCustomerPortalCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            && (bool) \Auth::user()?->can('update', $customer);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'channel' => ['nullable', 'string', 'in:email,sms,whatsapp'],
            // Prefijo país (dígitos) y/o teléfono E.164 para SMS/WhatsApp.
            'phonecode' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $channel = strtolower(trim((string) $this->input('channel', 'email')));
        if ($channel === '') {
            $channel = 'email';
        }
        $phonecode = preg_replace('/\D+/', '', (string) $this->input('phonecode', '')) ?: '';
        $phone = preg_replace('/\D+/', '', (string) $this->input('phone', '')) ?: '';
        $this->merge([
            'channel' => $channel,
            'phonecode' => $phonecode !== '' ? $phonecode : null,
            'phone' => $phone !== '' ? $phone : null,
        ]);
    }
}
