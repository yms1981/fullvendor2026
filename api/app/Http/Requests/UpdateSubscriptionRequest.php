<?php

namespace App\Http\Requests;

class UpdateSubscriptionRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'subscription';
    }

    public function rules(): array
    {
        return [];
    }
}
