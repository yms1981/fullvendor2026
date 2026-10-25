<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Navbar',
    properties: [
        new OA\Property(
            property: 'navbar_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'url_nav',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'identify',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name_eng',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name_spa',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'father_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'icon',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'config',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'is_active_to_rol',
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
class NavbarSchema {}

