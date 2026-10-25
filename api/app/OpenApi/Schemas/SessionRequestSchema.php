<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SessionRequest',
    properties: [
        new OA\Property(
            property: 'user_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'ip_address',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'user_agent',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'payload',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'last_activity',
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
class SessionRequestSchema {}

