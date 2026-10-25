<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'NavbarrolidRequest',
    properties: [
        new OA\Property(
            property: 'navbar_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'rol_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'fhater_id',
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
class NavbarRolIdRequestSchema {}

