<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategorylistRequest',
    properties: [
        new OA\Property(
            property: 'order_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cat_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'category_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'language_id',
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
            property: 'category_name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'images',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'category_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'category_created_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'id_kor',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'category_default',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'items',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
    ]
)]
class CategoryListRequestSchema {}

