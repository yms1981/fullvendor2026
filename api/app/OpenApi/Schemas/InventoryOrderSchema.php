<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Inventoryorder',
    properties: [
        new OA\Property(
            property: 'tipo_d',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'order_number',
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
            property: 'user_id',
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
            property: 'customer_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'payment_method',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'payment_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'transaction_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order_comments',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'delivery_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discount_type',
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
            property: 'updated',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'approved_by',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'delivery_notes',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'warehouse_user_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'warehouse_assign_date',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'source',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'internal_notes',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'token_pass',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'date_confirmed',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'send_email_order',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'date_rejected_email',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'date_expired_email',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'expiration_days',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'amount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'discount_a',
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
            property: 'business_name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name_status_spanish',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name_status_english',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'color',
            type: 'string',
            nullable: true
        ),
    ]
)]
class InventoryOrderSchema {}

