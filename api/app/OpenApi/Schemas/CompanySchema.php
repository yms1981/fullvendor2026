<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Company',
    properties: [
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'company_category_id',
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
            property: 'name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'short_name',
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
            property: 'image',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'small_image',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'address',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'country',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'state',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'city',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'zip_code',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'tax_condition',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'tax_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'max_number_of_clients',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'max_number_of_orders',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'max_number_of_vendors',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'total_price',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'website',
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
            property: 'slug_url',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'currency',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'createdby_comp_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'currency_code_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'token',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'ordersend_time',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'catalogsend_time',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'format_id',
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
            property: 'sell_with_inventory',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'rol_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'show_inventory',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'show_semaforo_stock',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'email_client_on_salesman_order',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'publish_price_list',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'show_plus_taxes_label',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'plus_taxes_label',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sellers_ids',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sem_red_hasta',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sem_amarillo_desde',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sem_amarillo_hasta',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sem_verde_desde',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sem_verde_hasta',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sem_red',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sem_amarillo',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sem_verde',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'email_on_salesman_order',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'default_unit_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
    ]
)]
class CompanySchema {}

