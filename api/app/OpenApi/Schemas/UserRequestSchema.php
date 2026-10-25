<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserRequest',
    properties: [
        new OA\Property(
            property: 'name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'email_verified_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'language_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'two_factor_secret',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'two_factor_recovery_codes',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'two_factor_confirmed_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'remember_token',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'deleted_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
    ]
)]
class UserRequestSchema {}

