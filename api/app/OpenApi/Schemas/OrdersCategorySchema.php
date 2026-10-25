<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Orderscategory',
    properties: [
        new OA\Property(
            property: 'order_category_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'category_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'catalog_id',
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
            property: 'order',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'catalog_name',
            type: 'string',
            nullable: true
        ),
    ]
)]
class OrdersCategorySchema {}

