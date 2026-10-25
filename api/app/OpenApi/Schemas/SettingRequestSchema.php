<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SettingRequest',
    properties: [
        new OA\Property(
            property: 'settingId',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'logo',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'favicon',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'title',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'address',
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
            property: 'website',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'facebook',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'twitter',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'linkedin',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'instagram',
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
class SettingRequestSchema {}

