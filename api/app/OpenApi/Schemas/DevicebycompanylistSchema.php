<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Devicebycompanylist',
    properties: [
        new OA\Property(
            property: 'deviceinfo',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'so',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'version',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created',
            type: 'string',
            format: 'date',
            nullable: true
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'versionname',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
    ]
)]
class DevicebycompanylistSchema {}

