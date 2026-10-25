<?php

namespace App\Http\Requests;

class UpdatePlanRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'plan';
    }

    public function rules(): array
    {
        return [];
    }
}
