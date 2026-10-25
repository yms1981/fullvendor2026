<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Banner',
    properties: [
        new OA\Property(
            property: 'bannerId',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'bannerHeading',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'bannerDescription',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'bannerImage',
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
            property: 'deleted_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
    ]
)]
class BannerSchema {}

