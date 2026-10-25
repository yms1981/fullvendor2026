<?php

namespace App\Http\Controllers;

use App\Services\MarketplacePublicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Marketplace',
    description: 'Marketplace público: empresas, categorías por empresa y productos con foto. Sin autenticación.'
)]
class MarketplacePublicController extends Controller
{
    #[OA\Get(
        path: 'api/v2/marketplace/home',
        operationId: 'marketplaceHomeFeed',
        summary: 'Home marketplace (empresas + categorías + productos con foto)',
        description: 'Devuelve empresas marketplace con catálogo, categorías por empresa y productos con imagen para promociones.',
        security: [],
        tags: ['Marketplace'],
        parameters: [
            new OA\Parameter(name: 'companies', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 8)),
            new OA\Parameter(name: 'products_per_company', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 4)),
            new OA\Parameter(name: 'featured', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 12)),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function home(Request $request, MarketplacePublicService $service): JsonResponse
    {
        $payload = $service->homeFeed(
            (int) $request->query('companies', 8),
            (int) $request->query('products_per_company', 4),
            (int) $request->query('featured', 12)
        );

        return response()->json(['data' => $payload]);
    }

    #[OA\Get(
        path: 'api/v2/marketplace/companies',
        operationId: 'marketplaceCompanies',
        summary: 'Listar empresas del marketplace',
        security: [],
        tags: ['Marketplace'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 12)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
        ]
    )]
    public function companies(Request $request, MarketplacePublicService $service): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 8)));
        $offset = ($page - 1) * $perPage;

        $payload = $service->listCompanies($offset, $perPage);

        return response()->json(['data' => $payload]);
    }

    public function searchCompanies(Request $request, MarketplacePublicService $service): JsonResponse
    {
        $keyword = (string) $request->input('company_name', $request->query('keyword', ''));

        return response()->json([
            'data' => [
                'results' => $service->searchCompanies($keyword),
            ],
        ]);
    }

    public function companyBySlug(string $slug, MarketplacePublicService $service): JsonResponse
    {
        $company = $service->getCompanyBySlug($slug);
        if ($company === null) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        return response()->json(['data' => $company]);
    }

    #[OA\Get(
        path: 'api/v2/marketplace/companies/{companyId}/categories',
        operationId: 'marketplaceCompanyCategories',
        summary: 'Categorías de una empresa',
        security: [],
        tags: ['Marketplace'],
        parameters: [
            new OA\Parameter(name: 'companyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
        ]
    )]
    public function categories(int $companyId, MarketplacePublicService $service): JsonResponse
    {
        return response()->json(['data' => $service->listActiveCategories($companyId)]);
    }

    #[OA\Get(
        path: 'api/v2/marketplace/companies/{companyId}/products',
        operationId: 'marketplaceCompanyProducts',
        summary: 'Productos de una empresa por categoría',
        security: [],
        tags: ['Marketplace'],
        parameters: [
            new OA\Parameter(name: 'companyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'category_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
        ]
    )]
    public function products(int $companyId, Request $request, MarketplacePublicService $service): JsonResponse
    {
        $categoryId = (int) $request->query('category_id', 0);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 8)));
        $offset = ($page - 1) * $perPage;

        return response()->json([
            'data' => $service->listProducts($companyId, $categoryId, $offset, $perPage),
        ]);
    }

    public function productsByCategory(Request $request, MarketplacePublicService $service): JsonResponse
    {
        $companyId = (int) $request->input('companyId', 0);
        $categoryId = (int) $request->input('categoryId', 0);

        return response()->json([
            'data' => [
                'products' => $service->listProductsForCategory($companyId, $categoryId),
            ],
        ]);
    }
}
