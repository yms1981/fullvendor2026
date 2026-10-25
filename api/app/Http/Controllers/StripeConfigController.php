<?php

namespace App\Http\Controllers;

use App\Models\EntidadEmisora;
use App\Models\StripePublish;
use App\Models\StripeSecret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class StripeConfigController extends Controller
{
    /**
     * Configuración Stripe + entidad emisora para el formulario admin.
     */
    public function show(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        return response()->json([
            'data' => [
                'issuing_entity' => $this->loadIssuingEntity(),
                'stripe_secret' => $this->loadStripeSecretRow(),
                'stripe_publish' => $this->loadStripePublishRow(),
                'tables' => [
                    'issuing_entity' => Schema::hasTable('issuing_entity'),
                    'stripe_secret' => Schema::hasTable('stripe_secret'),
                    'stripe_publish' => Schema::hasTable('stripe_publish'),
                ],
            ],
        ]);
    }

    /**
     * Actualiza entidad emisora y claves Stripe (upsert singleton por tabla).
     */
    public function update(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $errors = [];
        $savedAny = false;

        if (! Schema::hasTable('issuing_entity')) {
            $errors[] = 'missing_issuing_entity_table';
        } else {
            $entityRow = $this->readEntityPayload($request);
            if ($entityRow === null) {
                $errors[] = 'invalid_issuing_entity';
            } else {
                if ($this->persistIssuingEntity($entityRow)) {
                    $savedAny = true;
                } else {
                    $errors[] = 'save_issuing_entity_failed';
                }
            }
        }

        if (Schema::hasTable('stripe_secret')) {
            $secret = trim((string) $request->input('stripe_secret', ''));
            if ($this->upsertStripeSecret($secret)) {
                $savedAny = true;
            } else {
                $errors[] = 'save_stripe_secret_failed';
            }
        }

        if (Schema::hasTable('stripe_publish')) {
            $publish = trim((string) $request->input('stripe_publish', ''));
            if ($this->upsertStripePublish($publish)) {
                $savedAny = true;
            } else {
                $errors[] = 'save_stripe_publish_failed';
            }
        }

        return response()->json([
            'data' => [
                'issuing_entity' => $this->loadIssuingEntity(),
                'stripe_secret' => $this->loadStripeSecretRow(),
                'stripe_publish' => $this->loadStripePublishRow(),
            ],
            'saved_any' => $savedAny,
            'errors' => $errors,
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if ($user === null || ! $user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }

    private function loadIssuingEntity(): ?array
    {
        if (! Schema::hasTable('issuing_entity')) {
            return null;
        }

        $row = EntidadEmisora::query()->orderBy('entity_id')->first();
        if ($row === null) {
            return null;
        }

        return $row->only([
            'entity_id',
            'entity_name',
            'entity_address',
            'entity_country',
            'entity_state',
            'entity_city',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadStripeSecretRow(): array
    {
        if (! Schema::hasTable('stripe_secret')) {
            return ['id' => 0, 'secret' => ''];
        }

        $row = StripeSecret::query()->orderBy('id')->first();
        if ($row === null) {
            return ['id' => 0, 'secret' => ''];
        }

        return [
            'id' => (int) $row->id,
            'secret' => (string) ($row->secret ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadStripePublishRow(): array
    {
        if (! Schema::hasTable('stripe_publish')) {
            return ['stripeID' => 0, 'secretAPI' => ''];
        }

        $row = StripePublish::query()->orderBy('stripeID')->first();
        if ($row === null) {
            return ['stripeID' => 0, 'secretAPI' => ''];
        }

        return [
            'stripeID' => (int) $row->stripeID,
            'secretAPI' => (string) ($row->secretAPI ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readEntityPayload(Request $request): ?array
    {
        $nombre = trim((string) $request->input('entity_name', ''));
        $direccion = trim((string) $request->input('entity_address', ''));
        $pais = trim((string) $request->input('entity_country', ''));
        $estado = trim((string) $request->input('entity_state', ''));
        $ciudad = trim((string) $request->input('entity_city', ''));

        $len = static function (string $s): int {
            return function_exists('mb_strlen') ? (int) mb_strlen($s, 'UTF-8') : strlen($s);
        };

        if ($nombre === '' || $len($nombre) > 100) {
            return null;
        }
        if ($len($pais) > 50 || $len($estado) > 50 || $len($ciudad) > 50) {
            return null;
        }

        return [
            'entity_name' => $nombre,
            'entity_address' => $direccion === '' ? null : $direccion,
            'entity_country' => $pais === '' ? null : $pais,
            'entity_state' => $estado === '' ? null : $estado,
            'entity_city' => $ciudad === '' ? null : $ciudad,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function persistIssuingEntity(array $row): bool
    {
        $existing = EntidadEmisora::query()->orderBy('entity_id')->first();
        if ($existing !== null) {
            return (bool) $existing->update($row);
        }

        return (bool) EntidadEmisora::query()->create($row);
    }

    private function upsertStripeSecret(string $value): bool
    {
        if (! Schema::hasTable('stripe_secret') || ! Schema::hasColumn('stripe_secret', 'secret')) {
            return false;
        }

        $payload = ['secret' => $value === '' ? null : $value];
        $first = StripeSecret::query()->orderBy('id')->first();
        if ($first !== null) {
            return (bool) $first->update($payload);
        }

        return (bool) StripeSecret::query()->create($payload);
    }

    private function upsertStripePublish(string $value): bool
    {
        if (! Schema::hasTable('stripe_publish') || ! Schema::hasColumn('stripe_publish', 'secretAPI')) {
            return false;
        }

        $payload = ['secretAPI' => $value === '' ? null : $value];
        $first = StripePublish::query()->orderBy('stripeID')->first();
        if ($first !== null) {
            return (bool) $first->update($payload);
        }

        return (bool) StripePublish::query()->create($payload);
    }
}
