<?php

namespace App\Http\Controllers;

use App\Enums\CompanyUserTypes;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Requests\WarehouseAssignUserRequest;
use App\Http\Requests\WarehouseUnassignUserRequest;
use App\Models\Address;
use App\Models\CompanyUser;
use App\Models\Warehouse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    protected $resourceRoute = 'warehouse';

    public function index()
    {
        return parent::index();
    }

    public function store(StoreWarehouseRequest $request)
    {
        return DB::transaction(function () use ($request) {
            [$warehouseData, $addressData] = $this->splitData($request->validated());

            $address = Address::create($addressData);
            $warehouseData['address_id'] = $address->id;

            return $this->createFromAttributes($warehouseData);
        });
    }

    public function show(Warehouse $warehouse)
    {
        return $this->getMainModelInstance($warehouse->id);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        return DB::transaction(function () use ($request, $warehouse) {
            [$warehouseData, $addressData] = $this->splitData($request->validated());

            $warehouse->address()->update($addressData);

            return $this->updateModel($warehouse, $warehouseData);
        });
    }

    public function destroy(Warehouse $warehouse)
    {
        //implement validations
        return $this->destroyResourceEmptyResponse($warehouse);
    }

    private function splitData(array $validated): array
    {
        $warehouseData = Arr::only($validated, ['name', 'company_id']);
        $addressData   = $this->normalizeAddressData(
            Arr::except($validated, ['name', 'company_id'])
        );

        return [$warehouseData, $addressData];
    }

    /**
     * Campos de dirección opcionales: evitar null en columnas NOT NULL (p. ej. street_number).
     *
     * @param  array<string, mixed>  $addressData
     * @return array<string, mixed>
     */
    private function normalizeAddressData(array $addressData): array
    {
        $addressData['street'] = trim((string) ($addressData['street'] ?? ''));
        $addressData['street_number'] = trim((string) ($addressData['street_number'] ?? ''));

        foreach (['city_id', 'state_id'] as $nullableIdField) {
            $raw = $addressData[$nullableIdField] ?? null;
            if ($raw === null || $raw === '' || (int) $raw < 1) {
                $addressData[$nullableIdField] = null;
            } else {
                $addressData[$nullableIdField] = (int) $raw;
            }
        }

        if (isset($addressData['country_id'])) {
            $addressData['country_id'] = (int) $addressData['country_id'];
        }

        return $addressData;
    }

    public function assignUser(WarehouseAssignUserRequest $request, Warehouse $warehouse)
    {
        $warehouse->users()->syncWithoutDetaching([
            $request->validated()['user_id'] => ['company_id' => $request->validated()['company_id']]
        ]);

        CompanyUser::firstOrCreate([
            'company_id' => $request->validated()['company_id'],
            'user_id'    => $request->validated()['user_id'],
            'type' => CompanyUserTypes::Warehouse
        ]);

        return response()->json([
            'message' => $this->cmpWarehouseApiMessage('user_assigned'),
        ], 201);
    }

    public function unassignUser(WarehouseUnassignUserRequest $request, Warehouse $warehouse)
    {
        $userId = (int) $request->validated()['user_id'];
        $warehouse->users()->detach($userId);

        return response()->json([
            'message' => $this->cmpWarehouseApiMessage('user_unassigned'),
        ]);
    }

    private function cmpWarehouseApiMessage(string $key): string
    {
        $locale = strtolower((string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en')));
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return $key;
        }
        $data = json_decode(file_get_contents($path), true);

        return $data['cmp-warehouse'][$key] ?? $key;
    }
}
