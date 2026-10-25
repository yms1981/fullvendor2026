<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Warehouseuser',
    properties: [
        new OA\Property(
            property: 'user_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'unique_id',
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
            property: 'first_name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'last_name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'username',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'phone_number',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cell_number',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'fax',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'profile_image',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'email_verification',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'otp',
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
            property: 'token',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'add_customer',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'update_customer',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'send_catalog',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'deleted_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
    ]
)]
class WarehouseUserSchema {}

