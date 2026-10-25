<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CatalogsentmailRequest',
    properties: [
        new OA\Property(
            property: 'catalog_sent_id',
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
            property: 'catlog_reqest_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'hide_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'catalog_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'catalog_text',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'catalog_send_type',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'catalog_email_text',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'send_date',
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
            property: 'customer_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'expiration',
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
class CatalogSentMailRequestSchema {}

