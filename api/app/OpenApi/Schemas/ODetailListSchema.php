<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Odetaillist',
    properties: [
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'detail_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'order_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'modify_date',
            type: 'string',
            format: 'date-time',
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
            property: 'qty',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'delivered_quantity',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'delivered_pack',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'pack',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discount',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'discount_type',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sale_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'fob_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'purchase_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'comment',
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
            property: 'amount_sales',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discount_amount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'total_amount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'amount_delivered',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discount_delivered',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'total_delivered',
            type: 'string',
            nullable: true
        ),
    ]
)]
class ODetailListSchema {}

