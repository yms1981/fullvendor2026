<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\ResolvesErpCompany;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Erp', description: 'Integración ERP. Auth: header X-Api-Key (sin Sanctum).')]
class ErpCustomersController extends Controller
{
    use ResolvesErpCompany;

    #[OA\Get(
        path: 'api/v2/erp/customers',
        summary: 'Listar clientes de la empresa',
        description: 'Devuelve clientes de company_id (query o header X-Company-Id).',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Company-Id', in: 'header', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'company_id requerido'),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $rows = Customer::query()
            ->where('company_id', $companyId)
            ->orderBy('customer_id')
            ->get([
                'customer_id', 'name', 'business_name', 'email', 'phone', 'cell_phone',
                'tax_id', 'cust_id_kor', 'group_id', 'term_id', 'customer_status',
                'discount', 'notes',
            ]);

        return $this->erpOk($rows->map(fn (Customer $c) => [
            'customer_id' => (int) $c->customer_id,
            'name' => (string) ($c->name ?? ''),
            'business_name' => (string) ($c->business_name ?? ''),
            'email' => (string) ($c->email ?? ''),
            'phone' => (string) ($c->phone ?? ''),
            'cell_phone' => (string) ($c->cell_phone ?? ''),
            'tax_id' => (string) ($c->tax_id ?? ''),
            'cust_id_kor' => (string) ($c->cust_id_kor ?? ''),
            'group_id' => $c->group_id,
            'term_id' => $c->term_id,
            'customer_status' => (int) ($c->customer_status ?? 1),
            'discount' => (float) ($c->discount ?? 0),
            'notes' => (string) ($c->notes ?? ''),
        ])->all());
    }

    #[OA\Post(
        path: 'api/v2/erp/customers/upsert',
        summary: 'Crear o actualizar cliente',
        description: 'Busca por customer_id o cust_id_kor/iderp. Puede incluir balance y dirección comercial.',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Company-Id', in: 'header', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'customer_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'cust_id_kor', type: 'string', nullable: true, description: 'ID ERP'),
                    new OA\Property(property: 'iderp', type: 'string', nullable: true, description: 'Alias de cust_id_kor'),
                    new OA\Property(property: 'name', type: 'string', nullable: true),
                    new OA\Property(property: 'business_name', type: 'string', nullable: true),
                    new OA\Property(property: 'email', type: 'string', nullable: true),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                    new OA\Property(property: 'cell_phone', type: 'string', nullable: true),
                    new OA\Property(property: 'tax_id', type: 'string', nullable: true),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                    new OA\Property(property: 'group_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'term_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'discount', type: 'number', nullable: true),
                    new OA\Property(property: 'customer_status', type: 'integer', nullable: true),
                    new OA\Property(property: 'balance', type: 'number', nullable: true),
                    new OA\Property(property: 'commercial_address', type: 'string', nullable: true),
                    new OA\Property(property: 'commercial_country', type: 'string', nullable: true),
                    new OA\Property(property: 'commercial_state', type: 'string', nullable: true),
                    new OA\Property(property: 'commercial_city', type: 'string', nullable: true),
                    new OA\Property(property: 'commercial_zip_code', type: 'string', nullable: true),
                    new OA\Property(property: 'dispatch_delivery_address', type: 'string', nullable: true),
                    new OA\Property(property: 'dispatch_shipping_notes', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Cliente creado o actualizado'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'company_id requerido'),
        ]
    )]
    public function upsert(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $iderp = trim((string) ($request->input('cust_id_kor') ?? $request->input('iderp') ?? ''));
        $customerId = (int) $request->input('customer_id', 0);

        $customer = null;
        if ($customerId > 0) {
            $customer = Customer::query()
                ->where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->first();
        }
        if (! $customer && $iderp !== '') {
            $customer = Customer::query()
                ->where('company_id', $companyId)
                ->where('cust_id_kor', $iderp)
                ->first();
        }

        $created = false;
        if (! $customer) {
            $customer = new Customer;
            $customer->company_id = $companyId;
            $customer->customer_created_at = now();
            $customer->customer_status = 1;
            $created = true;
        }

        foreach ([
            'name', 'business_name', 'email', 'phone', 'cell_phone', 'tax_id',
            'notes', 'group_id', 'term_id', 'discount', 'customer_status', 'balance',
            'commercial_address', 'commercial_country', 'commercial_state',
            'commercial_city', 'commercial_zip_code',
            'dispatch_delivery_address', 'dispatch_shipping_notes',
        ] as $field) {
            if ($request->exists($field)) {
                $customer->{$field} = $request->input($field);
            }
        }
        if ($iderp !== '') {
            $customer->cust_id_kor = $iderp;
        }
        if (! $customer->name && $customer->business_name) {
            $customer->name = $customer->business_name;
        }

        $customer->modified_at = now();
        $customer->save();

        return $this->erpOk([
            'customer_id' => (int) $customer->customer_id,
            'cust_id_kor' => (string) ($customer->cust_id_kor ?? ''),
            'created' => $created,
        ], $created ? 'Customer created' : 'Customer updated');
    }
}
