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

    #[OA\Post(
        path: 'api/v2/erp/categories/upsert',
        summary: 'Crear o actualizar categoría por nombre',
        description: 'Busca por category_name (case-insensitive) en la empresa. Si no existe, la crea. Devuelve cat_id.',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'category_name'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'category_name', type: 'string', example: 'Beverages'),
                    new OA\Property(property: 'category_status', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'category_default', type: 'integer', nullable: true, example: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Categoría creada o actualizada'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function upsertCategory(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $name = trim((string) $request->input('category_name', ''));
        if ($name === '') {
            return $this->erpFail('category_name is required');
        }

        $nameColumn = \App\Support\CategoryNameColumn::name();
        $category = Category::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(TRIM('.$nameColumn.')) = ?', [mb_strtolower($name, 'UTF-8')])
            ->first();

        $created = false;
        if (! $category) {
            $category = new Category;
            $category->company_id = $companyId;
            $category->{$nameColumn} = $name;
            $category->category_created_at = now();
            $created = true;
        } else {
            $category->{$nameColumn} = $name;
        }

        if ($request->exists('category_status')) {
            $category->category_status = (int) $request->input('category_status') ? 1 : 0;
        } elseif ($created) {
            $category->category_status = 1;
        }

        if ($request->exists('category_default')) {
            $category->category_default = (int) $request->input('category_default') ? 1 : 0;
        } elseif ($created) {
            $category->category_default = 0;
        }

        $category->save();

        return $this->erpOk([
            'cat_id' => (int) $category->cat_id,
            'category_name' => (string) ($category->category_name ?? ''),
            'category_status' => (int) ($category->category_status ?? 1),
            'category_default' => (int) ($category->category_default ?? 0),
            'created' => $created,
        ], $created ? 'Category created' : 'Category updated');
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
