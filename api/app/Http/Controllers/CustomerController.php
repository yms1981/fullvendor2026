<?php

namespace App\Http\Controllers;

use App\Actions\CustomerDeleteService;
use App\Enums\CompanyUserTypes;
use App\Enums\OrderStatusCodes;
use App\Exports\CustomersExport;
use App\Http\Requests\CustomerAssignUserRequest;
use App\Http\Requests\BulkAssignSellerRequest;
use App\Http\Requests\BulkCustomerDeleteRequest;
use App\Http\Requests\BulkSendCatalogRequest;
use App\Http\Requests\BulkUnassignSellerRequest;
use App\Http\Requests\ExportCustomer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreCustomerPublicRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\CustomerUser;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Services\CompanyPlanLimitService;
use App\Services\CustomerPortalSessionCartService;
use App\Services\OrderCatalogCartSessionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

class CustomerController extends Controller
{
    const DECREASE_ON_PRICE = 'Decrease on Price List %';
    const INCREASE_ON_PRICE = 'Increase on Price List %';
    protected $resourceRoute = 'customer';

    /** Orden por defecto: última modificación primero. */
    protected $defaultOrderProperty = 'modified_at';
    protected $defaultOrderDirection = 'DESC';
    protected $defaultOrderPropertySecondary = 'customer_created_at';
    protected $defaultOrderDirectionSecondary = 'DESC';

    #[OA\Get(
        path: "api/v2/customer",
        summary: "Display a listing of the Customer.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Customer')
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
        return $this->getModelList();
    }

    /**
     * Obtener todos los clientes sin paginación de la compañía del usuario logueado.
     * - Manager/admin: todos los de la compañía.
     * - Seller con all_customers=1: todos los de la compañía.
     * - Seller sin all_customers: solo clientes asignados a ese vendedor.
     */
    #[OA\Get(
        path: "api/v2/customer/all",
        summary: "Listar clientes de la compañía (todos o filtrados por seller según all_customers)",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                in: 'query',
                required: false,
                description: 'ID de compañía (si no se envía, se toma la del usuario)',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'props',
                in: 'query',
                required: false,
                description: 'Campos a retornar (array o string separado por comas)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Lista de clientes",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Customer')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "No autenticado"
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: "No se pudo determinar la compañía del usuario"
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: "Usuario seller sin registro de vendedor en la compañía"
            )
        ]
    )]
    public function all(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $companyId = $request->input('company_id') ?? $request->input('company');

        if (!$companyId) {
            $userCompany = $user->companiesForRole('manager')->first();
            if (!$userCompany) {
                $userCompany = $user->companies()->first();
            }
            $companyId = $userCompany?->company_id;
        }

        if (!$companyId) {
            return response()->json([
                'message' => 'No se pudo determinar la compañía. Asegúrese de estar asociado a una compañía.'
            ], 422);
        }

        $companyId = (int) $companyId;
        $merge = [
            'all' => true,
            'company' => $companyId,
            'company_id' => $companyId,
        ];

        // Seller: si no tiene "todos los clientes", limitar a los asignados a su seller_id.
        if ($user->isSeller($companyId) && ! $user->isAdmin() && ! $user->isManager($companyId)) {
            $vendor = $user->sellerByCompany($companyId);
            if (! $vendor) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            if ((int) ($vendor->all_customers ?? 0) !== 1) {
                $merge['seller'] = (int) $vendor->id;
            }
        }

        $request->merge($merge);

        $customers = $this->getModelList($request);

        return response()->json([
            'data' => $customers
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/customer",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CustomerRequest'
            )
        ),
        tags: ["Customer"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Customer'
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
    public function store(StoreCustomerRequest $request, CompanyPlanLimitService $planLimits)
     {
         $validated = $request->validated();
         $companyId = (int) ($validated['company_id'] ?? 0);
         if ($companyId > 0) {
             $denied = $planLimits->assertCanCreateCustomer($companyId);
             if ($denied !== null) {
                 return $denied;
             }
         }

         $customer = new Customer();

         $customer = $this->saveCustomerData($customer, $validated);

         return $this->makeResource($customer);
    }

    /**
     * Store a newly created customer without authentication (public endpoint)
     * 
     * @param StoreCustomerPublicRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(StoreCustomerPublicRequest $request, CompanyPlanLimitService $planLimits)
    {
        $validated = $request->validated();
        $companyId = (int) ($validated['company_id'] ?? 0);
        if ($companyId > 0) {
            $denied = $planLimits->assertCanCreateCustomer($companyId);
            if ($denied !== null) {
                return $denied;
            }
        }

        $customer = new Customer();

        $customer = $this->saveCustomerData($customer, $validated);

        return $this->makeResource($customer);
    }

    private function extractCommercialData(array $data): array
    {
        $fields = [
            'commercial_address_id',
            'commercial_address',
            'commercial_delivery_address',
            'commercial_country',
            'commercial_state',
            'commercial_state_id',
            'commercial_city',
            'commercial_city_id',
            'commercial_zip_code',
            'commercial_zone',
        ];
        $extracted = Arr::only($data, $fields);
 
        // Limpiar valores vacíos: convertir strings vacíos a null para campos opcionales
        foreach ($extracted as $key => $value) {
            if ($key !== 'commercial_address_id' && ($value === '' || $value === null)) {
                $extracted[$key] = null;
            } elseif ($key === 'commercial_address_id' && ($value === '' || $value === '0' || $value === 0)) {
                $extracted[$key] = null;
            }
        }

        foreach (['commercial_address', 'commercial_delivery_address', 'commercial_state', 'commercial_city', 'commercial_zip_code', 'commercial_zone'] as $uk) {
            if (!empty($extracted[$uk]) && is_string($extracted[$uk])) {
                $extracted[$uk] = mb_strtoupper(trim($extracted[$uk]), 'UTF-8');
            }
        }
        
        \Log::info('extractCommercialData - datos después de limpiar', [
            'extracted_after_clean' => $extracted
        ]);
        
        return $extracted;
    }

    private function extractCommercialDeliveryData(array $data): array
    {
        $fields = [
            'commercial_delivery_address_id',
            'commercial_delivery_address',
            'commercial_delivery_country',
            'commercial_delivery_state',
            'commercial_delivery_city',
            'commercial_delivery_zip_code',
            'commercial_delivery_zone',
        ];
        $extracted = Arr::only($data, $fields);
 
        // Limpiar valores vacíos: convertir strings vacíos a null para campos opcionales
        foreach ($extracted as $key => $value) {
            if ($key !== 'commercial_delivery_address_id' && ($value === '' || $value === null)) {
                $extracted[$key] = null;
            } elseif ($key === 'commercial_delivery_address_id' && ($value === '' || $value === '0' || $value === 0)) {
                $extracted[$key] = null;
            }
        }
        
        \Log::info('extractCommercialDeliveryData - datos después de limpiar', [
            'extracted_after_clean' => $extracted
        ]);
        
        return $extracted;
    }

    private function extractDispatchData(array $data): array
    {
        $fields = [
            'dispatch_address_id',
            'dispatch_address',
            'dispatch_delivery_address',
            'dispatch_country',
            'dispatch_state',
            'dispatch_state_id',
            'dispatch_city',
            'dispatch_city_id',
            'dispatch_zip_code',
            'dispatch_zone',
            'dispatch_shipping_notes',
        ];
        $extracted = Arr::only($data, $fields);
 
        // Limpiar valores vacíos: convertir strings vacíos a null para campos opcionales
        foreach ($extracted as $key => $value) {
            if ($key !== 'dispatch_address_id' && ($value === '' || $value === null)) {
                $extracted[$key] = null;
            } elseif ($key === 'dispatch_address_id' && ($value === '' || $value === '0' || $value === 0)) {
                $extracted[$key] = null;
            }
        }

        foreach (['dispatch_address', 'dispatch_delivery_address', 'dispatch_state', 'dispatch_city', 'dispatch_zip_code', 'dispatch_zone'] as $uk) {
            if (!empty($extracted[$uk]) && is_string($extracted[$uk])) {
                $extracted[$uk] = mb_strtoupper(trim($extracted[$uk]), 'UTF-8');
            }
        }
        
        \Log::info('extractDispatchData - datos después de limpiar', [
            'extracted_after_clean' => $extracted
        ]);
        
        return $extracted;
    }

    private function extractCustomerData(array $data): array
    {
        $exclude = array_merge(
            ['sellers'],
            array_keys($this->extractCommercialData($data)),
            array_keys($this->extractCommercialDeliveryData($data)),
            array_keys($this->extractDispatchData($data))
        );

        return Arr::except($data, $exclude);
    }

    /**
     * Columnas de dirección en tabla `customers` (fuente de verdad).
     * El formulario puede enviar country como id; aquí se resuelve a nombre.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractCustomerAddressColumns(array $data): array
    {
        $upperKeys = [
            'commercial_address',
            'commercial_delivery_address',
            'commercial_state',
            'commercial_city',
            'commercial_zone',
            'commercial_zip_code',
            'dispatch_address',
            'dispatch_delivery_address',
            'dispatch_state',
            'dispatch_city',
            'dispatch_zone',
            'dispatch_zip_code',
        ];

        $out = [];

        foreach ($upperKeys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            if ($value === null || $value === '') {
                $out[$key] = null;
                continue;
            }
            $out[$key] = is_string($value)
                ? mb_strtoupper(trim($value), 'UTF-8')
                : $value;
        }

        if (array_key_exists('commercial_country', $data)) {
            $out['commercial_country'] = $this->resolveCountryNameForCustomer($data['commercial_country']);
        }
        if (array_key_exists('dispatch_country', $data)) {
            $out['dispatch_country'] = $this->resolveCountryNameForCustomer($data['dispatch_country']);
        }
        if (array_key_exists('dispatch_shipping_notes', $data)) {
            $notes = $data['dispatch_shipping_notes'];
            if ($notes === null || $notes === '') {
                $out['dispatch_shipping_notes'] = null;
            } else {
                $out['dispatch_shipping_notes'] = mb_substr(trim((string) $notes), 0, 50);
            }
        }

        // Si address trae ciudad/estado/zip pegados, descomponer a columnas y dejar solo la calle.
        return app(\App\Services\CustomerAddressNormalizerService::class)
            ->normalizeCustomerColumns($out);
    }

    /**
     * @param  mixed  $countryInput  id numérico o nombre
     */
    private function resolveCountryNameForCustomer(mixed $countryInput): ?string
    {
        if ($countryInput === null || $countryInput === '') {
            return null;
        }
        if (is_numeric($countryInput) && (int) $countryInput > 0) {
            $country = Country::query()->find((int) $countryInput);

            return $country?->name;
        }
        $name = trim((string) $countryInput);

        return $name === '' ? null : $name;
    }

    private function hasValidAddressData(array $data, string $type): bool
    {
        // Si hay address_id (incluso si es 0 o string vacío, convertirlo a null para verificar correctamente)
        $addressIdKey = ($type === 'commercial_delivery') ? "{$type}_address_id" : "{$type}_address_id";
        $addressId = $data[$addressIdKey] ?? null;
        if ($addressId === '' || $addressId === '0' || $addressId === 0) {
            $addressId = null;
        }
        
        // Si hay address_id válido, significa que existe una dirección que se debe actualizar
        if (!empty($addressId)) {
            \Log::info("hasValidAddressData: address_id encontrado para tipo {$type}", ['address_id' => $addressId]);
            return true;
        }
        
        // Para datos comerciales: la dirección es obligatoria, verificar que exista
        // Para datos de despacho: guardar SOLO si hay al menos un campo con datos
        // Para commercial_delivery: guardar SOLO si hay al menos un campo con datos
        if ($type === 'commercial') {
            // Dirección comercial opcional: requiere calle u otros datos reales (no solo país por defecto).
            $address = trim((string) ($data["{$type}_address"] ?? ''));
            $zipCode = trim((string) ($data["{$type}_zip_code"] ?? ''));
            $country = $data["{$type}_country"] ?? null;
            $state = $data["{$type}_state"] ?? null;
            $city = $data["{$type}_city"] ?? null;
            $zone = trim((string) ($data["{$type}_zone"] ?? ''));

            $hasLocationDetails = !empty($address)
                || !empty($zipCode)
                || !empty($state)
                || !empty($city)
                || !empty($zone);
            // País solo no basta: el formulario suele traer país por defecto y borraría commercial_address.
            $hasAnyData = $hasLocationDetails || (!empty($country) && $hasLocationDetails);

            \Log::info("hasValidAddressData para tipo {$type} (opcional)", [
                'address' => $address,
                'hasAnyData' => $hasAnyData,
                'all_data' => $data,
            ]);

            return $hasAnyData;
        } elseif ($type === 'commercial_delivery') {
            // Para commercial_delivery: guardar SOLO si hay al menos un campo con datos
            $address = trim((string) ($data["{$type}_address"] ?? ''));
            $zipCode = trim((string) ($data["{$type}_zip_code"] ?? ''));
            $country = $data["{$type}_country"] ?? null;
            $state = $data["{$type}_state"] ?? null;
            $city = $data["{$type}_city"] ?? null;
            $zone = trim((string) ($data["{$type}_zone"] ?? ''));
            
            $hasAnyData = !empty($address) 
                || !empty($zipCode)
                || !empty($state)
                || !empty($city)
                || !empty($zone);
            
            \Log::info("hasValidAddressData para tipo {$type} (opcional)", [
                'address' => $address,
                'zipCode' => $zipCode,
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'zone' => $zone,
                'hasAnyData' => $hasAnyData,
                'all_data' => $data
            ]);
            
            return $hasAnyData;
        } else {
            // Para despacho: guardar SOLO si hay calle u otros datos reales (no solo país por defecto)
            $address = trim((string) ($data["{$type}_address"] ?? ''));
            $zipCode = trim((string) ($data["{$type}_zip_code"] ?? ''));
            $country = $data["{$type}_country"] ?? null;
            $state = $data["{$type}_state"] ?? null;
            $city = $data["{$type}_city"] ?? null;
            $zone = trim((string) ($data["{$type}_zone"] ?? ''));
            $shippingNotes = trim((string) ($data["{$type}_shipping_notes"] ?? ''));
            
            $hasAnyData = !empty($address) 
                || !empty($zipCode)
                || !empty($state)
                || !empty($city)
                || !empty($zone)
                || !empty($shippingNotes);
            
            \Log::info("hasValidAddressData para tipo {$type} (opcional)", [
                'address' => $address,
                'zipCode' => $zipCode,
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'zone' => $zone,
                'shippingNotes' => $shippingNotes,
                'hasAnyData' => $hasAnyData,
                'all_data' => $data
            ]);
            
            return $hasAnyData;
        }
    }

    /**
     * @return array{state_id: int|null, state_label: string|null}
     */
    private function resolveStateInputForAddress(?int $countryId, mixed $stateInput): array
    {
        if ($stateInput === null || $stateInput === '') {
            return ['state_id' => null, 'state_label' => null];
        }
        $trim = trim((string) $stateInput);
        if ($trim === '') {
            return ['state_id' => null, 'state_label' => null];
        }

        if (ctype_digit($trim)) {
            $id = (int) $trim;
            $q = State::query()->where('id', $id);
            if ($countryId) {
                $q->where('country_id', $countryId);
            }
            if ($q->exists()) {
                return ['state_id' => $id, 'state_label' => null];
            }
        }

        $found = $this->findStateByName($trim, $countryId);
        if (! $found && $countryId) {
            $found = $this->findStateByName($trim, null);
        }
        if ($found) {
            return ['state_id' => (int) $found->id, 'state_label' => null];
        }

        return ['state_id' => null, 'state_label' => mb_substr($trim, 0, 255)];
    }

    private function findStateByName(string $name, ?int $countryId): ?State
    {
        $q = State::query();
        if ($countryId) {
            $q->where('country_id', $countryId);
        }
        $exact = (clone $q)->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$name])->first();
        if ($exact) {
            return $exact;
        }

        return (clone $q)->whereRaw('LOWER(TRIM(name)) LIKE LOWER(TRIM(?))', [$name . '%'])->first();
    }

    /**
     * @return array{city_id: int|null, city_label: string|null}
     */
    private function resolveCityInputForAddress(?int $stateId, mixed $cityInput): array
    {
        if ($cityInput === null || $cityInput === '') {
            return ['city_id' => null, 'city_label' => null];
        }
        $trim = trim((string) $cityInput);
        if ($trim === '') {
            return ['city_id' => null, 'city_label' => null];
        }

        if (ctype_digit($trim)) {
            $id = (int) $trim;
            $city = City::query()->find($id);
            if ($city) {
                if ($stateId === null || (int) $city->state_id === $stateId) {
                    return ['city_id' => $city->id, 'city_label' => null];
                }
            }
        }

        $found = $this->findCityByName($trim, $stateId);
        if (! $found && $stateId) {
            $found = $this->findCityByName($trim, null);
        }
        if ($found) {
            return ['city_id' => (int) $found->id, 'city_label' => null];
        }

        return ['city_id' => null, 'city_label' => mb_substr($trim, 0, 255)];
    }

    private function findCityByName(string $name, ?int $stateId): ?City
    {
        $q = City::query();
        if ($stateId) {
            $q->where('state_id', $stateId);
        }
        $exact = (clone $q)->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$name])->first();
        if ($exact) {
            return $exact;
        }

        return (clone $q)->whereRaw('LOWER(TRIM(name)) LIKE LOWER(TRIM(?))', [$name . '%'])->first();
    }

    /**
     * Persiste estado/ciudad en texto libre según columnas existentes (state/city o state_label/city_label).
     *
     * @param  array<string, mixed>  $addressData
     * @param  array{state_id: int|null, state_label: string|null}  $resolvedState
     * @param  array{city_id: int|null, city_label: string|null}  $resolvedCity
     */
    private function applyFreeTextStateCityToAddressData(array &$addressData, array $resolvedState, array $resolvedCity): void
    {
        $stateText = $resolvedState['state_id'] === null ? $resolvedState['state_label'] : null;
        $cityText = $resolvedCity['city_id'] === null ? $resolvedCity['city_label'] : null;

        if (Schema::hasColumn('addresses', 'state_label')) {
            $addressData['state_label'] = $stateText;
        }
        if (Schema::hasColumn('addresses', 'city_label')) {
            $addressData['city_label'] = $cityText;
        }
        if (Schema::hasColumn('addresses', 'state')) {
            $addressData['state'] = $stateText;
        }
        if (Schema::hasColumn('addresses', 'city')) {
            $addressData['city'] = $cityText;
        }
    }

    private function resolveAddressStateName(Address $address, array $validated, string $fallbackKey): string
    {
        $text = $address->state_text;
        if ($text !== null && $text !== '') {
            return $text;
        }

        return trim((string) ($validated[$fallbackKey] ?? ''));
    }

    private function resolveAddressCityName(Address $address, array $validated, string $fallbackKey): string
    {
        $text = $address->city_text;
        if ($text !== null && $text !== '') {
            return $text;
        }

        return trim((string) ($validated[$fallbackKey] ?? ''));
    }

    private function updateOrCreateAddress(Customer $customer, array $data, string $type, bool $isPrimary = false)
    {
        $addressId = $data["{$type}_address_id"] ?? null;
        // Normalizar address_id: convertir string vacío o '0' a null
        if ($addressId === '' || $addressId === '0' || $addressId === 0) {
            $addressId = null;
        }
        $addressId = $addressId !== null ? (int) $addressId : null;

        // Si no viene id, reutilizar la dirección existente del mismo tipo (evita huérfanos y borra denormalizados).
        if ($addressId === null) {
            $existing = $customer->addresses()
                ->wherePivot('type', $type)
                ->orderByDesc('address_customer.is_primary')
                ->first();
            if ($existing) {
                $addressId = (int) $existing->id;
            }
        }
        
        // Preparar los datos de la dirección
        // Nota: zipcode, street y street_number no pueden ser null según la migración, deben ser string vacío
        // Para commercial_delivery, usar commercial_delivery_address en lugar de commercial_address
        $addressKey = ($type === 'commercial_delivery') ? "{$type}_address" : "{$type}_address";
        $addressValue = $data[$addressKey] ?? null;
        $streetValue = !empty($addressValue) && !empty(trim((string) $addressValue)) ? trim((string) $addressValue) : '';
        // Alinear con columna addresses.street VARCHAR(255)
        if (mb_strlen($streetValue) > 255) {
            $streetValue = mb_substr($streetValue, 0, 255);
        }

        $countryId = !empty($data["{$type}_country"]) ? (int) $data["{$type}_country"] : null;

        $stateIdInput = $data["{$type}_state_id"] ?? null;
        $cityIdInput = $data["{$type}_city_id"] ?? null;
        $resolvedState = $this->resolveStateInputForAddress(
            $countryId,
            (is_numeric($stateIdInput) && (int) $stateIdInput > 0) ? $stateIdInput : ($data["{$type}_state"] ?? null)
        );
        $resolvedCity = $this->resolveCityInputForAddress(
            $resolvedState['state_id'],
            (is_numeric($cityIdInput) && (int) $cityIdInput > 0) ? $cityIdInput : ($data["{$type}_city"] ?? null)
        );
        if ($resolvedState['state_id'] === null && !empty($resolvedCity['city_id'])) {
            $cityRow = City::query()->find($resolvedCity['city_id']);
            if ($cityRow && $cityRow->state_id) {
                $resolvedState['state_id'] = (int) $cityRow->state_id;
                $resolvedState['state_label'] = null;
            }
        }
        
        // Separar street y street_number si es necesario (por ahora todo va en street)
        $addressData = [
            'street'        => $streetValue,
            'street_number' => '', // Por ahora vacío, se puede mejorar para separar si hay un campo específico
            'country_id'   => $countryId,
            'state_id'     => $resolvedState['state_id'],
            'city_id'      => $resolvedCity['city_id'],
            'zipcode'      => !empty($data["{$type}_zip_code"]) && !empty(trim((string) $data["{$type}_zip_code"])) ? trim((string) $data["{$type}_zip_code"]) : '',
            'zone'         => !empty($data["{$type}_zone"]) && !empty(trim((string) $data["{$type}_zone"])) ? trim((string) $data["{$type}_zone"]) : null,
        ];
        $this->applyFreeTextStateCityToAddressData($addressData, $resolvedState, $resolvedCity);
        
        // Solo agregar notes para dispatch
        if ($type === 'dispatch') {
            $notes = $data["{$type}_shipping_notes"] ?? null;
            $addressData['notes'] = !empty($notes) && !empty(trim((string) $notes)) ? trim((string) $notes) : null;
        }
        
        \Log::info("updateOrCreateAddress para tipo {$type}", [
            'address_id' => $addressId,
            'address_data' => $addressData,
            'customer_id' => $customer->customer_id,
            'raw_data' => $data
        ]);

        if ($addressId !== null) {
            $address = Address::withTrashed()->find($addressId);
            if ($address) {
                if ($address->trashed()) {
                    $address->restore();
                }
                $address->fill($addressData);
                $address->save();
            } else {
                $address = Address::create($addressData);
            }
        } else {
            $address = Address::create($addressData);
        }

        // Refrescar la dirección para cargar las relaciones
        $address->refresh();
        $address->load(['country', 'state', 'city']);

        \Log::info("Dirección creada/actualizada", [
            'address_id' => $address->id,
            'address_data' => $address->toArray(),
            'state_id' => $address->state_id,
            'city_id' => $address->city_id,
            'state_name' => $address->state->name ?? 'NULL',
            'city_name' => $address->city->name ?? 'NULL'
        ]);

        // Sincronizar la relación con el cliente (restaurar pivot soft-deleted si aplica)
        $pivot = \App\Models\AddressCustomer::withTrashed()
            ->where('customer_id', $customer->customer_id)
            ->where('address_id', $address->id)
            ->first();

        if ($pivot) {
            if ($pivot->trashed()) {
                $pivot->restore();
            }
            $pivot->is_primary = $isPrimary;
            $pivot->type = $type;
            $pivot->save();
        } else {
            $customer->addresses()->syncWithoutDetaching([
                $address->id => ['is_primary' => $isPrimary, 'type' => $type],
            ]);
        }
        
        \Log::info("Relación sincronizada", [
            'customer_id' => $customer->customer_id,
            'address_id' => $address->id,
            'type' => $type
        ]);
        
        return $address;
    }

    /**
     * Si id_kor es 0 o cust_id_kor vacío/'0': en edición usar customer_id.
     *
     * @param  array<string, mixed>  $customerData
     * @return array<string, mixed>
     */
    private function applyErpIdDefaultsForSave(array $customerData, int $customerId): array
    {
        $cid = $customerId;
        $customerData = app(\App\Services\CustomerCustIdKorService::class)
            ->applyToPayload($customerData, $cid, isCreate: false);
        if (array_key_exists('id_kor', $customerData)) {
            $v = $customerData['id_kor'];
            if ($v === null || $v === '' || (int) $v === 0) {
                $customerData['id_kor'] = (int) $cid;
            }
        }

        return $customerData;
    }

    private function applyErpIdDefaultsAfterCreate(Customer $customer): void
    {
        $cid = $customer->customer_id;
        $upd = [];
        // Alta: CXXXXXXX único si sigue vacío (el observer suele haberlo puesto ya).
        app(\App\Services\CustomerCustIdKorService::class)->fillAfterCreate($customer->fresh() ?? $customer);
        $customer->refresh();
        $idKor = $customer->id_kor ?? null;
        if ($idKor === null || (int) $idKor === 0) {
            $upd['id_kor'] = (int) $cid;
        }
        if ($upd !== []) {
            $customer->update($upd);
        }
    }

    private function saveCustomerData(Customer $customer, array $validated, bool $isUpdate = false): Customer
    {
        $sellers = $validated['sellers'] ?? [];

        $customerData = $this->extractCustomerData($validated);
        // Fuente de verdad: columnas de dirección en `customers`.
        $customerData = array_merge($customerData, $this->extractCustomerAddressColumns($validated));
        $commercialData = $this->extractCommercialData($validated);
        $dispatchData = $this->extractDispatchData($validated);

        if (empty($customerData['seller_assign_catalog'])) {
            $customerData['seller_assign_catalog'] = Company::findOrFail($customerData['company_id'])->default_seller_id;
        }

        if ($isUpdate && $customer->exists) {
            $customerData = $this->applyErpIdDefaultsForSave($customerData, $customer->customer_id);
            $customer->update($customerData);
        } else {
            $customer = Customer::create($customerData);
            $this->applyErpIdDefaultsAfterCreate($customer);
        }

        $customer->sellers()->sync($sellers);

        \Log::info('Datos comerciales recibidos', [
            'commercial_data' => $commercialData,
            'has_valid_data' => $this->hasValidAddressData($commercialData, 'commercial'),
            'customer_id' => $customer->customer_id,
            'customers_table_address' => Arr::only($customerData, [
                'commercial_address',
                'commercial_country',
                'commercial_state',
                'commercial_city',
                'dispatch_address',
                'dispatch_country',
            ]),
        ]);

        // Pivot addresses: auxiliar (legacy). No debe impedir ni pisar customers.
        if ($this->hasValidAddressData($commercialData, 'commercial')) {
            try {
                $this->updateOrCreateAddress($customer, $commercialData, 'commercial', true);
            } catch (\Exception $e) {
                \Log::error('Error guardando dirección comercial (pivot auxiliar): ' . $e->getMessage(), [
                    'customer_id' => $customer->customer_id,
                    'commercial_data' => $commercialData,
                ]);
            }
        }

        $commercialDeliveryData = $this->extractCommercialDeliveryData($validated);
        if (! empty(trim((string) ($commercialDeliveryData['commercial_delivery_address'] ?? '')))) {
            try {
                $this->updateOrCreateAddress($customer, $commercialDeliveryData, 'commercial_delivery', false);
            } catch (\Exception $e) {
                \Log::error('Error guardando commercial_delivery (pivot auxiliar): ' . $e->getMessage(), [
                    'customer_id' => $customer->customer_id,
                ]);
            }
        }

        if ($this->hasValidAddressData($dispatchData, 'dispatch')) {
            try {
                $this->updateOrCreateAddress($customer, $dispatchData, 'dispatch');
            } catch (\Exception $e) {
                \Log::error('Error guardando despacho (pivot auxiliar): ' . $e->getMessage(), [
                    'customer_id' => $customer->customer_id,
                    'dispatch_data' => $dispatchData,
                ]);
            }
        }

        $customer->refresh();

        return $customer;
    }

    /**
     * @deprecated La fuente de verdad son las columnas de `customers` vía extractCustomerAddressColumns.
     * Se mantiene por compatibilidad si algún flujo legacy lo invocara; no sobrescribe lo enviado en request.
     */
    private function syncAddressFieldsToCustomer(Customer $customer, array $validated = []): void
    {
        // No-op: no pisar customers desde el pivot addresses.
        \Log::info('syncAddressFieldsToCustomer skipped (customers table is source of truth)', [
            'customer_id' => $customer->customer_id,
            'has_validated' => $validated !== [],
        ]);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/customer/{customer}",
        summary: "Display the specified Customer.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "customer",
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
    public function show($customer)
    {
        $builder = $this->getModelInstanceBuilder($customer);

        if(\request()->has('withGroup'))
            $builder = $builder->with('customer_group');

        $customer = $builder->sole();

        if ((\request()->has('withGroup')) && !$customer->customer_group) {
            if (Company::where('company_id', $customer->company_id)->exists()) {
                $company = Company::where('company_id', $customer->company_id)->sole();
                $defaultGroup = $company->default_customer_group();
                $customer->setRelation('customer_group', $defaultGroup);
            }
        }

        return $customer;
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/customer/{customer}",
        summary: "Updates the specified Customer.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CustomerRequest'
            )
        ),
        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "customer",
                description: "The Customer identifier to update.",
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
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        $customer = $this->saveCustomerData($customer, $validated, true);

        return $this->makeResource($customer);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/customer/{customer}",
        summary: "Remove the specified Customer.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "customer",
                description: "The Customer identifier to remove.",
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
    public function destroy(Customer $customer, CustomerDeleteService $service): JsonResponse|Response
    {
        $error = $service->delete($customer);

        if (!empty($error)) {
            return response()->json([
                'errors' => [$error],
            ], 422);
        }

        return response()->noContent();
    }

    #[OA\Post(
        path: "api/v2/customer/email/exists",
        summary: "Check if email exists for a customer",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Customer"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Email to check'),
                    new OA\Property(property: 'customer_id', type: 'integer', description: 'Customer ID to exclude from check (optional)')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'exists', type: 'boolean', description: 'True if email exists, false otherwise')
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
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
            )
        ]
    )]
    public function emailExists(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'customer_id' => 'nullable|integer'
        ]);
        
        $email = mb_strtolower(trim((string) $request->input('email')), 'UTF-8');
        $customerId = $request->input('customer_id');
        
        $query = $this->getBuilder()->whereRaw('LOWER(TRIM(email)) = ?', [$email]);
        
        // Excluir el cliente actual si se proporciona el ID
        if ($customerId) {
            $query->where('customer_id', '!=', $customerId);
        }
        
        $exists = $query->exists();

        if (! $exists) {
            $extraQuery = CustomerEmail::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereHas('customer', function ($q) use ($customerId) {
                    $q->where('company_id', request()->get('company_id') ?? request()->get('company'));
                    if ($customerId) {
                        $q->where('customer_id', '!=', $customerId);
                    }
                });
            $exists = $extraQuery->exists();
        }

        return response()->json(['exists' => $exists]);
    }
    
    #[OA\Post(
        path: "api/v2/customer/update-email",
        summary: "Update customer email",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Customer"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_id', 'email'],
                properties: [
                    new OA\Property(property: 'customer_id', type: 'integer', description: 'Customer ID'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', description: 'New email address')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Email updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: '#/components/schemas/Customer')
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function updateEmail(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,customer_id',
            'email' => 'required|email|max:255'
        ]);
        
        $customerId = $request->input('customer_id');
        $newEmail = $request->input('email');
        
        $customer = Customer::findOrFail($customerId);
        
        // Verificar que el nuevo email no exista en otro cliente
        $emailExists = $this->getBuilder()
            ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim((string) $newEmail), 'UTF-8')])
            ->where('customer_id', '!=', $customerId)
            ->exists();

        if (! $emailExists) {
            $emailExists = CustomerEmail::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $newEmail), 'UTF-8')])
                ->whereHas('customer', function ($q) use ($customer, $customerId) {
                    $q->where('company_id', $customer->company_id)
                        ->where('customer_id', '!=', $customerId);
                })
                ->exists();
        }
        
        if ($emailExists) {
            return response()->json([
                'message' => __('validation.custom.email.unique'),
                'errors' => ['email' => [__('validation.custom.email.unique')]]
            ], 422);
        }

        $normalized = mb_strtolower(trim((string) $newEmail), 'UTF-8');
        
        DB::transaction(function () use ($customer, $normalized) {
            $customer->update(['email' => $normalized]);

            CustomerEmail::query()
                ->where('customer_id', $customer->customer_id)
                ->update(['is_primary' => false]);

            $row = CustomerEmail::query()
                ->where('customer_id', $customer->customer_id)
                ->whereRaw('LOWER(email) = ?', [$normalized])
                ->first();

            if ($row) {
                $row->is_primary = true;
                $row->save();
            } else {
                CustomerEmail::query()->create([
                    'customer_id' => $customer->customer_id,
                    'email' => $normalized,
                    'is_primary' => true,
                ]);
            }
        });
        
        return response()->json([
            'message' => 'ok',
            'data' => $customer->fresh()
        ]);
    }

    /**
     * Update customer group
     */
    #[OA\Put(
        path: "api/v2/customer/updateGroup",
        summary: "Updates the customer group.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'customer_id',
                        type: 'integer',
                        description: 'The customer ID',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'group_id',
                        type: 'integer',
                        nullable: true,
                        description: 'The group ID (null to remove group)',
                        example: 5
                    )
                ],
                required: ['customer_id']
            )
        ),
        tags: ["Customer"],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Grupo actualizado correctamente'
                        ),
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
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Validation error"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function updateGroup(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,customer_id',
            'group_id' => 'nullable|integer|exists:customer_groups,group_id'
        ]);
        
        $customerId = $request->input('customer_id');
        $groupId = $request->input('group_id');
        
        $customer = Customer::findOrFail($customerId);
        
        // Actualizar solo el grupo
        $customer->update(['group_id' => $groupId]);
        
        // Recargar el cliente con sus relaciones
        $customer->load('customer_group');
        
        return response()->json([
            'message' => 'Grupo actualizado correctamente',
            'data' => $customer
        ]);
    }

    #[OA\Get(
        path: "api/v2/customer/company/{companyId}/bussines-name/{bussinesName}/exists",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "bussinesName",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                   type: 'boolean',
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
    public function bussinesNameExists($companyId, string $bussinesName): JsonResponse
    {
        $exist = $this->getBuilder()
            ->where('company_id', $companyId)
            ->where('business_name', $bussinesName)
            ->get()
            ->count();

        return response()->json($exist > 0);
    }

    /**
     * Devuelve el cliente por company_id y business_name (para import: si existe, actualizar).
     * Comparación case-insensitive porque el modelo guarda business_name en mayúsculas.
     */
    public function getByBusinessName($companyId, string $businessName)
    {
        return $this->findCustomerByBusinessName($companyId, $businessName);
    }

    /**
     * Variante por query string para nombres que contienen "/" u otros caracteres de ruta.
     */
    public function findByBusinessName(Request $request, $companyId)
    {
        $businessName = trim((string) $request->query('business_name', ''));
        if ($businessName === '') {
            abort(404, 'Customer not found');
        }

        return $this->findCustomerByBusinessName($companyId, $businessName);
    }

    /**
     * Busca cliente por Id ERP (cust_id_kor) dentro de la empresa (importación).
     */
    public function findByErpId(Request $request, $companyId)
    {
        $erpId = trim((string) $request->query('cust_id_kor', ''));
        if ($erpId === '') {
            abort(404, 'Customer not found');
        }

        $customer = $this->getBuilder()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(TRIM(cust_id_kor)) = ?', [mb_strtoupper($erpId, 'UTF-8')])
            ->first();

        if (!$customer) {
            abort(404, 'Customer not found');
        }

        return $this->makeResource($this->withImportPortalUserFlag($customer));
    }

    private function findCustomerByBusinessName($companyId, string $businessName)
    {
        $customer = $this->getBuilder()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(TRIM(business_name)) = ?', [mb_strtoupper(trim($businessName), 'UTF-8')])
            ->first();

        if (!$customer) {
            abort(404, 'Customer not found');
        }

        return $this->makeResource($this->withImportPortalUserFlag($customer));
    }

    /**
     * Marca si el cliente ya tiene usuario portal vinculado (customer_user activo).
     */
    private function withImportPortalUserFlag(Customer $customer): Customer
    {
        $hasPortalUser = CustomerUser::query()
            ->where('customer_id', $customer->customer_id)
            ->whereNull('deleted_at')
            ->exists();

        $customer->setAttribute('has_portal_user', $hasPortalUser);

        return $customer;
    }

    protected function getFilteredSortedModelQuery(Request $request): Builder
    {
        $builder = parent::getFilteredSortedModelQuery($request);

        $with = $request->input('relations', []);
        if (! is_array($with)) {
            $with = [];
        }

        // Listado: grupo sin company anidada; sellers solo si no viene ya en relations.
        $hasSellers = false;
        $normalized = [];
        foreach ($with as $relation) {
            if (! is_string($relation) || $relation === '') {
                continue;
            }
            if (str_starts_with($relation, 'customer_group')) {
                continue;
            }
            if (str_starts_with($relation, 'sellers')) {
                $hasSellers = true;
            }
            $normalized[] = $relation;
        }
        $normalized[] = 'customer_group:group_id,name,company_id';
        if (! $hasSellers) {
            $normalized[] = 'sellers';
        }

        $builder->with(array_values(array_unique($normalized)));

        return $builder;
    }

    public function seller ($customerId, Request $request){
        /** @var Customer $customer */
         $customer = $this->getBuilder()
            ->where('customer_id', $customerId)->sole();
         $seller = $customer->sellers->first();
         if(!$seller){
             $seller = $customer->company->defaultSeller;
         }
         return $seller;
    }

    protected function getColumns($properties)
    {
        $properties = str_replace('customer_name',"CONCAT(business_name,' (',name,')') AS customer_name",$properties);
        return parent::getColumns($properties);
    }

    protected function getMainModel($model = null)
    {
        $mainModel = parent::getMainModel($model);
        return $mainModel->with('sellers');
    }

    protected function getBuilder($model = null)
    {
        $builder = parent::getBuilder($model);

        /** @var User $user */
        $user = \Auth::user();

        if (!\request()->has('status')) {
            $builder->where('customer_status', 1);
        }

        $companyId = (int) (\request()->input('company_id') ?: \request()->input('company'));
        if ($companyId > 0) {
            $builder->where('company_id', $companyId);
        } elseif ($user && $user->companies->count() === 1) {
            /** @var Company $company */
            $company = $user->companies()->first();
            $builder->where('company_id', $company->company_id);
        }

        return $builder;
    }


    #[OA\Get(
        path: "api/v2/customer/{customerId}/has-orders",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer","Order"],
        parameters: [
            new OA\Parameter(
                name: "customerId",
                description: "The Has order identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: 'boolean',
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
    public function hasOrders($customerId)
    {
        /** @var Customer $customer */
        $customer = $this->getMainModelInstance($customerId);
        return $customer->orders->count() > 0;
    }


    #[OA\Get(
        path: "api/v2/customer/{customerId}/product/{productId}/price",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer","Product"],
        parameters: [
            new OA\Parameter(
                name: "customerId",
                description: "The Price identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "productId",
                description: "The Price identifier to retrieve.",
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
                        property: 'amount',
                        type: 'number',
                    ),
                                    new OA\Property(
                        property: 'currency',
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
    public function productPrice($customerId, $productId)
    {
        $customer = Customer::findOrFail($customerId);
        /** @var Product $product */
        $product = Product::whereProId($productId)->sole();
        $revertirPrecio = request()->boolean('revertirPrecio');

        return response()->json([
            'amount' => $product->getPriceForCustomer($customer, $revertirPrecio),
            'currency' => $product->currency
        ]);
    }

    public function ensureCatalogCart($customer): JsonResponse
    {
        /** @var Customer $customerModel */
        $customerModel = $this->getMainModelInstance($customer);
        $user = request()->user();
        if ($user === null || ! $this->userCanAccessCustomerOrders($user, $customerModel)) {
            return response()->json([
                'success' => false,
                'code' => 'customer_catalog_cart_forbidden',
            ], 403);
        }

        try {
            $session = app(OrderCatalogCartSessionService::class)->ensure(
                (int) $customerModel->company_id,
                (int) $customerModel->customer_id
            );

            return response()->json(['data' => $session]);
        } catch (\RuntimeException $e) {
            $code = match ($e->getMessage()) {
                'default_catalog_missing', 'catalog_cart_table_missing' => 422,
                default => 500,
            };

            return response()->json([
                'message' => $e->getMessage(),
                'error'   => true,
            ], $code);
        } catch (\Throwable $e) {
            Log::error('CustomerController::ensureCatalogCart', [
                'customer_id' => $customerModel->customer_id,
                'error'       => $e->getMessage(),
            ]);

            return response()->json(['message' => 'error', 'error' => true], 500);
        }
    }

    /**
     * POST /api/v2/customer/{customer}/ensure-portal-session-cart
     *
     * Token corto session_cart para el portal cliente (sesión MVC customer_portal_session_token).
     */
    public function ensurePortalSessionCart($customer, CustomerPortalSessionCartService $sessions): JsonResponse
    {
        /** @var Customer $customerModel */
        $customerModel = $this->getMainModelInstance($customer);
        $user = request()->user();
        if ($user === null || ! $this->userCanAccessCustomerOrders($user, $customerModel)) {
            return response()->json([
                'success' => false,
                'code' => 'customer_portal_session_forbidden',
            ], 403);
        }

        $session = $sessions->ensure(
            (int) $customerModel->company_id,
            (int) $customerModel->customer_id
        );

        return response()->json([
            'data' => array_merge($session, [
                'catalog_cart_enabled' => $sessions->catalogCartEnabled(),
            ]),
        ]);
    }

    public function orders($customer)
    {
        /** @var Customer $customerModel */
        $customerModel = $this->getMainModelInstance($customer);
        $user = request()->user();
        if ($user === null || ! $this->userCanAccessCustomerOrders($user, $customerModel)) {
            return response()->json([
                'success' => false,
                'code' => 'customer_orders_forbidden',
            ], 403);
        }

        $ordersQuery = $customerModel
            ->orders()
            ->whereRelation('statusOrder', 'cod_status', '!=', OrderStatusCodes::SHOPPING->value)
            ->with([
                'statusOrder',
                'seller:id,user_id',
                'seller.user:id,name',
            ])
            ->orderBy('created', 'desc');

        return $ordersQuery->paginate(
            min(150, max(1, (int) request()->input('per_page', 10))),
            ['*'],
            'page',
            (int) request()->input('page', 1)
        );
    }

    /**
     * Listado de órdenes por cliente: portal (pivote customer_user) o personal de la empresa (manager/seller).
     */
    private function userCanAccessCustomerOrders(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->customers()->where('customers.customer_id', $customer->customer_id)->exists()) {
            return true;
        }
        $companyId = (int) $customer->company_id;

        return $companyId > 0 && ($user->isManager($companyId) || $user->isSeller($companyId));
    }

    public function CSVExport(ExportCustomer $request)
    {
        $builder = $this->getBulkBuilder($request);

        $customers = $builder
            ->with([
                'sellers.user',
                'terms_of_sale',
                'customer_group',
                'commercialAddresses.country',
                'commercialAddresses.state',
                'commercialAddresses.city',
                'dispatchAddresses.country',
                'dispatchAddresses.state',
                'dispatchAddresses.city',
            ])
            ->get();

        return Excel::download(new CustomersExport($customers), 'customer_list' . date('Ymd') . '.xlsx');
    }

    public function bulkAssignSeller(BulkAssignSellerRequest $request): JsonResponse
    {
        return $this->bulkSellerAction($request, 'assign');
    }

    public function bulkUnassignSeller(BulkUnassignSellerRequest $request): JsonResponse
    {
        return $this->bulkSellerAction($request, 'unassign');
    }

    private function bulkSellerAction(BulkAssignSellerRequest|BulkUnassignSellerRequest $request, string $action): JsonResponse
    {
        $sellerIds = array_values(array_unique(array_map('intval', (array) $request->input('sellers', []))));

        $customers = $this->getBulkBuilder($request)->get();

        if ($customers->isEmpty()) {
            return response()->json([
                'message' => $this->langCmpClientes('bulk_seller_no_customers'),
            ], 404);
        }

        foreach ($customers as $customer) {
            match ($action) {
                'assign'   => $customer->sellers()->syncWithoutDetaching($sellerIds),
                'unassign' => $this->unassignSellers($customer, $sellerIds, false),
                default    => null,
            };
        }

        $message = $action === 'assign'
            ? $this->langCmpClientes('seller_asigned')
            : $this->langCmpClientes('seller_removed');

        return response()->json(['message' => $message]);
    }

    /**
     * @param  bool  $attachDefaultWhenEmpty  En bulk desasignar (false) no se vuelve a asignar el vendedor por defecto:
     *                                        si no, el usuario cree que "no quitó" al ver otro nombre en la grilla.
     */
    private function unassignSellers(Customer $customer, array $sellerIds, bool $attachDefaultWhenEmpty = true): void
    {
        $customer->sellers()->detach($sellerIds);

        if (! $attachDefaultWhenEmpty) {
            return;
        }

        if (! $customer->sellers()->exists()) {
            $defaultSellerId = $customer->company->default_seller_id;
            if ($defaultSellerId) {
                $customer->sellers()->syncWithoutDetaching([$defaultSellerId]);
            }
        }
    }

    public function bulkSendCatalog(BulkSendCatalogRequest $request)
    {
        $company = Company::select([
            'company_id',
            'image',
            'name',
            'country',
            'catalogsend_time',
            'default_seller_id',
            'language_id',
        ])
            ->with('defaultSeller')
            ->findOrFail($request->input('company_id'));

        $sendType = strtolower(trim((string) $request->input('type', 'email')));
        if (! in_array($sendType, ['email', 'sms', 'whatsapp'], true)) {
            $sendType = 'email';
        }

        $customers = $this->getBulkBuilder($request)
            ->select([
                'customer_id',
                'business_name',
                'name',
                'email',
                'phone',
                'cell_phone',
                'commercial_country',
                'seller_assign_catalog',
                'customer_status',
                'catalog_emails',
            ])
            ->with(['sellerAssignCatalog', 'sellers', 'commercialAddresses.country'])
            ->get()
            ->filter(function ($c) use ($sendType) {
                if ((int) ($c->customer_status ?? 0) !== 1 || (int) ($c->catalog_emails ?? 0) !== 1) {
                    return false;
                }
                if ($sendType === 'email') {
                    return trim((string) ($c->email ?? '')) !== '';
                }
                $phone = preg_replace('/\D+/', '', (string) (($c->cell_phone ?? '') !== '' ? $c->cell_phone : ($c->phone ?? ''))) ?? '';

                return $phone !== '';
            })
            ->values();

        if ($customers->isEmpty()) {
            return response()->json([
                'message' => $this->langCmpClientes('bulk_catalog_no_eligible'),
            ], 422);
        }

        $catalogIds = $request->input('catalogs', []);
        if (! is_array($catalogIds) || count($catalogIds) < 1) {
            return response()->json([
                'message' => $this->langCmpClientes('bulk_send_catalog_select_catalogs'),
            ], 422);
        }

        try {
            \App\Jobs\BulkSendCatalogJob::dispatchSync(
                $company,
                $customers,
                $catalogIds,
                $request->validated()
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $this->langCmpClientes('bulk_catalog_sent'),
        ]);
    }

    /**
     * Texto cmp-clientes desde lang JSON (en-EN / es-ES) según locale de la petición.
     */
    private function langCmpClientes(string $key): string
    {
        $locale = app()->getLocale();
        $file = $locale === 'es' ? 'es-ES.json' : 'en-EN.json';
        $path = base_path("lang/{$file}");
        if (! is_readable($path)) {
            return $key;
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || ! isset($decoded['cmp-clientes'][$key])) {
            return $key;
        }

        return (string) $decoded['cmp-clientes'][$key];
    }

    public function bulkDelete(BulkCustomerDeleteRequest $request, CustomerDeleteService $service)
    {
        $customers = $this->getBulkBuilder($request)
            ->get();

        $errors = [];

        foreach ($customers as $customer) {
            $result = $service->delete($customer);

            if ($result !== null) {
                $errors[] = $result;
            }
        }

        if (empty($errors)) {
            return response()->noContent();
        }

        return response()->json([
            'errors' => $errors,
        ], 422);
    }

    /**
     * @param Request $request
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    private function getBulkBuilder(Request $request): \Illuminate\Database\Query\Builder|Builder
    {
        // No usar getBuilder(): en POST JSON no viene "status" y getBuilder fuerza customer_status=1,
        // excluyendo filas seleccionadas (p. ej. inactivas) y dejando la colección vacía sin desasignar.
        $companyId = (int) $request->input('company_id');
        $builder = Customer::query()->where('company_id', $companyId);

        if (($request->input('all_checked') ?? '') === 'pagina') {
            $ids = array_values(array_filter(array_map('intval', (array) ($request->input('customer_ids') ?? []))));
            if ($ids !== []) {
                $builder->whereIn('customer_id', $ids);
            }
        } else {
            $filters = $request->input('filters') ?? [];

            $builder = $builder->filter([
                'group' => isset($filters['group']) && $filters['group'] !== 'todo' ? $filters['group'] : null,
                'search' => isset($filters['search']) && $filters['search'] !== '' ? $filters['search'] : null,
                'seller' => isset($filters['seller']) && $filters['seller'] !== 'todo' ? $filters['seller'] : null,
                'status' => isset($filters['status']) && $filters['status'] !== 'todo' ? $filters['status'] : null,
                'term' => isset($filters['term']) && $filters['term'] !== 'todo' ? $filters['term'] : null,
            ]);
        }

        return $builder;
    }

    public function enable(Customer $customer): Customer
    {
        return $this->updateBooleanField($customer, 'customer_status', true);
    }

    public function disable(Customer $customer): Customer
    {
        return $this->updateBooleanField($customer, 'customer_status', false);
    }

    public function enableEmail(Customer $customer): Customer
    {
        return $this->updateBooleanField($customer, 'catalog_emails', true);
    }

    public function disableEmail(Customer $customer): Customer
    {
        return $this->updateBooleanField($customer, 'catalog_emails', false);
    }

    private function updateBooleanField(Customer $customer, string $field, bool $value): Customer
    {
        if ($customer->$field === $value) {
            return $customer;
        }

        $customer->update([$field => $value]);

        return $customer;
    }

    public function assignUser(CustomerAssignUserRequest $request, Customer $customer)
    {
        $customer->users()->syncWithoutDetaching($request->validated()['user_id']);

        CompanyUser::firstOrCreate([
            'company_id' => $request->validated()['company_id'],
            'user_id'    => $request->validated()['user_id'],
            'type' => CompanyUserTypes::Customer
        ]);

        return response()->json([
            'message' => 'Usuario asignado correctamente',
        ], 201);
    }
}
