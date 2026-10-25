<?php

namespace App\Http\Requests;

class UpdateInitialOrderNumberRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'initialordernumber';
    }

    public function rules(): array
    {
        return [
            'initial_number' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
