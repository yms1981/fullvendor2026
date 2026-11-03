<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\ResolvesErpCompany;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\TermsOfSale;
use App\Models\Vendor;
use App\Models\WarehouseUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Erp', description: 'Integración ERP. Auth: header X-Api-Key (sin Sanctum).')]
class ErpCatalogController extends Controller
{
    use ResolvesErpCompany;

    #[OA\Get(
        path: 'api/v2/erp/categories',
        summary: 'Listar categorías',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
        ]
    )]
    public function categories(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $rows = Category::query()
            ->where('company_id', $companyId)
            ->orderBy('cat_id')
            ->get(['cat_id', 'category_name', 'category_status', 'image', 'category_default']);

        return $this->erpOk($rows->map(fn (Category $c) => [
            'cat_id' => (int) $c->cat_id,
            'category_name' => (string) ($c->category_name ?? ''),
            'category_status' => (int) ($c->category_status ?? 1),
            'image' => $c->image,
            'category_default' => (int) ($c->category_default ?? 0),
        ])->all());
    }

    #[OA\Get(
        path: 'api/v2/erp/customer-groups',
        summary: 'Listar grupos de clientes',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
        ]
    )]
    public function customerGroups(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $rows = CustomerGroup::query()
            ->where('company_id', $companyId)
            ->orderBy('group_id')
            ->get();

        return $this->erpOk($rows->toArray());
    }

    #[OA\Get(
        path: 'api/v2/erp/terms',
        summary: 'Listar términos de venta',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
        ]
    )]
    public function terms(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $rows = TermsOfSale::query()
            ->where('company_id', $companyId)
            ->orderBy('term_id')
            ->get();

        return $this->erpOk($rows->toArray());
    }

    #[OA\Get(
        path: 'api/v2/erp/sellers',
        summary: 'Listar vendedores (sellers)',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
        ]
    )]
    public function sellers(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $rows = Vendor::query()
            ->where('company_id', $companyId)
            ->with('user:id,name,first_name,last_name,email')
            ->orderBy('id')
            ->get();

        return $this->erpOk($rows->map(fn (Vendor $s) => [
            'id' => (int) $s->id,
            'user_id' => (int) ($s->user_id ?? 0),
            'name' => (string) ($s->user?->name ?? trim(($s->user?->first_name ?? '').' '.($s->user?->last_name ?? ''))),
            'email' => (string) ($s->user?->email ?? ''),
        ])->all());
    }

    #[OA\Get(
        path: 'api/v2/erp/warehouse-users',
        summary: 'Listar usuarios de almacén',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
        ]
    )]
    public function warehouseUsers(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $rows = WarehouseUser::query()
            ->where('company_id', $companyId)
            ->get();

        return $this->erpOk($rows->toArray());
    }
}
