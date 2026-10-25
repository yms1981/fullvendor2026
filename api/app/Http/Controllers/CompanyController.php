<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Enums\CompanyUserTypes;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Company;
use App\Models\Currency;
use App\Models\FormatNumeric;
use App\Models\Plan;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CompanyBootstrapService;
use App\Services\CompanyManagerPasswordService;
use App\Services\CompanyDeletionGuard;
use App\Services\CompanyPlanLimitService;
use App\Services\CompanyPlanSyncService;
use App\Services\CurrencyCatalogService;
use App\Services\FullVendorCatalogService;
use App\Services\OrderCatalogCartSessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use OpenApi\Attributes as OA;

class CompanyController extends Controller
{
    protected $resourceRoute = 'company';

    #[OA\Get(
        path: "api/v2/company",
        summary: "Display a listing of the Company.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Company')
                    ),
                    new OA\Property(
                property: 'meta',
                type: 'object',
                properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'from', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                    new OA\Property(property: 'to', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer')
                ]
            ),
            new OA\Property(
                property: 'links',
                type: 'object',
                properties: [
                    new OA\Property(property: 'first', type: 'string'),
                    new OA\Property(property: 'last', type: 'string'),
                    new OA\Property(property: 'prev', type: 'string', nullable: true),
                    new OA\Property(property: 'next', type: 'string', nullable: true)
                ]
            )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
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
        path: "api/v2/company",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CompanyRequest'
            )
        ),
        tags: ["Company"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Company'
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource created successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function store(StoreCompanyRequest $request): \Illuminate\Http\Resources\Json\JsonResource
    {
        $attributes = $this->normalizeCompanyStoreAttributes($request->all());

        return $this->createFromAttributes($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeCompanyStoreAttributes(array $attributes): array
    {
        if (array_key_exists('currency', $attributes)) {
            $resolved = app(CurrencyCatalogService::class)
                ->resolveStoreCurrencyId($attributes['currency']);
            if ($resolved === null || $resolved <= 0) {
                unset($attributes['currency']);
            } else {
                $attributes['currency'] = $resolved;
            }
        }

        if (isset($attributes['language_id'])) {
            $attributes['language_id'] = (int) $attributes['language_id'];
        }

        if (isset($attributes['company_category_id']) && $attributes['company_category_id'] === '') {
            $attributes['company_category_id'] = null;
        }

        return $attributes;
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/company/{company}",
        summary: "Display the specified Company.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "company",
                description: "The Company identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Company'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function show($company)
    {
        return $this->getMainModelInstance($company);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/company/{company}",
        summary: "Updates the specified Company.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CompanyRequest'
            )
        ),
        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "company",
                description: "The Company identifier to update.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Company'
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource updated successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $attributes = $this->normalizeCompanyStoreAttributes($request->all());
        $passwordChanged = array_key_exists('password', $attributes)
            && trim((string) ($attributes['password'] ?? '')) !== '';

        $model = $this->getMainModelInstance($company->company_id);
        $result = $this->updateModel($model, $attributes);

        if ($passwordChanged) {
            $model->refresh();
            CompanyManagerPasswordService::syncFromCompany($model);
        }

        return $result;
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/company/{company}",
        summary: "Remove the specified Company.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "company",
                description: "The Company identifier to remove.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource deleted successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function destroy(Company $company): ?Response
    {
        $this->authorize('delete', $company);

        $blockKey = CompanyDeletionGuard::blockReasonTranslationKey($company);
        if ($blockKey !== null) {
            return response()->json([
                'message' => $this->langCmpAdminCompanyString($blockKey),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Verificar si hay compañías que referencian esta compañía como creadora
        $childCompanies = Company::where('createdby_comp_id', $company->company_id)->get();

        if ($childCompanies->count() > 0) {
            // Actualizar las compañías hijas para que su createdby_comp_id sea null
            // ya que la compañía padre está siendo eliminada
            Company::where('createdby_comp_id', $company->company_id)
                ->update(['createdby_comp_id' => null]);
        }

        return $this->destroyResourceEmptyResponse($company);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Get(
        path: "api/v2/company/{companyId}/createdBy",
        summary: "Remove the specified Company.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The CreatedBy identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Company'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function createdBy($companyId)
    {
        return Company::where('createdby_comp_id', $companyId)->get();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/contacts",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Contact identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Companycontact'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function contacts($companyId)
    {
        return Company::where('company_id', $companyId)->sole()->company_contacts()->get();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/currency",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Currency identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Currency'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function currency($companyId)
    {
        /** @var Company $company */
        $company = $this->getMainModelInstance($companyId);
        return $company->currencyModel ?? Currency::query()->first();
    }

    /**
     * Datos para validar suscripción/plan y límites de la empresa al iniciar sesión en el panel company.
     * El MVC (Company_plan_validator) consume esto para construir los mensajes traducidos; aquí no hay
     * lógica de presentación, solo los datos crudos para evitar acceso directo a BD desde el MVC.
     */
    public function planValidation($companyId)
    {
        $companyId = (int) $companyId;
        if ($companyId < 1) {
            return response()->json(['message' => 'Invalid company id'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $hasSubscriptionTables = Schema::hasTable('subscriptions') && Schema::hasTable('plan');

        $subscriptionData = null;
        $planData = null;

        if ($hasSubscriptionTables) {
            $subscription = Subscription::query()
                ->where('company_id', $companyId)
                ->where('status', 1)
                ->orderByDesc('subscription_id')
                ->first();

            if ($subscription !== null) {
                $subscriptionData = [
                    'plan_id' => (int) $subscription->plan_id,
                    'status' => (int) $subscription->status,
                    'end_period_date' => $subscription->end_period_date
                        ? (string) $subscription->end_period_date
                        : null,
                ];

                $plan = Plan::query()->where('plan_id', (int) $subscription->plan_id)->first();
                if ($plan !== null) {
                    // Plan completo: el panel admin (company_edit) usa max_clients/max_orders/max_users/title, etc.
                    $planData = $plan->toArray();
                    $planData['status'] = isset($plan->status) ? (int) $plan->status : 1;
                    $planData['neverexpire'] = (int) ($plan->neverexpire ?? 0);
                }
            }
        }

        $usage = app(CompanyPlanLimitService::class)->usage($companyId);

        return response()->json([
            'has_subscription_tables' => $hasSubscriptionTables,
            'subscription' => $subscriptionData,
            'plan' => $planData,
            'limits' => [
                'sellers' => [
                    'current' => (int) ($usage['sellers']['current'] ?? 0),
                    'max' => (int) ($usage['sellers']['max'] ?? 0),
                ],
                'customers' => [
                    'current' => (int) ($usage['customers']['current'] ?? 0),
                    'max' => (int) ($usage['customers']['max'] ?? 0),
                ],
                'orders_month' => [
                    'current' => (int) ($usage['orders_month']['current'] ?? 0),
                    'max' => (int) ($usage['orders_month']['max'] ?? 0),
                ],
            ],
        ]);
    }

    /**
     * List all available (enabled) currencies for the given a company
     * @param $companyId
     * @return mixed
     */
    #[OA\Get(
        path: "api/v2/company/{companyId}/currencies",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Currency identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Currency'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function currencies($companyId)
    {
        return Company::where('company_id', $companyId)
            ->sole()
            ->currencies()
            ->where('status', 1)
            ->get();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/currency-code",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Currency code identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'code', type: 'string', example: 'USD'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function currencyCode($companyId)
    {
        try {
            /** @var Company $company */
            $company = Company::where('company_id', $companyId)->first();
            
            if (!$company) {
                // Si no se encuentra la compañía, obtener la primera moneda disponible
                $defaultCurrency = Currency::query()->first();
                return $defaultCurrency ? ($defaultCurrency->code ?? '') : '';
            }
            
            // Intentar cargar la relación currency
            $company->load('currencyModel');
            
            // Intentar obtener el código de la moneda de la compañía
            if ($company->currencyModel && isset($company->currencyModel->code)) {
                return $company->currencyModel->code;
            }
            
            // Si no tiene moneda asociada, obtener la primera moneda disponible
            $defaultCurrency = Currency::query()->first();
            if ($defaultCurrency && isset($defaultCurrency->code)) {
                return $defaultCurrency->code;
            }
            
            // Si no hay monedas, devolver un valor por defecto
            return '';
        } catch (\Exception $e) {
            Log::error('Error in currencyCode: ' . $e->getMessage(), [
                'companyId' => $companyId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // En caso de error, intentar devolver una moneda por defecto
            try {
                $defaultCurrency = Currency::query()->first();
                return $defaultCurrency ? ($defaultCurrency->code ?? '') : '';
            } catch (\Exception $e2) {
                Log::error('Error getting default currency: ' . $e2->getMessage());
                return '';
            }
        }
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/currency-symbol",
        summary: "Get the currency symbol for a company",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Currency symbol identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'symbol', type: 'string', example: '$'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function currencySymbol($companyId)
    {
        try {
            /** @var Company $company */
            $company = Company::where('company_id', $companyId)->first();
            
            if (!$company) {
                // Si no se encuentra la compañía, obtener la primera moneda disponible
                $defaultCurrency = Currency::query()->first();
                return $defaultCurrency ? ($defaultCurrency->symbol ?? '') : '';
            }
            
            // Intentar cargar la relación currency
            $company->load('currencyModel');
            
            // Intentar obtener el símbolo de la moneda de la compañía
            if ($company->currencyModel && isset($company->currencyModel->symbol)) {
                return $company->currencyModel->symbol;
            }
            
            // Si no tiene moneda asociada, obtener la primera moneda disponible
            $defaultCurrency = Currency::query()->first();
            if ($defaultCurrency && isset($defaultCurrency->symbol)) {
                return $defaultCurrency->symbol;
            }
            
            // Si no hay monedas, devolver un valor por defecto
            return '';
        } catch (\Exception $e) {
            Log::error('Error in currencySymbol: ' . $e->getMessage(), [
                'companyId' => $companyId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // En caso de error, intentar devolver una moneda por defecto
            try {
                $defaultCurrency = Currency::query()->first();
                return $defaultCurrency ? ($defaultCurrency->symbol ?? '') : '';
            } catch (\Exception $e2) {
                Log::error('Error getting default currency: ' . $e2->getMessage());
                return '';
            }
        }
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/curconfig",
        summary: "Get the complete currency configuration for a company",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Company identifier to retrieve currency configuration.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Currency'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function curconfig($companyId)
    {
        /** @var Company $company */
        $company = Company::where('company_id', $companyId)->sole();
        $currency = $company->currencyModel ?? Currency::query()->first();
        
        // Devolver el objeto Currency completo usando CurrencyResource para formato consistente
        return new \App\Http\Resources\CurrencyResource($currency);
    }

    #[OA\Get(
        path: "api/v2/company/slug-{slug}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "slug",
                description: "The Company identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Company'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function getBySlug(string $slug)
    {
        return Company::where('slug_url', $slug)->sole();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/units",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Unit identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Unit'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function units($companyId)
    {
        return Company::where('company_id', $companyId)->sole()->units()->get();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/units/default",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Default identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Unit'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function defaultUnit($companyId)
    {
        return $this->getMainModelInstance($companyId)->defaultUnit()->get();
    }

    /**
     * Returns the products of the given company (all statuses by default, enabled if specified)
     * @param $companyId
     * @return mixed
     */
    #[OA\Get(
        path: "api/v2/company/{companyId}/products",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Product identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Product'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function products($companyId)
    {
        $productsQuery = $this->getMainModelInstance($companyId)
            ->products();

        if(\request()->has('limit'))
            $productsQuery->limit(\request()->get('limit'));
        if (\request()->has('enabled')) {
            $productsQuery->where('status', 1);
        }
        return $productsQuery
            ->with(['default_image', 'product_images.image'])
            ->get();
    }

    /**
     * Returns the count of the given company (all statuses by default, enabled if specified)
     * @param $companyId
     * @return mixed
     */
    #[OA\Get(
        path: "api/v2/company/{companyId}/products/count",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Count identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'integer'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function productsCount($companyId)
    {
        /** @var Company $company */
        $company = $this->getMainModelInstance($companyId);
        $productsQuery = $company
            ->products();
        if (\request()->has('enabled')) {
            $productsQuery->where('status', 1);
        }
        return $productsQuery->count();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/categories",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Category identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Category'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function categories(Request $request, $companyId)
    {
        if ($request->has('withProducts')) {
            return Category::select('categories.cat_id', 'categories.category_name')
                ->selectRaw('COUNT(products.pro_id) as products_count')
                ->join('category_product', 'category_product.category_id', '=', 'categories.cat_id')
                ->join('products', 'products.pro_id', '=', 'category_product.product_id')
                ->where([
                            ['products.status', 1],
                            ['products.sale_price', '>', 0],
                            ['categories.company_id', $companyId],
                            ['categories.category_status', 1]
                        ])
                ->whereNull('categories.category_default')
                ->groupBy(['categories.cat_id', 'categories.category_name'])
                ->having('products_count', '>', 0)
                ->orderBy('category_name', 'desc')
                ->get();
        }

        return $this->getMainModelInstance($companyId)
            ->categories()
            ->where('category_status', 1)
            ->get();
    }

    public function catalogs($companyId, Request $request)
    {
        Log::info('CATALOGS API CALL', [
            'companyId' => $companyId,
            'all' => $request->has('all'),
            'all_value' => $request->input('all'),
            'status_filter' => $request->input('status_filter'),
            'page' => $request->input('page'),
            'per_page' => $request->input('per_page'),
            'props' => $request->input('props'),
        ]);

        // Recrear / restaurar FullVendor Catalog si falta (p. ej. borrado manual en BD).
        try {
            app(FullVendorCatalogService::class)->sync((int) $companyId);
        } catch (\Throwable $e) {
            Log::warning('CompanyController::catalogs - FullVendor sync failed', [
                'company_id' => (int) $companyId,
                'error' => $e->getMessage(),
            ]);
        }
        
        // Consultar la tabla catalog filtrando por company_id
        $query = Catalog::query()->where('company_id', $companyId)->whereNull('deleted_at');
        
        // Si all=1, incluir todos los catálogos (incluso default_catalog)
        // Si no tiene all o all=0, excluir default_catalog
        if (!$request->has('all') || $request->input('all') != '1') {
            $query->whereNull('default_catalog');
            Log::info('CATALOGS FILTER - Excluyendo default_catalog');
        } else {
            Log::info('CATALOGS FILTER - Incluyendo todos los catálogos (all=1)');
        }
        
        // Solo filtrar por status si NO se pasa 'all=1' o si status_filter=1 explícitamente
        // Si all=1 y NO hay status_filter, NO filtrar por status para mostrar todos (activos e inactivos)
        $hasAll = $request->has('all') && $request->input('all') == '1';
        $hasStatusFilter = $request->input('status_filter') === '1';
        
        if (!$hasAll || $hasStatusFilter) {
            $query->where('status', 1);
            Log::info('CATALOGS FILTER - Filtrando por status=1', ['hasAll' => $hasAll, 'hasStatusFilter' => $hasStatusFilter]);
        } else {
            Log::info('CATALOGS FILTER - NO filtrando por status (mostrando todos)', ['hasAll' => $hasAll]);
        }

        // Manejar ordenamiento
        $orderProperty = $request->input('orderProperty', 'catalog_name');
        $orderDirection = $request->input('orderDirection', 'asc');
        if ($orderProperty) {
            $query->orderBy($orderProperty, $orderDirection);
        } else {
            $query->orderBy('catalog_name', 'asc');
        }

        // Manejar props (campos específicos)
        $props = $request->input('props', ['*']);
        // Si props viene como array desde query string, convertirlo correctamente
        if (is_string($props)) {
            $props = json_decode($props, true) ?? [$props];
        }
        if ($props !== ['*'] && is_array($props) && !empty($props)) {
            // Asegurar que catalog_id esté siempre incluido para evitar problemas
            if (!in_array('catalog_id', $props)) {
                $props[] = 'catalog_id';
            }
            // Asegurar que company_id NO esté en el SELECT (solo se usa en WHERE)
            $props = array_filter($props, function($prop) {
                return $prop !== 'company_id';
            });
            $query->select($props);
        }

        // Log de la consulta SQL antes de ejecutar
        Log::info('CATALOGS QUERY SQL', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        
        // Contar total antes de paginar (clonar query para no afectar la paginación)
        $countQuery = clone $query;
        $totalCount = $countQuery->count();
        Log::info('CATALOGS TOTAL COUNT', ['count' => $totalCount]);
        
        // Manejar paginación
        if ($request->has('page') || $request->has('per_page')) {
            $perPage = $request->input('per_page', 30);
            $page = $request->input('page', 1);
            $result = $query->paginate($perPage, ['*'], 'page', $page);
            
            Log::info('CATALOGS PAGINATED RESULT', [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'data_count' => $result->count(),
                'first_item' => $result->count() > 0 ? $result->first() : null,
            ]);
            
            return $result;
        }

        // Si no hay paginación, devolver todos
        $result = $query->get();
        
        Log::info('CATALOGS GET RESULT', [
            'count' => $result->count(),
            'first_item' => $result->count() > 0 ? $result->first() : null,
        ]);
        
        return $result;
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/catalogs/default",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Default identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Catalog'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function defaultCatalog($companyId)
    {
        try {
            app(FullVendorCatalogService::class)->sync((int) $companyId);
        } catch (\Throwable $e) {
            Log::warning('CompanyController::defaultCatalog - FullVendor sync failed', [
                'company_id' => (int) $companyId,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getMainModelInstance($companyId)
            ->catalogs()
            ->where('default_catalog', 1)
            ->sole();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/categories/default",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Default identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Category'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function defaultCategory($companyId)
    {
        return $this->getMainModelInstance($companyId)
            ->categories()
            ->where('category_default', 1)
            ->sole();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/customer-groups",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Customer group identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Customergroup'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function customerGroups($companyId, Request $request)
    {
        return $this->getMainModelInstance($companyId)
            ->customer_groups()
            ->when(
                !$request->has('all'),
                fn($query) => $query->where('group_status', 1)
            )
            ->orderBy('name', 'ASC')
            ->get();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/customer-groups/default",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Default identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Customergroup'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function defaultCustomerGroup($companyId)
    {
        return $this->getMainModelInstance($companyId)
            ->customer_groups()
            ->where('default', 1)
            ->sole();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/customers",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Customer identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Customer'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function customers($companyId)
    {
        /** @var Company $company */
        $company = $this->getMainModelInstance($companyId);
        $onlyActive = filter_var(
            request()->query('only_active', request()->query('enabled', '1')),
            FILTER_VALIDATE_BOOLEAN
        );

        $query = $company
            ->customers()
            ->when($onlyActive, static fn ($q) => $q->where('customer_status', 1));

        $allCustomersPageSize = max(1, (clone $query)->count());

        return $query
            ->orderBy('business_name')
            ->select([
                'cust_id_kor',
                'customer_id',
                'tax_id',
                'business_name',
                'name',
                'email',
                'phone',
                'cell_phone',
                'commercial_address',
                'commercial_state',
                'commercial_city',
                'commercial_zone',
                'commercial_zip_code',
                'commercial_country',
                'dispatch_address',
                'dispatch_state',
                'dispatch_city',
                'dispatch_zone',
                'dispatch_zip_code',
                'dispatch_country',
                'dispatch_delivery_address',
            ])
            ->selectRaw("CONCAT(COALESCE(business_name,''),' (',COALESCE(name,''),')') AS customer_name")
            ->paginate($allCustomersPageSize);
    }

    #[OA\Post(
        path: "api/v2/company/{companyId}/units/default",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/UnitRequest'
            )
        ),
        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Default identifier.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Unit'
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource created successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function updateDefaultUnit(UpdateCompanyRequest $request, $companyId)
    {
        $unitId = (int) $request->input('unit_id');
        Company::where('company_id', $companyId)->update(['default_unit_id' => $unitId]);

        return $this->makeResource(Company::where('company_id', $companyId)->sole());
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/customers/count",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Count identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'integer'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function customersCount($companyId)
    {
        $onlyActive = filter_var(
            request()->query('only_active', request()->query('enabled', '1')),
            FILTER_VALIDATE_BOOLEAN
        );

        $query = $this->getMainModelInstance($companyId)->customers();
        if ($onlyActive) {
            $query->where('customer_status', 1);
        }

        return $query->count();
    }

    /**
     * Uso actual vs límites del plan (companies.max_number_of_*).
     */
    public function planLimits(int $companyId, CompanyPlanLimitService $planLimits): JsonResponse
    {
        $this->getMainModelInstance($companyId);

        return response()->json($planLimits->usage($companyId));
    }

    /**
     * Recalcula companies.max_number_of_* desde la suscripción activa más reciente (admin).
     */
    public function syncPlanLimits(int $companyId, CompanyPlanSyncService $sync, Request $request): JsonResponse
    {
        if ($request->user() === null || ! $request->user()->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->getMainModelInstance($companyId);
        $synced = $sync->syncFromActiveSubscription($companyId);

        return response()->json([
            'data' => [
                'synced' => $synced,
            ],
        ]);
    }

    /**
     * POST /api/v2/company/{companyId}/init-defaults
     *
     * Categoría, grupo, término, perfil, vendedor, unidad y almacén por defecto al crear compañía (admin).
     */
    public function initDefaults(int $companyId, CompanyBootstrapService $bootstrap, Request $request): JsonResponse
    {
        if ($request->user() === null || ! $request->user()->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->getMainModelInstance($companyId);

        try {
            $created = $bootstrap->bootstrap(
                $companyId,
                $request->input('password') !== null ? (string) $request->input('password') : null
            );

            return response()->json(['data' => ['created' => $created]]);
        } catch (\Throwable $e) {
            Log::error('CompanyController::initDefaults', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'error', 'error' => true], 500);
        }
    }

    public function termsOfSales($companyId)
    {
        return $this->getMainModelInstance($companyId)
            ->terms_of_sales()
            ->where('term_status', 1)
            ->get();
    }


    #[OA\Get(
        path: "api/v2/company/{companyId}/warehouse-users",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Warehouse user identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Warehouseuser'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function warehouseUsers($companyId)
    {
        return response()->json([
            'data' => $this->legacyWarehouseUserListForCompany((int) $companyId, false),
        ]);
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/warehouse-users-actives",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Warehouse users active identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Warehouseuser'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function warehouseUsersActives($companyId)
    {
        return response()->json([
            'data' => $this->legacyWarehouseUserListForCompany((int) $companyId, true),
        ]);
    }

    /**
     * Misma forma que esperaba el MVC (user_id, first_name, last_name, email): rol warehouse en company_user + users.
     *
     * @return list<array{user_id:int, first_name:string, last_name:string, email:?string}>
     */
    private function legacyWarehouseUserListForCompany(int $companyId, bool $onlyNonDeletedUsers): array
    {
        $q = DB::table('company_user as cu')
            ->join('users as u', 'u.id', '=', 'cu.user_id')
            ->where('cu.company_id', $companyId)
            ->where('cu.type', CompanyUserTypes::Warehouse->value)
            ->when($onlyNonDeletedUsers, fn ($q) => $q->whereNull('u.deleted_at'))
            ->orderBy('u.name')
            ->select('u.id as user_id', 'u.name', 'u.email')
            ->distinct();

        return $q->get()->map(function ($row) {
            $name = trim((string) $row->name);
            $parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);

            return [
                'user_id' => (int) $row->user_id,
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
                'email' => $row->email,
            ];
        })->values()->all();
    }

    /**
     * Usuarios con rol warehouse en la compañía (para asignar orden a usuario de depósito).
     */
    public function warehouseUsersForOrderAssignment(int $companyId, Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canAccessCompany($companyId)) {
            return response()->json(['message' => 'Forbidden', 'error' => true], 403);
        }

        $rows = DB::table('company_user as cu')
            ->join('users as u', 'u.id', '=', 'cu.user_id')
            ->where('cu.company_id', $companyId)
            ->where('cu.type', CompanyUserTypes::Warehouse->value)
            ->orderBy('u.name')
            ->select('u.id as user_id', 'u.name', 'u.email')
            ->distinct()
            ->get();

        return response()->json(['data' => $rows]);
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/isAssignedToUser/{rolId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The IsAssignedToUser identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "rolId",
                description: "The IsAssignedToUser identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'boolean'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function isAssignedToUser($companyId, $rolId)
    {
        return $this->getBuilder()
            ->whereNotNull('createdby_comp_id')
            ->where('createdby_comp_id', $companyId)
            ->where('rol_id', $rolId)
            ->exists();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/orders",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Order identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Order'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function orders($companyId, Request $request)
    {
        /** @var Company $company */
        $company = $this->getMainModelInstance($companyId);
        $ordersQuery = $company
            ->orders()
            ->with([
                'customer:customer_id,business_name,name,group_id,email,phone,cell_phone,dispatch_delivery_address,dispatch_address,dispatch_city,dispatch_state,dispatch_country,dispatch_zone,dispatch_zip_code',
                'customer.customer_group:group_id,name',
                'seller:id,user_id',
                'seller.user:id,name',
                'warehouse_user:user_id,company_id,warehouse_id',
                'warehouse_user.user:id,name',
                'statusOrder',
            ])
            ->select([
                         'orders.*',
                         DB::raw('IFNULL(orders.updated, orders.created) AS fechaorden'),
                         DB::raw(
                             "(SELECT sum(order_details.qty * order_details.sale_price) FROM order_details where order_details.order_id = orders.order_id) AS totsale"
                         ),
                     ]);

        // Por defecto: desde 30 días atrás, hasta hoy (inclusivo)
        $fromDate = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $toDate = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();
        $ordersQuery
            ->whereRaw('IFNULL(orders.updated, orders.created) >= ?', [$fromDate])
            ->whereRaw('IFNULL(orders.updated, orders.created) <= ?', [$toDate])
            ->when(
                $request->filled('sellers'),
                fn($query) => $query->whereIn('user_id', $request->input('sellers'))
            )
            ->when(
                $request->filled('source') && $request->input('source') != 'todo',
                fn($query) => $query->where('source', $request->input('source'))
            )
            ->when(
                $request->filled('status'),
                function ($query) use ($request) {
                    $codStatuses = array_map('intval', explode('-', (string) $request->input('status')));
                    if (Schema::hasColumn('orders', 'status_id')) {
                        $idStatuses = StatusOrder::query()
                            ->whereIn('cod_status', $codStatuses)
                            ->pluck('id_status')
                            ->toArray();
                        $hasId = ! empty($idStatuses);
                        $hasLegacy = Schema::hasColumn('orders', 'order_status');
                        if ($hasId || $hasLegacy) {
                            $query->where(function ($q) use ($idStatuses, $codStatuses, $hasId, $hasLegacy) {
                                if ($hasId) {
                                    $q->whereIn('orders.status_id', $idStatuses);
                                }
                                if ($hasLegacy) {
                                    if ($hasId) {
                                        $q->orWhereIn('orders.order_status', $codStatuses);
                                    } else {
                                        $q->whereIn('orders.order_status', $codStatuses);
                                    }
                                }
                            });
                        } else {
                            $query->whereRaw('1 = 0');
                        }
                    } elseif (Schema::hasColumn('orders', 'order_status')) {
                        $query->whereIn('orders.order_status', $codStatuses);
                    }
                }
            )
            ->when(
                $request->filled('group') && $request->input('group') != 'todo',
                fn($query) => $query->whereHas('customer', fn($q) => $q->where('group_id', $request->input('group')))
            )
            ->when(
                $request->filled('catalog'),
                fn($query) => $query->where('catalog_id', $request->input('catalog'))
            )
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->input('search');
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('order_number', 'LIKE', "%{$search}%")
                            ->orWhereHas('customer', fn($q) => $q->where('business_name', 'LIKE', "%{$search}%"));
                        //revisar esta relacion
                        //->orWhereHas('vendor', fn($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]));
                    });
                }
            )
            ->when(
                $request->boolean('require_warehouse_id'),
                function ($query) {
                    if (Schema::hasColumn('orders', 'warehouse_id')) {
                        $query->where('orders.warehouse_id', '>', 0);
                    }
                }
            )
            ->when(
                $request->filled('customer_id'),
                fn ($query) => $query->where('orders.customer_id', (int) $request->input('customer_id'))
            )
            ->when(
                $request->filled('warehouse_user_id'),
                function ($query) use ($request) {
                    $uid = (int) $request->input('warehouse_user_id');
                    $query->where(function ($q) use ($uid) {
                        $q->where('orders.warehouse_user_id', $uid);
                        if (Schema::hasColumn('orders', 'warehouse_id')) {
                            $q->orWhere('orders.warehouse_id', $uid);
                        }
                    });
                }
            )
            ->orderByDesc('order_id');

        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', $request->input('perPage', 25))));
        $orders = $ordersQuery->paginate($perPage, ['*'], 'page', $page);


        $transformedOrders = $orders->getCollection()->map(function ($order) {
            $ord = (object)$order;
            $customer = $order->customer;
            $ord->nombre_cliente = $customer->business_name ?? null;
            $ord->nombre_contacto = $customer->name ?? null;
            $ord->customer_email = $customer->email ?? null;
            $ord->customer_phone = $customer->phone ?? null;
            $ord->customer_cell_phone = $customer->cell_phone ?? null;
            $addr = !empty($customer->dispatch_delivery_address)
                ? $customer->dispatch_delivery_address
                : trim(implode(', ', array_filter([
                    $customer->dispatch_address ?? null,
                    $customer->dispatch_city ?? null,
                    $customer->dispatch_state ?? null,
                    $customer->dispatch_zone ?? null,
                    $customer->dispatch_zip_code ?? null,
                    $customer->dispatch_country ?? null,
                  ])));
            $ord->customer_address = $addr !== '' ? $addr : null;
            $ord['grupo_cliente'] = $customer->customer_group->name ?? null;
            $seller = $order->seller;
            $sellerLine = '';
            if ($seller?->user) {
                $sellerLine = trim((string) ($seller->user->name ?? ''));
            }
            if ($sellerLine === '' && $seller) {
                $sellerLine = trim(($seller->first_name ?? '').' '.($seller->last_name ?? ''));
            }
            if ($sellerLine === '' && $seller && isset($seller->name)) {
                $sellerLine = (string) $seller->name;
            }
            $ord['seller_info'] = $sellerLine !== '' ? $sellerLine : null;

            // Almacenero = users.id (warehouse_user_id o warehouse_id legado).
            $whLine = '';
            $whUserId = (int) ($order->warehouse_user_id ?? 0);
            if ($whUserId <= 0) {
                $whUserId = (int) ($order->warehouse_id ?? 0);
            }
            if ($order->warehouse_user?->user) {
                $whLine = trim((string) ($order->warehouse_user->user->name ?? ''));
                if ($whLine === '') {
                    $whLine = trim((string) ($order->warehouse_user->user->email ?? ''));
                }
            }
            if ($whLine === '' && $whUserId > 0) {
                $u = User::query()->find($whUserId);
                if ($u) {
                    $whLine = trim((string) ($u->name ?? ''));
                    if ($whLine === '') {
                        $whLine = trim((string) ($u->email ?? ''));
                    }
                }
            }
            $ord['warehouse_user_name'] = $whLine !== '' ? $whLine : null;
            $ord['warehouse_user_display'] = $whLine !== '' ? $whLine : null;
            $ord['warehouse_user_id'] = $whUserId > 0 ? $whUserId : ($order->warehouse_user_id ?? null);
            // El listado MVC lee warehouse.name; forzar el nombre del usuario de almacén.
            if ($whLine !== '') {
                $ord['warehouse'] = (object) ['name' => $whLine];
            }


            $total_orden = $ord['totsale'];
            $discount_total = 0;
            if ($order['discount_type'] == 1) {
                $discount_total = $total_orden * ($order['discount'] / 100);
            } elseif ($order['discount_type'] == 2) {
                $discount_total = $order['discount'];
            }

            $ord->totsale = $total_orden - $discount_total;
            $ord->order_status = (int) ($order->statusOrder->cod_status ?? $order->order_status ?? 0);

            $ord->source = $order->source;
            $ord->order_comments = $order->order_comments;
            $ord->created = $order->created
                ? $order->created->format('Y-m-d H:i:s')
                : null;
            if ($ord->created === null && $order->updated) {
                $ord->created = $order->updated->format('Y-m-d H:i:s');
            }
            $ord->updated = $order->updated
                ? $order->updated->format('Y-m-d H:i:s')
                : null;

            $listDateCreated = $order->created
                ? $order->created->format('Y-m-d H:i:s')
                : null;
            $listDateUpdated = $order->updated
                ? $order->updated->format('Y-m-d H:i:s')
                : null;
            if ($listDateCreated === null && $listDateUpdated !== null) {
                $listDateCreated = $listDateUpdated;
            }
            $ord->list_date_created = $listDateCreated;
            $ord->list_date_updated = $listDateUpdated;

            return $ord;
        });

        $ordersForDisplay = $orders->setCollection($transformedOrders);


        return $ordersForDisplay;
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/users",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The User identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/User'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function users($companyId, Request $request)
    {
        $type = $request->get('type');

        /** @var Company $company */
        $company = $this->getMainModelInstance($companyId);
        $usersQuery = $company->users();
        if (isset($type)) {
            $usersQuery->wherePivot('type', $type);
        }

        return $usersQuery->paginate();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/sellers",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Seller identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/User'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function sellers($companyId)
    {
        $req = \request();
        $req->merge(['type' => 'seller']);
        return $this->users($companyId, $req);
    }


    #[OA\Get(
        path: "api/v2/company/{companyId}/catalogs",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Catalog identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Catalog'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function findCatalog($companyId)
    {
        $catalogName = request()->input('catalog_name');
        if (strpos($catalogName, '%') === false) {
            $catalogName = "%{$catalogName}%";
        }
        $onlyCheckIfExist = request()->input('exists');

        /** @var Company $company */
        $company = $this->getBuilder()->find($companyId);
        $catalogQuery = $company->catalogs()->where('catalog_name', 'like', $catalogName);
        return $onlyCheckIfExist ? $catalogQuery->count() > 0 : $catalogQuery->get();
    }


    public function formatNumeric(Company $company) {
        return $company?->format_numeric ?? FormatNumeric::default();
    }

    /**
     * Texto cmp-admin-company desde lang/*.json según language_id del usuario autenticado.
     */
    private function langCmpAdminCompanyString(string $key, string $fallback = ''): string
    {
        $user = auth()->user();
        $langId = $user && isset($user->language_id) ? (int) $user->language_id : 1;
        $map = [
            1 => 'en-EN.json',
            2 => 'es-ES.json',
            3 => 'pt-PT.json',
            4 => 'it-IT.json',
            5 => 'ru-RU.json',
            6 => 'de-DE.json',
            7 => 'zh-CN.json',
        ];
        $file = $map[$langId] ?? 'en-EN.json';
        $path = lang_path($file);
        if (! is_readable($path)) {
            return $fallback !== '' ? $fallback : $key;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $fallback !== '' ? $fallback : $key;
        }
        $data = json_decode($raw, true);
        if (! is_array($data) || ! isset($data['cmp-admin-company'][$key]) || ! is_string($data['cmp-admin-company'][$key])) {
            return $fallback !== '' ? $fallback : $key;
        }

        return $data['cmp-admin-company'][$key];
    }

    public function emailExists(string $email): \Illuminate\Http\JsonResponse
    {
        $normalized = trim(urldecode($email));
        $exists = $normalized !== '' && Company::query()->where('email', $normalized)->exists();

        return response()->json(['exists' => $exists]);
    }

    public function phoneExists(string $phone): \Illuminate\Http\JsonResponse
    {
        $normalized = trim(urldecode($phone));
        $exists = $normalized !== '' && Company::query()->where('phone_number', $normalized)->exists();

        return response()->json(['exists' => $exists]);
    }

    public function nameExists(string $name): \Illuminate\Http\JsonResponse
    {
        $normalized = trim(urldecode($name));
        $exists = $normalized !== '' && Company::query()->where('name', $normalized)->exists();

        return response()->json(['exists' => $exists]);
    }

    public function pendingStripeCustomersCount(): \Illuminate\Http\JsonResponse
    {
        if (! Schema::hasColumn('companies', 'stripe_id')) {
            return response()->json(['count' => 0]);
        }

        $count = Company::query()
            ->where(static function ($q): void {
                $q->whereNull('stripe_id')->orWhere('stripe_id', '');
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function deletionBlockReason(int $companyId): \Illuminate\Http\JsonResponse
    {
        $company = Company::query()->findOrFail($companyId);
        $blockKey = CompanyDeletionGuard::blockReasonTranslationKey($company);

        return response()->json([
            'block_key' => $blockKey,
            'message' => $blockKey !== null ? $this->langCmpAdminCompanyString($blockKey) : null,
        ]);
    }

    /**
     * POST /api/v2/company/{companyId}/prepare-order-catalog
     *
     * Asegura categoría por defecto y catálogo FullVendor antes de crear una orden
     * desde el modal "Agregar nueva orden" (e-catálogo). Sustituye las consultas
     * directas a BD del MVC (Helper::creat_categorydefault / creat_fullvendorcatalog).
     */
    public function prepareOrderCatalog(int $companyId): JsonResponse
    {
        $user = request()->user();
        if (! $user || ! $user->canAccessCompany($companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        try {
            DB::transaction(function () use ($companyId) {
                $company = Company::findOrFail($companyId);
                $categoryName = $this->langCmpDataDefaultString(
                    'category_default',
                    'Without Category',
                    (int) ($company->language_id ?? 1)
                );

                $defaultCategory = Category::firstOrCreate(
                    ['company_id' => $companyId, 'category_default' => 1],
                    [
                        'category_name'        => $categoryName,
                        'category_status'      => 1,
                        'category_created_at'  => now(),
                    ]
                );

                $catId = (int) $defaultCategory->cat_id;

                if (Schema::hasColumn('products', 'category_id')) {
                    Product::where('company_id', $companyId)
                        ->where(function ($query) {
                            $query->whereNull('category_id')
                                ->orWhere('category_id', '')
                                ->orWhere('category_id', '0');
                        })
                        ->update(['category_id' => (string) $catId]);
                }

                app(FullVendorCatalogService::class)->linkProductsWithoutCompanyCategory($companyId, $catId);
                app(FullVendorCatalogService::class)->sync($companyId);
            });

            return response()->json(['message' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('CompanyController::prepareOrderCatalog', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['message' => 'error', 'error' => true], 500);
        }
    }

    /**
     * POST /api/v2/company/{companyId}/order-catalog-session
     *
     * Crea catalog_cart + token para abrir catalogo/producto tras "Agregar nueva orden → Desde catálogo".
     */
    public function createOrderCatalogSession(int $companyId, Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canAccessCompany($companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        $customerId = (int) $request->input('customer_id', 0);
        if ($customerId <= 0) {
            return response()->json(['message' => 'customer_id_required', 'error' => true], 422);
        }

        $sellerUserId = (int) $request->input('user_id', $request->input('seller_user_id', 0));
        $sellerUserId = $sellerUserId > 0 ? $sellerUserId : null;

        try {
            $session = app(OrderCatalogCartSessionService::class)->create(
                $companyId,
                $customerId,
                $sellerUserId
            );

            return response()->json(['data' => $session]);
        } catch (\RuntimeException $e) {
            $code = match ($e->getMessage()) {
                'default_catalog_missing' => 422,
                'catalog_cart_table_missing' => 422,
                default => 500,
            };

            return response()->json([
                'message' => $e->getMessage(),
                'error'   => true,
            ], $code);
        } catch (\Throwable $e) {
            Log::error('CompanyController::createOrderCatalogSession', [
                'company_id'  => $companyId,
                'customer_id' => $customerId,
                'error'       => $e->getMessage(),
            ]);

            return response()->json(['message' => 'error', 'error' => true], 500);
        }
    }

    /**
     * Texto cmp-data-default desde lang/*.json según language_id de la compañía.
     */
    private function langCmpDataDefaultString(string $key, string $fallback, int $langId): string
    {
        $map = [
            1 => 'en-EN.json',
            2 => 'es-ES.json',
            3 => 'pt-PT.json',
            4 => 'it-IT.json',
            5 => 'ru-RU.json',
            6 => 'de-DE.json',
            7 => 'zh-CN.json',
        ];
        $file = $map[$langId] ?? 'en-EN.json';
        $path = lang_path($file);
        if (! is_readable($path)) {
            return $fallback;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $fallback;
        }
        $data = json_decode($raw, true);
        if (! is_array($data) || ! isset($data['cmp-data-default'][$key]) || ! is_string($data['cmp-data-default'][$key])) {
            return $fallback;
        }

        return $data['cmp-data-default'][$key];
    }

}
