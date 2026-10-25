<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EnquiryRequest',
    properties: [
        new OA\Property(
            property: 'enquiryId',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
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
            property: 'phone',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'subject',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'status',
            type: 'integer',
            format: 'int32',
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
class EnquiryRequestSchema {}

