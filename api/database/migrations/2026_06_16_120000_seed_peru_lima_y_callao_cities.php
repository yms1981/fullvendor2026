<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Provincias del departamento Lima y de la provincia constitucional del Callao (Perú).
     * En FullVendor se modelan como ciudades del estado "Lima y Callao".
     */
    private const PROVINCES = [
        'Lima',
        'Barranca',
        'Cajatambo',
        'Canta',
        'Canete',
        'Huaral',
        'Huarochiri',
        'Huaura',
        'Oyon',
        'Yauyos',
        'Callao',
    ];

    public function up(): void
    {
        $country = DB::table('countries')->where('sortname', 'PE')->first();
        if ($country === null) {
            return;
        }

        $limaState = DB::table('states')
            ->where('country_id', $country->id)
            ->where('name', 'Lima y Callao')
            ->first();

        if ($limaState === null) {
            return;
        }

        $loretoState = DB::table('states')
            ->where('country_id', $country->id)
            ->where('name', 'Loreto')
            ->first();

        if ($loretoState !== null) {
            DB::table('cities')
                ->where('state_id', $loretoState->id)
                ->where('name', 'Barranca')
                ->whereNull('deleted_at')
                ->update([
                    'state_id' => $limaState->id,
                    'updated_at' => now(),
                ]);
        }

        $now = now();

        foreach (self::PROVINCES as $province) {
            $exists = DB::table('cities')
                ->where('state_id', $limaState->id)
                ->where('name', $province)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('cities')->insert([
                'name' => $province,
                'state_id' => $limaState->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $country = DB::table('countries')->where('sortname', 'PE')->first();
        if ($country === null) {
            return;
        }

        $limaState = DB::table('states')
            ->where('country_id', $country->id)
            ->where('name', 'Lima y Callao')
            ->first();

        if ($limaState === null) {
            return;
        }

        $loretoState = DB::table('states')
            ->where('country_id', $country->id)
            ->where('name', 'Loreto')
            ->first();

        if ($loretoState !== null) {
            DB::table('cities')
                ->where('state_id', $limaState->id)
                ->where('name', 'Barranca')
                ->whereNull('deleted_at')
                ->update([
                    'state_id' => $loretoState->id,
                    'updated_at' => now(),
                ]);
        }

        $namesToRemove = array_values(array_filter(
            self::PROVINCES,
            static fn (string $name): bool => $name !== 'Barranca'
        ));

        DB::table('cities')
            ->where('state_id', $limaState->id)
            ->whereIn('name', $namesToRemove)
            ->whereNull('deleted_at')
            ->delete();
    }
};
