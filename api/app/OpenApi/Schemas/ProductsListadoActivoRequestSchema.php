<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductslistadoactivoRequest',
    properties: [
        new OA\Property(
            property: 'pro_id',
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
            property: 'warehouse_user_id',
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
            property: 'sale_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'category_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'barcode',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'barcode_images',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'tags',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'descriptions',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'unit_type',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'stock',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'minimum_stock',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'notify_minimum_stock',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'currency_type',
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
            property: 'locations',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created_by',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created_on',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'modified_on',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'Img',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'imgURL',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'simgURL',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'id_kor',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'cat_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cats_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sell_with_inventory',
            type: 'string',
            nullable: true
        ),
    ]
)]
class ProductsListadoActivoRequestSchema {}

