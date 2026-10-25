<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use App\Models\Gpstracker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

/**
 * Tracking GPS de sellers: lectura de rutas y escritura de puntos.
 *
 * Endpoints bajo `/api/v2/company-gps/*` sobre la tabla `gpstracker`.
 */
#[OA\Tag(
    name: 'CompanyGps',
    description: 'GPS tracker de sellers (tabla gpstracker): listar sellers, leer ruta por fecha y registrar puntos'
)]
class CompanyGpsTrackerController extends Controller
{
    private const VIEW_ROLES = ['manager', 'seller', 'warehouse'];

    #[OA\Get(
        path: 'api/v2/company-gps/sellers',
        operationId: 'companyGpsSellers',
        summary: 'Listar sellers de la compañía',
        description: 'Devuelve los usuarios con rol seller de la compañía, incluyendo la fecha del último punto GPS si existe. Requiere rol manager, seller o warehouse. Header opcional: `X-Company-Id`.',
        security: [['bearerAuth' => []]],
        tags: ['CompanyGps'],
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                in: 'query',
                required: true,
                description: 'ID de la compañía',
                schema: new OA\Schema(type: 'integer', example: 39)
            ),
            new OA\Parameter(
                name: 'X-Company-Id',
                in: 'header',
                required: false,
                description: 'Alternativa a company_id en query',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Listado de sellers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CompanyGpsSeller')
                        ),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin rol de acceso en la compañía'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'company_id requerido'),
        ]
    )]
    public function sellers(Request $request): JsonResponse
    {
        [$user, $companyId, $error] = $this->resolveViewer($request);
        if ($error) {
            return $error;
        }

        $rows = CompanyUser::query()
            ->where('company_id', $companyId)
            ->where('type', 'seller')
            ->with(['user:id,name,email,image'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($group) {
                /** @var CompanyUser $first */
                $first = $group->first();
                $u = $first->user;
                if (! $u) {
                    return null;
                }

                return [
                    'user_id' => (int) $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'image' => $u->image,
                    'avatar_url' => $this->resolveAvatarUrl($u->image),
                ];
            })
            ->filter()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $sellerIds = $rows->pluck('user_id')->all();
        $lastByUser = [];
        if ($sellerIds !== []) {
            $lastByUser = Gpstracker::query()
                ->selectRaw('user_id, MAX(created) as last_at')
                ->where('company_id', $companyId)
                ->whereIn('user_id', $sellerIds)
                ->groupBy('user_id')
                ->pluck('last_at', 'user_id')
                ->all();
        }

        $data = $rows->map(function (array $row) use ($lastByUser) {
            $uid = $row['user_id'];
            $last = $lastByUser[$uid] ?? null;
            $row['last_point_at'] = $last
                ? Carbon::parse($last)->toIso8601String()
                : null;

            return $row;
        })->values();

        return response()->json(['data' => $data]);
    }

    #[OA\Get(
        path: 'api/v2/company-gps/track',
        operationId: 'companyGpsTrack',
        summary: 'Obtener ruta GPS de un seller en una fecha',
        description: 'Devuelve los puntos de `gpstracker` para el seller y la fecha indicada (día civil, de 00:00:00 a 23:59:59), ordenados por `created`.',
        security: [['bearerAuth' => []]],
        tags: ['CompanyGps'],
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 39)
            ),
            new OA\Parameter(
                name: 'user_id',
                in: 'query',
                required: true,
                description: 'user_id del seller',
                schema: new OA\Schema(type: 'integer', example: 1323)
            ),
            new OA\Parameter(
                name: 'date',
                in: 'query',
                required: true,
                description: 'Fecha de la ruta (YYYY-MM-DD)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-21')
            ),
            new OA\Parameter(
                name: 'X-Company-Id',
                in: 'header',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Ruta del día',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/CompanyGpsTrack'),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Seller no pertenece a la compañía'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación fallida'),
        ]
    )]
    public function track(Request $request): JsonResponse
    {
        [$viewer, $companyId, $error] = $this->resolveViewer($request);
        if ($error) {
            return $error;
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|min:1',
            'date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $sellerUserId = (int) $request->input('user_id');
        $date = (string) $request->input('date');

        if (! $this->isSellerOfCompany($companyId, $sellerUserId)) {
            return response()->json(['message' => 'Seller not found in company'], 404);
        }

        $start = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $end = (clone $start)->endOfDay();

        $points = Gpstracker::query()
            ->where('company_id', $companyId)
            ->where('user_id', $sellerUserId)
            ->whereBetween('created', [$start, $end])
            ->orderBy('created')
            ->orderBy('id')
            ->get([
                'id',
                'latitude',
                'longitude',
                'created',
                'address',
                'deviceinfo',
                'login',
            ]);

        $seller = User::query()->find($sellerUserId, ['id', 'name', 'email', 'image']);

        return response()->json([
            'data' => [
                'user_id' => $sellerUserId,
                'date' => $date,
                'seller' => $seller ? [
                    'user_id' => (int) $seller->id,
                    'name' => $seller->name,
                    'email' => $seller->email,
                    'image' => $seller->image,
                    'avatar_url' => $this->resolveAvatarUrl($seller->image),
                ] : null,
                'points_count' => $points->count(),
                'points' => $points->map(fn (Gpstracker $p) => [
                    'id' => (int) $p->id,
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'created' => optional($p->created)?->toIso8601String(),
                    'address' => $p->address,
                    'deviceinfo' => $p->deviceinfo,
                    'login' => $p->login,
                ])->values(),
            ],
        ]);
    }

    #[OA\Post(
        path: 'api/v2/company-gps/points',
        operationId: 'companyGpsStorePoints',
        summary: 'Registrar puntos GPS del seller autenticado',
        description: "Inserta uno o varios puntos en `gpstracker` para el usuario autenticado (debe ser seller de la compañía). "
            . "Acepta un punto suelto (`latitude`/`longitude` en la raíz) o un lote en `points` (máximo 200). "
            . "`user_id` y `company_id` del registro se toman del token y del parámetro/header; no se pueden falsificar.",
        security: [['bearerAuth' => []]],
        tags: ['CompanyGps'],
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                in: 'query',
                required: false,
                description: 'Alternativa a enviarlo en el body o en X-Company-Id',
                schema: new OA\Schema(type: 'integer', example: 39)
            ),
            new OA\Parameter(
                name: 'X-Company-Id',
                in: 'header',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Punto único o lote `points`',
            content: new OA\JsonContent(ref: '#/components/schemas/CompanyGpsStorePointsRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Puntos creados',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'created'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CompanyGpsCreatedPoint')
                        ),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Requiere rol seller'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación fallida'),
        ]
    )]
    public function storePoints(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $companyId = (int) ($request->input('company_id') ?? $request->header('X-Company-Id') ?? 0);
        if ($companyId <= 0) {
            return response()->json(['message' => 'company_id required'], 422);
        }

        if (! $user->isSeller($companyId)) {
            return response()->json(['message' => 'Forbidden: seller role required'], 403);
        }

        // Acepta { points: [...] } o un único punto en la raíz.
        $pointsInput = $request->input('points');
        if (! is_array($pointsInput)) {
            $pointsInput = [[
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'address' => $request->input('address'),
                'deviceinfo' => $request->input('deviceinfo'),
                'login' => $request->input('login'),
                'created' => $request->input('created'),
            ]];
        }

        if ($pointsInput === []) {
            return response()->json(['message' => 'points required'], 422);
        }

        if (count($pointsInput) > 200) {
            return response()->json(['message' => 'Too many points (max 200)'], 422);
        }

        $created = [];
        foreach ($pointsInput as $i => $point) {
            if (! is_array($point)) {
                return response()->json(['message' => "Invalid point at index {$i}"], 422);
            }
            $v = Validator::make($point, [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address' => 'nullable|string|max:600',
                'deviceinfo' => 'nullable|string|max:5000',
                'login' => 'nullable|integer',
                'created' => 'nullable|date',
            ]);
            if ($v->fails()) {
                return response()->json([
                    'message' => "Validation failed at index {$i}",
                    'errors' => $v->errors(),
                ], 422);
            }

            $row = new Gpstracker();
            $row->company_id = $companyId;
            $row->user_id = (int) $user->id;
            $row->latitude = (float) $point['latitude'];
            $row->longitude = (float) $point['longitude'];
            $row->address = isset($point['address']) ? (string) $point['address'] : null;
            $row->deviceinfo = array_key_exists('deviceinfo', $point) && $point['deviceinfo'] !== null
                ? (string) $point['deviceinfo']
                : '-';
            $row->login = isset($point['login']) ? (int) $point['login'] : null;
            $row->created = ! empty($point['created'])
                ? Carbon::parse($point['created'])
                : Carbon::now();
            $row->save();

            $created[] = [
                'id' => (int) $row->id,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'created' => optional($row->created)?->toIso8601String(),
            ];
        }

        return response()->json([
            'message' => 'created',
            'data' => $created,
        ], 201);
    }

    /**
     * @return array{0:?User,1:int,2:?JsonResponse}
     */
    private function resolveViewer(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [null, 0, response()->json(['message' => 'Unauthenticated'], 401)];
        }

        $companyId = (int) ($request->input('company_id') ?? $request->header('X-Company-Id') ?? 0);
        if ($companyId <= 0) {
            return [null, 0, response()->json(['message' => 'company_id required'], 422)];
        }

        if (! $this->hasViewRole($user, $companyId)) {
            return [null, 0, response()->json(['message' => 'Forbidden'], 403)];
        }

        return [$user, $companyId, null];
    }

    private function hasViewRole(User $user, int $companyId): bool
    {
        foreach (self::VIEW_ROLES as $role) {
            $ok = match ($role) {
                'manager' => $user->isManager($companyId),
                'seller' => $user->isSeller($companyId),
                'warehouse' => $user->isWarehouse($companyId),
                default => false,
            };
            if ($ok) {
                return true;
            }
        }

        return false;
    }

    private function isSellerOfCompany(int $companyId, int $userId): bool
    {
        return CompanyUser::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('type', 'seller')
            ->exists();
    }

    private function resolveAvatarUrl(?string $image): ?string
    {
        $image = trim((string) $image);
        if ($image === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})(?:\.[a-z0-9]+)?$/i', $image, $m)) {
            return '/api/v2/images/' . $m[1];
        }

        $file = ltrim($image, '/');
        if (str_starts_with($file, 'users/') || str_starts_with($file, 'user/')) {
            return '/uploads/' . $file;
        }

        return '/uploads/users/' . $file;
    }
}
