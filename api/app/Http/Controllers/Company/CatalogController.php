<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Actions\SendCatalogService;
use App\Enums\OrderStatusCodes;
use App\Exports\PriceListExport;
use App\Http\Requests\SendMailCatalogRequest;
use App\Http\Requests\StoreCatalogRequest;
use App\Http\Requests\UpdateCatalogRequest;
use App\Http\Resources\CatalogProductResource;
use App\Models\Catalog;
use App\Models\CatalogCategory;
use App\Models\CatalogProduct;
use App\Models\CatalogRequest;
use App\Models\CatalogSentMail;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Image;
use App\Models\Product;
use App\SortOptions\ProductSortOptions;
use App\Services\FullVendorCatalogService;
use App\Services\TelecomMessagingClient;
use App\Support\ProductBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

class CatalogController extends Controller
{
    protected $resourceRoute = 'catalog';

    #[OA\Get(
        path: "api/v2/catalog",
        summary: "Display a listing of the Catalog.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalog"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     type:     'array',
                                                     items:    new OA\Items(ref: '#/components/schemas/Catalog')
                                                 ),
                                                 new OA\Property(
                                                     property:   'meta',
                                                     type:       'object',
                                                     properties: [
                                                                     new OA\Property(
                                                                         property: 'current_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(property: 'from', type: 'integer'),
                                                                     new OA\Property(
                                                                         property: 'last_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(
                                                                         property: 'per_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(property: 'to', type: 'integer'),
                                                                     new OA\Property(property: 'total', type: 'integer')
                                                                 ]
                                                 ),
                                                 new OA\Property(
                                                     property:   'links',
                                                     type:       'object',
                                                     properties: [
                                                                     new OA\Property(property: 'first', type: 'string'),
                                                                     new OA\Property(property: 'last', type: 'string'),
                                                                     new OA\Property(
                                                                         property: 'prev',
                                                                         type:     'string',
                                                                         nullable: true
                                                                     ),
                                                                     new OA\Property(
                                                                         property: 'next',
                                                                         type:     'string',
                                                                         nullable: true
                                                                     )
                                                                 ]
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function index()
    {
        return parent::index();
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/catalog",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/CatalogRequest'
                      )
        ),
        tags: ["Catalog"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Catalog'
                                                 ),
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource created successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function store(StoreCatalogRequest $request): \Illuminate\Http\Resources\Json\JsonResource
    {
        try {
            return $this->createFromAttributes($this->sanitizeCatalogAttributes($request->toArray()));
        } catch (\Throwable $e) {
            Log::error('CatalogController::store failed', [
                'error' => $e->getMessage(),
                'company_id' => $request->input('company_id'),
            ]);
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/catalog/{catalog}",
        summary: "Display the specified Catalog.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalog"],
        parameters: [
            new OA\Parameter(
                name:        "catalog",
                description: "The Catalog identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Catalog'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function show($catalog)
    {
        return $this->getMainModelInstance($catalog);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/catalog/{catalog}",
        summary: "Updates the specified Catalog.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/CatalogRequest'
                      )
        ),
        tags: ["Catalog"],
        parameters: [
            new OA\Parameter(
                name:        "catalog",
                description: "The Catalog identifier to update.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Catalog'
                                                 ),
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource updated successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function update(UpdateCatalogRequest $request, Catalog $catalog)
    {
        try {
            return $this->updateModel(
                $this->getMainModelInstance($catalog->catalog_id),
                $this->sanitizeCatalogAttributes($request->toArray(), $catalog)
            );
        } catch (\Throwable $e) {
            Log::error('CatalogController::update failed', [
                'catalog_id' => (int) $catalog->catalog_id,
                'error' => $e->getMessage(),
            ]);
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/catalog/{catalog}",
        summary: "Remove the specified Catalog.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalog"],
        parameters: [
            new OA\Parameter(
                name:        "catalog",
                description: "The Catalog identifier to remove.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource deleted successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function destroy(Catalog $catalog): ?Response
    {
        return $this->destroyResourceEmptyResponse($catalog);
    }

    public function exist($companyId, $catalogName): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $this->getBuilder()
                ->where('company_id', $companyId)
                ->where('catalog_name', $catalogName)
                ->get()
                ->count() > 0
        );
    }

    public function default($companyId)
    {
        return Catalog::where('company_id', $companyId)
            ->where('default_catalog', 1)
            ->sole();
    }

    public function showByCompany($catalogId, $companyId)
    {
        return Catalog::where('catalog_id', $catalogId)
            ->where('company_id', $companyId)
            ->sole();
    }

    #[OA\Get(
        path: "api/v2/catalog/{catalogId}/customer/{customerId}/products",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalog"],
        parameters: [
            new OA\Parameter(
                name:        "catalogId",
                description: "The Product identifier to retrieve.",
                in:          "path",
                required:    true
            ),
            new OA\Parameter(
                name:        "customerId",
                description: "The Product identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Product'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function products($catalogId, $customerId)
    {
        $customer = Customer::where('customer_id', $customerId)->sole();
        $page = request()->input('page', 1);
        $perPage = request()->input('per_page', 15);
        $props = request()->input('props', ['*']);
        $productsQuery = $this->buildProductsQuery($catalogId);

        $productsQuery->withStarOrder($customerId, OrderStatusCodes::forStar());

        $productsQuery->select($props);

        if (request()->has('all')) {
            $products = $productsQuery->get()->map(
                fn($product) => $product->applyCustomerPricing($customer)
            );
        } else {
            $products = $productsQuery->paginate($perPage, ['*'], 'page', $page);
            $products->getCollection()->transform(
                fn($product) => $product->applyCustomerPricing($customer)
            );
        }

        return CatalogProductResource::collection($products);
    }

    #[OA\Post(
        path: "api/v2/catalog/send",
        summary: "Sends a catalog request/invitation to a customer",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/CatalogRequest'
                      )
        ),
        tags: ["Catalog"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource created successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function sendMailCatalog(SendMailCatalogRequest $request, SendCatalogService $sendCatalogService, TelecomMessagingClient $telecom)
    {
        $token = (string) $request->input('token', '');
        $catalogRequest = CatalogRequest::where('token', $token)->sole();

        $customerId = $catalogRequest->customer_id;
        $catalogId = $catalogRequest->catalog_id;

        $customer = Customer::with(['company.defaultSeller', 'sellers', 'sellerAssignCatalog'])
            ->where('customer_id', $customerId)
            ->sole();
        $company = $customer->company;

        $seller = $customer->sellerAssignCatalog
            ?? $customer->sellers->first()
            ?? $company->defaultSeller;
        if ($seller) {
            $seller->loadMissing('user');
        }

        try {
            $invitation = json_decode(
                \Illuminate\Support\Facades\Crypt::decryptString($token),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            $expirationDate = (string) ($invitation['expirationDate'] ?? '');
            $catalogText = (string) ($invitation['catalog_text'] ?? '');
        } catch (\Throwable) {
            $expirationDate = '';
            $catalogText = '';
        }

        $sendCatalogService->ensureCatalogCartRow(
            $company,
            $customer,
            (int) $catalogId,
            $token,
            $expirationDate,
            $catalogText,
            $seller
        );

        $htmlBody = (string) $request->input('html_body');
        $status = 200;
        $skipMailer = $request->boolean('skip_mailer_send');

        try {
            if (!$skipMailer) {
                $toEmail = trim((string) ($customer->email ?? ''));
                if ($toEmail === '') {
                    throw new \RuntimeException('Customer has no email address');
                }
                $subject = trim((string) ($company->name ?? $company->company_name ?? 'Catalog')) . ' Product Catalog';
                $telecom->sendEmail([$toEmail], $subject, $htmlBody, true, (int) $company->company_id);
            }
            $msg = 'Catalog sent successfully';
            $catalogSentMail = new CatalogSentMail();
            $catalogSentMail->catlog_reqest_id = $catalogRequest->request_id;
            $catalogSentMail->company_id = $company->company_id;
            $catalogSentMail->catalog_id = $catalogId;
            $catalogSentMail->customer_id = $customerId;
            $catalogSentMail->hide_price = 0;
            $catalogSentMail->save();
        } catch (\Exception $e) {
            $msg = "Error sending Catalog {$e->getMessage()}";
            $status = 500;
        }
        return response()->json(['message' => $msg], $status);
    }

    #[OA\Get(
        path: "api/v2/catalog/{catalogId}/categories",
        summary: "Gets the categories of a catalog",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalog"],
        parameters: [
            new OA\Parameter(
                name:        "catalogId",
                description: "The Category identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Category'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function categories($catalogId)
    {
        /** @var Catalog $catalog */
        $catalog = Catalog::where('catalog_id', $catalogId)->sole();

        // FullVendor Catalog: asegurar que estén todas las categorías activas antes de listar.
        if ((int) ($catalog->default_catalog ?? 0) === 1) {
            try {
                app(FullVendorCatalogService::class)->sync((int) $catalog->company_id);
            } catch (\Throwable $e) {
                Log::warning('CatalogController::categories - FullVendor sync failed', [
                    'catalog_id' => (int) $catalogId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $catalog
            ->categories()
            ->withCount([
                            'products as qty_products' => function ($query) {
                                $query->where('status', 1)
                                    ->whereNotNull('name')
                                    ->where('sale_price', '>', 0);
                            }
                        ])
            ->where('categories.category_status', 1)
            ->orderBy('catalog_category.order')
            ->orderBy('categories.cat_id')
            ->get()
            ->makeHidden('pivot');
    }

    /**
     * Productos de un catálogo listos para descarga/impresión (precio por grupo, imagen y categorías).
     * Reemplaza CatalogoPublicModel::get_productos_todownload del MVC.
     */
    public function downloadProducts($catalogId)
    {
        /** @var Catalog $catalog */
        $catalog = Catalog::where('catalog_id', $catalogId)->sole();
        $companyId = $catalog->company_id;
        $company = Company::where('company_id', $companyId)->first();

        $groupId = request()->input('group_id');
        $group = null;
        if ($groupId) {
            $group = \App\Models\CustomerGroup::where('customer_group_id', $groupId)
                ->where('company_id', $companyId)
                ->first();
        }
        if (!$group && $company) {
            $group = \App\Models\CustomerGroup::where('company_id', $companyId)
                ->where('customer_group_id', $company->default_customer_group_id)
                ->first();
        }

        $format = null;
        if ($company && $company->format_id) {
            $format = \App\Models\FormatNumeric::where('format_id', $company->format_id)->first();
        }
        if (!$format) {
            $format = \App\Models\FormatNumeric::default();
        }
        $digits = $format->digits ?? 2;
        $radix = $format->radixPoint ?? '.';
        $groupSep = $format->groupSeparator ?? ',';

        $symbol = '';
        if ($company) {
            $company->loadMissing('currencyModel');
            $symbol = $company->currencyModel->symbol ?? '';
        }

        $baseUrl = rtrim((string) config('fullvendor.web_url', config('app.url', 'http://localhost')), '/');
        $noImageUrl = $baseUrl . '/api/v2/images/no-image';

        $products = $this->buildProductsQuery($catalogId)
            ->with(['default_image', 'categories'])
            ->get();

        return $products->map(function ($product) use ($group, $symbol, $digits, $radix, $groupSep, $baseUrl, $noImageUrl) {
            $price = $group ? $product->getPriceForGroup($group) : (float) $product->sale_price;
            $product->price_formated = $symbol . number_format($price, $digits, $radix, $groupSep);
            $product->categories_name = $product->categories->pluck('category_name')->implode(', ');

            $uuid = $product->default_image->uuid ?? null;
            // Misma URL pública que productos (no /images/catalog/… que exige token de carrito).
            $product->imagen_js = $uuid
                ? $baseUrl . '/api/v2/images/' . $uuid
                : $noImageUrl;

            return $product;
        })->values();
    }

    /**
     * Sincroniza las categorías de un catálogo (tabla catalog_category) respetando el orden recibido.
     * Recibe `categories` como arreglo de IDs en el orden deseado. Si viene vacío, desasocia todas.
     */
    public function syncCategories($catalogId)
    {
        /** @var Catalog $catalog */
        $catalog = Catalog::where('catalog_id', $catalogId)->sole();

        $ids = request()->input('categories', []);
        if (!is_array($ids)) {
            $ids = array_filter(array_map('trim', explode(',', (string) $ids)), fn($v) => $v !== '');
        }

        if ((int) ($catalog->default_catalog ?? 0) === 1) {
            try {
                $companyId = (int) $catalog->company_id;
                // Asegurar que catalog_category = categorías activas reales; luego aplicar orden del UI.
                $synced = app(FullVendorCatalogService::class)->sync($companyId);
                $catalog = $synced ?? $catalog->fresh();
                app(FullVendorCatalogService::class)->reorderCategories($catalog, $ids);
            } catch (\InvalidArgumentException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            } catch (\Throwable $e) {
                Log::error('CatalogController::syncCategories default failed', [
                    'catalog_id' => (int) $catalogId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => $e->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->json([
                'message' => 'Resource updated successfully',
                'data' => array_values(array_map('intval', $ids)),
            ], Response::HTTP_OK);
        }

        $syncData = [];
        $order = 1;
        foreach ($ids as $categoryId) {
            $categoryId = (int) $categoryId;
            if ($categoryId <= 0 || isset($syncData[$categoryId])) {
                continue;
            }
            $syncData[$categoryId] = ['order' => $order++];
        }

        // Se hace purge total + attach en orden para evitar colisiones con el
        // índice único `unique_catalog_order` (order, catalog_id). Las filas
        // soft-deleted siguen ocupando ese índice, por eso forceDelete withTrashed.
        try {
            \DB::transaction(function () use ($catalog, $syncData) {
                CatalogCategory::withTrashed()
                    ->where('catalog_id', (int) $catalog->catalog_id)
                    ->forceDelete();
                foreach ($syncData as $categoryId => $pivot) {
                    $catalog->categories()->attach($categoryId, $pivot);
                }

                // Mantener cat_id legacy (CSV de IDs) alineado con catalog_category.
                $catalog->update(['cat_id' => implode(',', array_keys($syncData))]);
            });
        } catch (\Throwable $e) {
            Log::error('CatalogController::syncCategories failed', [
                'catalog_id' => (int) $catalog->catalog_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Resource updated successfully',
            'data' => array_keys($syncData),
        ], Response::HTTP_OK);
    }

    /**
     * Normaliza atributos de create/update: solo fillable, logo válido (FK images.uuid).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function sanitizeCatalogAttributes(array $attributes, ?Catalog $existing = null): array
    {
        $fillable = (new Catalog())->getFillable();
        $clean = [];
        foreach ($fillable as $key) {
            if (! array_key_exists($key, $attributes)) {
                continue;
            }
            $clean[$key] = $attributes[$key];
        }

        if (array_key_exists('logo', $clean)) {
            if (! Schema::hasColumn('catalog', 'logo')) {
                unset($clean['logo']);
            } else {
                $logo = $clean['logo'];
                if ($logo === '' || $logo === false) {
                    $clean['logo'] = null;
                } elseif ($logo !== null) {
                    $logo = trim((string) $logo);
                    if ($logo === '' || ! preg_match('/^[0-9a-f-]{36}$/i', $logo)) {
                        unset($clean['logo']);
                    } elseif (! Image::where('uuid', $logo)->exists()) {
                        // UUID stale / no subido: no romper FK en create/update.
                        Log::warning('CatalogController::sanitizeCatalogAttributes - logo uuid missing in images', [
                            'logo' => $logo,
                            'catalog_id' => $existing?->catalog_id,
                        ]);
                        unset($clean['logo']);
                    } else {
                        $clean['logo'] = $logo;
                    }
                }
            }
        }

        if (isset($clean['attach']) && is_string($clean['attach']) && strlen($clean['attach']) > 255) {
            $clean['attach'] = substr($clean['attach'], 0, 255);
        }
        if (isset($clean['cabeza_pagina']) && is_string($clean['cabeza_pagina']) && strlen($clean['cabeza_pagina']) > 255) {
            $clean['cabeza_pagina'] = substr($clean['cabeza_pagina'], 0, 255);
        }

        if (isset($clean['cat_id']) && is_array($clean['cat_id'])) {
            $clean['cat_id'] = implode(',', array_map('intval', $clean['cat_id']));
        }
        if (! isset($clean['cat_id']) && $existing === null) {
            $clean['cat_id'] = '';
        }

        return $clean;
    }

    /**
     * @param $catalogId
     * @return Builder
     */
    public function buildProductsQuery($catalogId): Builder
    {
        $categoryId = request()->input('category_id');
        $sortOrder = request()->input('sort', 'name_asc');
        $productSortOptions = ProductSortOptions::all();

        /** @var Catalog $cat */
        $cat = Catalog::where('catalog_id', $catalogId)->sole();
        $companyId = $cat->company_id;
        return Product::query()
            ->with(
                [
                    'images',
                    'categories',
                    'catalogs'
                ]
            )
            ->where('status', 1)
            ->whereNotNull('name')
            ->when(request()->has('term'), function ($query) {
                $searchWords = explode(" ", request()->input('term'));
                $query->where(function ($query) use ($searchWords) {
                    collect($searchWords)->each(function ($word) use ($query) {
                        $query->where(function ($q) use ($word) {
                            $q->where('name', 'like', '%' . $word . '%')
                                ->orWhere('sku', 'like', '%' . $word . '%');
                        });
                    });
                });
            })
            ->where('sale_price', '>', 0)
            ->where(function ($q) use ($catalogId, $companyId, $categoryId) {
                $q->whereHas('categories', function ($query) use ($catalogId, $categoryId) {
                    $query->whereHas('catalogs', function ($query) use ($catalogId) {
                        $query->where('catalog.catalog_id', $catalogId);
                    })->where('category_status', 1);

                    $query->when($categoryId, function ($query) use ($categoryId) {
                        return $query->where('categories.cat_id', $categoryId);
                    });
                })
                    ->where('company_id', $companyId);
            })
            ->orWhereHas('catalogs', function ($query) use ($catalogId) {
                $query->where('catalog.catalog_id', $catalogId);
            })
            //ORDER
            ->when(isset($productSortOptions[$sortOrder]), function ($query) use ($productSortOptions, $sortOrder) {
                $order = $productSortOptions[$sortOrder];
                $query->orderBy($order['column'], $order['direction']);
            }, function ($query) {
                $query->orderBy('name');
            });
    }

    #[OA\Get(
        path: "api/v2/catalog/company/{companyId}/customer/{customerId}/price-list-export",
        summary: "Exports to an XLS the price list of a customer grouped by catalog",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalog","Customer"],
        parameters: [
            new OA\Parameter(
                name:        "companyId",
                description: "The Price list export identifier to retrieve.",
                in:          "path",
                required:    true
            ),
            new OA\Parameter(
                name:        "customerId",
                description: "The Price list export identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation, generates an xls file",
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function priceListExport($companyId, $customerId)
    {
        $company = Company::where('company_id', $companyId)->sole();
        $customer = Customer::where('customer_id', $customerId)->sole();

        $products = $this->buildProductsQuery($company->default_catalog()->catalog_id)->get();

        $productList = [];

        $products->map(function ($product) use (&$productList, $customer) {
            /** @var Product $product */
            $categories = $product->categories->pluck('category_name')->implode(', ');

            $productList[] = [
                $product->sku,
                str_replace("\"", "`", $product->name),
                $categories,
                $product->getPriceForCustomer($customer)
            ];
        });

        return Excel::download(
            new PriceListExport($productList),
            "PriceList-" . date("Ymd-his") . ".xlsx",
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    #[OA\Put(
        path: "api/v2/catalog/{catalogId}/products",
        summary: "Bulk add products to a catalog",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/ProductRequest'
                      )
        ),
        tags: ["Catalog","Product"],
        parameters: [
            new OA\Parameter(
                name:        "catalogId",
                description: "The Product identifier to update.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Product'
                                                 ),
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource updated successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function assignProducts($catalogId)
    {
        $productIds = ProductBulkSelection::resolveIds(request());
        /** @var Catalog $catalog */
        $catalog = Catalog::where('catalog_id', $catalogId)
            ->sole();
        $catalog->products()->syncWithoutDetaching($productIds->all());
    }

    #[OA\Delete(
        path: "api/v2/catalog/{catalogId}/products",
        summary: "Bulk removes products from a catalog",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product","Catalog"],
        parameters: [
            new OA\Parameter(
                name:        "catalogId",
                description: "The Product identifier to remove.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource deleted successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function removeProducts($categoryId)
    {
        $productIds = ProductBulkSelection::resolveIds(request());
        if ($productIds->isEmpty()) {
            return 0;
        }
        $deleted = CatalogProduct::query()
            ->where('category_id', $categoryId)
            ->whereIn('product_id', $productIds)
            ->forceDelete();
        return $deleted;
    }

}
