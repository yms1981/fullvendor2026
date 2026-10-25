<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CompanyuserRequest',
    properties: [
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'user_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'type',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'profileable_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'profileable_type',
            type: 'string',
            nullable: true
        ),
    ]
)]
class CompanyUserRequestSchema {}

