<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CmRequest',
    properties: [
        new OA\Property(
            property: 'pageId',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'pageTitle',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'pageText',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'link',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'content',
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
            property: 'modified',
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
class CmRequestSchema {}

