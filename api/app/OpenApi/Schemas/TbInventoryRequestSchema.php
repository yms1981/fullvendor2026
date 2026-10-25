<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TbinventoryRequest',
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
            property: 'sku',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'descriptions',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'qty',
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
            property: 'sp',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'pp',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'sale_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'purchase_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'category_id',
            type: 'string',
            nullable: true
        ),
    ]
)]
class TbInventoryRequestSchema {}

