<?php

namespace App\Http\Controllers\Mobile;

use App\Models\MobileChangeLog;
use App\Services\MobileSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'MobileSync',
    description: 'Registro de dispositivos móviles y sincronización total/parcial (Sanctum).'
)]
class MovilSyncController extends MobileController
{
    public function __construct(private readonly MobileSyncService $sync)
    {
    }

    #[OA\Post(
        path: 'api/v3/movilregister',
        summary: 'Registrar o actualizar dispositivo móvil',
        description: 'Si el device_uuid no existe lo crea; si existe actualiza datos y last_used_at.',
        security: [['bearerAuth' => []]],
        tags: ['MobileSync'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['device_uuid'],
                properties: [
                    new OA\Property(property: 'device_uuid', type: 'string', example: 'A1B2-C3D4-DEVICE'),
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'device_name', type: 'string', example: 'iPhone 15'),
                    new OA\Property(property: 'device_type', type: 'string', example: 'ios'),
                    new OA\Property(property: 'os_version', type: 'string', example: '17.4'),
                    new OA\Property(property: 'app_version', type: 'string', example: '2.1.0'),
                    new OA\Property(property: 'model', type: 'string'),
                    new OA\Property(property: 'manufacturer', type: 'string'),
                    new OA\Property(property: 'push_token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Dispositivo registrado/actualizado'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function movilregister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_uuid' => 'required_without:device_id|string|max:191',
            'device_id' => 'required_without:device_uuid|string|max:191',
            'company_id' => 'nullable|integer|min:1',
            'device_name' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|max:32',
            'os_version' => 'nullable|string|max:64',
            'app_version' => 'nullable|string|max:64',
            'model' => 'nullable|string|max:128',
            'manufacturer' => 'nullable|string|max:128',
            'push_token' => 'nullable|string|max:512',
        ]);
        if ($validator->fails()) {
            return $this->error($this->msg('validation_error'), $validator->errors(), 422);
        }

        try {
            $result = $this->sync->registerOrTouch($request->user(), $request->all());
        } catch (\InvalidArgumentException $e) {
            return $this->error($this->msg($e->getMessage()), null, 422);
        }

        return $this->success([
            'device' => $this->devicePayload($result['device']),
            'created' => (bool) $result['created'],
        ], $this->msg($result['created'] ? 'device_registered' : 'device_updated'));
    }

    #[OA\Post(
        path: 'api/v3/movilsync',
        summary: 'Solicitar sincronización (total o parcial)',
        description: 'Si nunca sincronizó o hay más de 500 cambios → sync_mode=total; si no → partial.',
        security: [['bearerAuth' => []]],
        tags: ['MobileSync'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['device_uuid'],
                properties: [
                    new OA\Property(property: 'device_uuid', type: 'string'),
                    new OA\Property(property: 'company_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Decisión de sync'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function movilsync(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if ($device instanceof JsonResponse) {
            return $device;
        }

        $companyId = $request->input('company_id', $device->company_id);
        try {
            $result = $this->sync->evaluateSync($device, $companyId ? (int) $companyId : null);
        } catch (\InvalidArgumentException $e) {
            return $this->error($this->msg($e->getMessage()), null, 422);
        }

        $deviceModel = $result['device'];
        unset($result['device']);
        $result['device'] = $this->devicePayload($deviceModel);

        return $this->success($result, $result['is_total']
            ? $this->msg('sync_total_required')
            : $this->msg('sync_partial_required'));
    }

    #[OA\Post(
        path: 'api/v3/movilsync/complete',
        summary: 'Marcar sincronización como completada',
        security: [['bearerAuth' => []]],
        tags: ['MobileSync'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['device_uuid'],
                properties: [
                    new OA\Property(property: 'device_uuid', type: 'string'),
                    new OA\Property(property: 'sync_log_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Sync completada'),
        ]
    )]
    public function complete(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if ($device instanceof JsonResponse) {
            return $device;
        }

        $updated = $this->sync->completeSync(
            $device,
            $request->filled('sync_log_id') ? (int) $request->input('sync_log_id') : null
        );

        return $this->success([
            'device' => $this->devicePayload($updated),
        ], $this->msg('sync_completed'));
    }

    #[OA\Get(
        path: 'api/v3/movilsync/products',
        summary: 'Cambios parciales de productos',
        security: [['bearerAuth' => []]],
        tags: ['MobileSync'],
        parameters: [
            new OA\Parameter(name: 'device_uuid', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Ítems cambiados'),
        ]
    )]
    public function products(Request $request): JsonResponse
    {
        return $this->partialGroup($request, MobileChangeLog::GROUP_PRODUCTS);
    }

    #[OA\Get(
        path: 'api/v3/movilsync/customers',
        summary: 'Cambios parciales de clientes',
        security: [['bearerAuth' => []]],
        tags: ['MobileSync'],
        parameters: [
            new OA\Parameter(name: 'device_uuid', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Ítems cambiados'),
        ]
    )]
    public function customers(Request $request): JsonResponse
    {
        return $this->partialGroup($request, MobileChangeLog::GROUP_CUSTOMERS);
    }

    #[OA\Get(
        path: 'api/v3/movilsync/categories',
        summary: 'Cambios parciales de categorías',
        security: [['bearerAuth' => []]],
        tags: ['MobileSync'],
        parameters: [
            new OA\Parameter(name: 'device_uuid', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Ítems cambiados'),
        ]
    )]
    public function categories(Request $request): JsonResponse
    {
        return $this->partialGroup($request, MobileChangeLog::GROUP_CATEGORIES);
    }

    #[OA\Get(
        path: 'api/v3/movilsync/orders',
        summary: 'Cambios parciales de órdenes',
        security: [['bearerAuth' => []]],
        tags: ['MobileSync'],
        parameters: [
            new OA\Parameter(name: 'device_uuid', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Ítems cambiados'),
        ]
    )]
    public function orders(Request $request): JsonResponse
    {
        return $this->partialGroup($request, MobileChangeLog::GROUP_ORDERS);
    }

    protected function partialGroup(Request $request, string $group): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if ($device instanceof JsonResponse) {
            return $device;
        }

        $companyId = $request->input('company_id', $device->company_id);
        try {
            $payload = $this->sync->partialChanges($device, $group, $companyId ? (int) $companyId : null);
        } catch (\InvalidArgumentException $e) {
            return $this->error($this->msg($e->getMessage()), null, 422);
        }

        return $this->success($payload, $this->msg('partial_ok'));
    }

    /**
     * @return \App\Models\MobileDevice|JsonResponse
     */
    protected function resolveDevice(Request $request)
    {
        $uuid = trim((string) ($request->input('device_uuid') ?: $request->input('device_id') ?: ''));
        if ($uuid === '') {
            return $this->error($this->msg('device_uuid_required'), null, 422);
        }

        $device = $this->sync->findDeviceForUser($request->user(), $uuid);
        if (! $device) {
            return $this->error($this->msg('device_not_found'), null, 404);
        }

        return $device;
    }

    /** @return array<string, mixed> */
    protected function devicePayload($device): array
    {
        return [
            'id' => (int) $device->id,
            'device_uuid' => $device->device_uuid,
            'user_id' => (int) $device->user_id,
            'company_id' => $device->company_id ? (int) $device->company_id : null,
            'device_name' => $device->device_name,
            'device_type' => $device->device_type,
            'os_version' => $device->os_version,
            'app_version' => $device->app_version,
            'model' => $device->model,
            'manufacturer' => $device->manufacturer,
            'last_used_at' => optional($device->last_used_at)->toIso8601String(),
            'last_sync_at' => optional($device->last_sync_at)->toIso8601String(),
        ];
    }

    protected function msg(string $key): string
    {
        $locale = strtolower((string) $this->requestLocale());
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return $key;
        }
        $data = json_decode((string) file_get_contents($path), true);

        return $data['cmp-mobile'][$key] ?? $key;
    }

    protected function requestLocale(): string
    {
        return (string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en'));
    }
}
