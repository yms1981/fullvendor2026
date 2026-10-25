<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Key',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'user_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'key',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'level',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'ignore_limits',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'is_private_key',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'ip_addresses',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'date_created',
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
            property: 'deleted_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
    ]
)]
class KeySchema {}

