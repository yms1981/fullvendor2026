<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MailslogRequest',
    properties: [
        new OA\Property(
            property: 'customers',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'subject',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'content',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'fecha',
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
class MailsLogRequestSchema {}

