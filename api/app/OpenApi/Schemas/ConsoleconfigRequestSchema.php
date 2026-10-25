<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ConsoleconfigRequest',
    properties: [
        new OA\Property(
            property: 'token',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'username',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'address',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'telephone',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'app',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'rol',
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
class ConsoleconfigRequestSchema {}

