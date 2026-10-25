<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Customer',
    properties: [
        new OA\Property(
            property: 'customer_id',
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
            property: 'user_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'business_name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'tax_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'term_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'group_id',
            type: 'integer',
            format: 'int64',
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
            property: 'cell_phone',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'notes',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'commercial_address',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'commercial_delivery_address',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'commercial_country',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'commercial_state',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'commercial_city',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'commercial_zone',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'commercial_zip_code',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dispatch_address',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dispatch_delivery_address',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dispatch_country',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dispatch_state',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dispatch_city',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dispatch_zone',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dispatch_zip_code',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dispatch_shipping_notes',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'catalog_emails',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'customer_created_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'customer_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cust_id_kor',
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
            property: 'assign_catalog',
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
            property: 'modified_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'balance',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sales',
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
class CustomerSchema {}

