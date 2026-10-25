<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserprofileRequest',
    properties: [
        new OA\Property(
            property: 'profile_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'profile_name',
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
            property: 'order_discount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order_disc_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order_net_discount',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order_net_disc_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'can_change_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'can_send_catalog',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'can_create_customer',
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
            property: 'status',
            type: 'integer',
            format: 'int32',
            nullable: true
        ),
        new OA\Property(
            property: 'default',
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
class SellerProfilesRequestSchema {}

