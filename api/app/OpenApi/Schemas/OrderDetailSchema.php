<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Orderdetail',
    properties: [
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
            property: 'product_id',
            type: 'integer',
            format: 'int64',
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
            property: 'sale_price_app',
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
            property: 'id_kor',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'comments',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'modify_date',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'fixed_pricedate',
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
        new OA\Property(
            property: 'amount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discountt',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'delivered_date',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'total',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'groupcustomer',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'tipolista',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'perc_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'salesp',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'impprice',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'totalprice',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'import_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'pack',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'delivered_pack',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
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
class OrderDetailSchema {}

