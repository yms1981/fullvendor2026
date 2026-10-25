<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Catalog',
    properties: [
        new OA\Property(
            property: 'catalog_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'catalog_name',
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
            property: 'cat_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'product_name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'status',
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
            property: 'customer_emails',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'email_sent_on',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'attach',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'dayexpired',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'default_catalog',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cabeza_pagina',
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
class CatalogSchema {}

