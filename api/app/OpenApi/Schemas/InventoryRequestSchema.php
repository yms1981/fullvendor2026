<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InventoryRequest',
    properties: [
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'product_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'kor_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'qty',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'sale_price',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'purchased_price',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'modify_date',
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
class InventoryRequestSchema {}

