<?php

namespace App\Services;

use App\Models\Currency;
use App\Support\CurrencySchemaHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo global de monedas (tabla `currency`) y utilidades de resolución para companies.currency.
 */
final class CurrencyCatalogService
{
    /**
     * Lista agrupada para el selector admin (solo tabla `currency`, sin currencybycountries).
     *
     * @return array<int, object{GroupID: string, currencies: array<int, object>}>
     */
    public function groupedByContinent(): array
    {
        $ownerKey = CurrencySchemaHelper::companiesCurrencyOwnerKey();

        $query = Currency::query()
            ->when(
                Schema::hasColumn('currency', 'status'),
                fn ($q) => $q->where('status', 1)
            );

        if (Schema::hasColumn('currency', 'company_id')) {
            $query->where('company_id', 0);
        }

        $currencies = $query->orderBy($ownerKey)->get();

        if ($currencies->isEmpty() && Schema::hasColumn('currency', 'company_id')) {
            $currencies = Currency::query()
                ->when(
                    Schema::hasColumn('currency', 'status'),
                    fn ($q) => $q->where('status', 1)
                )
                ->orderBy($ownerKey)
                ->get();
        }

        $groups = [];
        $groupIndex = [];
        $hasDefault = false;

        foreach ($currencies as $currency) {
            $tags = is_array($currency->tags) ? $currency->tags : $this->decodeTags($currency->tags ?? null);
            $groupId = trim((string) ($tags['main_category'] ?? ''));
            if ($groupId === '') {
                $groupId = trim((string) ($currency->country ?? ''));
            }
            if ($groupId === '') {
                $groupId = trim((string) ($currency->code ?? ''));
            }
            if ($groupId === '') {
                $groupId = 'Other';
            }

            if (! isset($groupIndex[$groupId])) {
                $groupIndex[$groupId] = count($groups);
                $groups[] = (object) [
                    'GroupID' => $groupId,
                    'currencies' => [],
                ];
            }

            $isDefault = (($tags['default'] ?? '') === 'selected');
            if ($isDefault) {
                $hasDefault = true;
            }

            $idx = $groupIndex[$groupId];
            $groups[$idx]->currencies[] = (object) [
                'id' => CurrencySchemaHelper::rowCompanyCurrencyId($currency),
                'GroupID' => $groupId,
                'Contry' => (string) ($tags['country_category'] ?? $currency->country ?? ''),
                'Name' => $this->currencyDisplayName($currency),
                'Code' => (string) ($currency->code ?? ''),
                'Symbol' => (string) ($currency->symbol ?? ''),
                'default' => $isDefault ? 'selected' : '',
            ];
        }

        if (! $hasDefault && $groups !== [] && ($groups[0]->currencies ?? []) !== []) {
            $groups[0]->currencies[0]->default = 'selected';
        }

        return $groups;
    }

    public function resolveStoreCurrencyId(mixed $currency): ?int
    {
        if ($currency === '' || $currency === null) {
            return null;
        }

        $id = (int) $currency;
        if ($id <= 0) {
            return null;
        }

        return $this->resolveCompanyCurrencyForeignKey($id);
    }

    /**
     * Valida que el ID elegido en el picker exista en `currency` (columna FK de companies).
     */
    public function resolveCompanyCurrencyForeignKey(int $currencyId): ?int
    {
        if ($currencyId <= 0) {
            return null;
        }

        $fk = CurrencySchemaHelper::companiesCurrencyOwnerKey();
        $pk = CurrencySchemaHelper::primaryKeyColumn();

        if (DB::table('currency')->where($fk, $currencyId)->exists()) {
            return $currencyId;
        }

        if ($fk !== $pk) {
            $row = DB::table('currency')->where($pk, $currencyId)->first();
            if ($row !== null) {
                $fkVal = CurrencySchemaHelper::rowCompanyCurrencyId($row);
                if ($fkVal > 0 && DB::table('currency')->where($fk, $fkVal)->exists()) {
                    return $fkVal;
                }
            }
        }

        return $this->ensureCurrencyFromLegacyId($currencyId);
    }

    /**
     * Garantiza que el ID elegido (currencybycountries) exista en `currency` con datos completos.
     * Si no existe, lo crea desde currencybycountries; si existe incompleto, completa campos vacíos.
     */
    public function ensureCurrencyFromLegacyId(int $legacyId): ?int
    {
        if ($legacyId <= 0) {
            return null;
        }

        $fkColumn = CurrencySchemaHelper::companiesCurrencyOwnerKey();

        if (! $this->canSyncFromLegacy()) {
            return DB::table('currency')->where($fkColumn, $legacyId)->exists() ? $legacyId : null;
        }

        $legacy = DB::table('currencybycountries')->where('id', $legacyId)->first();
        if ($legacy === null) {
            return DB::table('currency')->where($fkColumn, $legacyId)->exists() ? $legacyId : null;
        }

        $result = $this->upsertCurrencyRowFromLegacy($legacy, fillMissingOnly: true, strictLegacyId: true);
        if ($result === null) {
            return null;
        }

        return $this->currencyForeignKeyExists($legacyId) ? $legacyId : null;
    }

    private function currencyForeignKeyExists(int $legacyId): bool
    {
        if ($legacyId <= 0) {
            return false;
        }

        $fk = CurrencySchemaHelper::companiesCurrencyOwnerKey();

        return DB::table('currency')->where($fk, $legacyId)->exists();
    }

    /**
     * Sincroniza solo si faltan filas o hay registros incompletos.
     */
    public function syncFromLegacyTableIfNeeded(): int
    {
        if (! $this->canSyncFromLegacy()) {
            return 0;
        }

        $legacyCount = (int) DB::table('currencybycountries')->count();
        if ($legacyCount < 1) {
            return 0;
        }

        $globalCount = $this->globalCurrencyCount();
        if ($globalCount < $legacyCount || $this->hasIncompleteGlobalCurrencies()) {
            $stats = $this->syncFromLegacyTable(fillMissingOnly: true);

            return $stats['inserted'] + $stats['updated'];
        }

        return 0;
    }

    /**
     * Sincroniza currencybycountries → currency (inserta faltantes y completa campos vacíos).
     *
     * @return array{inserted: int, updated: int, skipped: int}
     */
    public function syncFromLegacyTable(bool $fillMissingOnly = true): array
    {
        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0];

        if (! $this->canSyncFromLegacy()) {
            return $stats;
        }

        $rows = DB::table('currencybycountries')->orderBy('id')->get();
        foreach ($rows as $row) {
            $legacyId = (int) ($row->id ?? 0);
            $code = trim((string) ($row->Code ?? ''));
            if ($legacyId < 1 || $code === '') {
                $stats['skipped']++;

                continue;
            }

            $result = $this->upsertCurrencyRowFromLegacy($row, $fillMissingOnly, false);
            if ($result === null) {
                $stats['skipped']++;

                continue;
            }

            if ($result['action'] === 'inserted') {
                $stats['inserted']++;
            } elseif ($result['action'] === 'updated') {
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * Inserta o actualiza una fila de currency desde currencybycountries.
     *
     * @param  bool  $strictLegacyId  Si true, solo usa el ID legacy (alta de empresa); no reasigna por código.
     * @return array{id: int, action: 'inserted'|'updated'|'unchanged'}|null
     */
    private function upsertCurrencyRowFromLegacy(object $row, bool $fillMissingOnly = true, bool $strictLegacyId = false): ?array
    {
        $legacyId = (int) ($row->id ?? 0);
        $code = trim((string) ($row->Code ?? ''));
        if ($legacyId < 1 || $code === '') {
            return null;
        }

        $payload = $this->buildPayloadFromLegacyRow($row, $legacyId, $code);
        $pk = CurrencySchemaHelper::primaryKeyColumn();
        $fk = CurrencySchemaHelper::companiesCurrencyOwnerKey();
        $existing = DB::table('currency')->where($pk, $legacyId)->first();

        if ($existing === null && $fk !== $pk) {
            $existing = DB::table('currency')->where($fk, $legacyId)->first();
        }

        if ($existing === null && ! $strictLegacyId) {
            $existing = $this->findGlobalCurrencyByCode($code);
        }

        if ($existing !== null && $strictLegacyId) {
            $pkVal = $this->rowPrimaryKeyValue($existing);
            $fkVal = (int) ($existing->{$fk} ?? 0);
            if ($pkVal !== $legacyId && $fkVal !== $legacyId) {
                $existing = null;
            }
        }

        if ($existing === null) {
            if (! $this->insertLegacyCurrencyRow($payload, preserveId: true)) {
                return null;
            }

            return $this->currencyForeignKeyExists($legacyId)
                ? ['id' => $legacyId, 'action' => 'inserted']
                : null;
        }

        $existingId = $this->rowPrimaryKeyValue($existing);
        $update = $fillMissingOnly
            ? $this->mergeMissingCurrencyFields((array) $existing, $payload)
            : $this->filterPayloadToColumns($payload);

        if ($fk !== $pk && (int) ($existing->{$fk} ?? 0) !== $legacyId) {
            $update[$fk] = $legacyId;
        }

        unset($update['created_at'], $update['created']);
        unset($update[$pk]);
        if ($pk !== 'id' && isset($update['id'])) {
            unset($update['id']);
        }

        if ($update !== []) {
            if (Schema::hasColumn('currency', 'updated_at')) {
                $update['updated_at'] = now();
            }

            DB::table('currency')->where($pk, $existingId)->update($update);

            return $this->currencyForeignKeyExists($legacyId)
                ? ['id' => $legacyId, 'action' => 'updated']
                : null;
        }

        return $this->currencyForeignKeyExists($legacyId)
            ? ['id' => $legacyId, 'action' => 'unchanged']
            : null;
    }

    private function canSyncFromLegacy(): bool
    {
        return Schema::hasTable('currency')
            && Schema::hasTable('currencybycountries')
            && (Schema::hasColumn('currency', 'id') || Schema::hasColumn('currency', 'currency_id'));
    }

    private function globalCurrencyCount(): int
    {
        $query = Currency::query();
        if (Schema::hasColumn('currency', 'company_id')) {
            $query->where('company_id', 0);
        }

        return (int) $query->count();
    }

    private function hasIncompleteGlobalCurrencies(): bool
    {
        $query = DB::table('currency');
        if (Schema::hasColumn('currency', 'company_id')) {
            $query->where('company_id', 0);
        }

        return $query
            ->where(function ($q) {
                if (Schema::hasColumn('currency', 'name')) {
                    $q->whereNull('name')->orWhere('name', '');
                } elseif (Schema::hasColumn('currency', 'currency')) {
                    $q->whereNull('currency')->orWhere('currency', '');
                }

                $q->orWhereNull('code')->orWhere('code', '')
                    ->orWhereNull('symbol')->orWhere('symbol', '');

                if (Schema::hasColumn('currency', 'country')) {
                    $q->orWhereNull('country')->orWhere('country', '');
                }
                if (Schema::hasColumn('currency', 'tags')) {
                    $q->orWhereNull('tags')->orWhere('tags', '');
                }
            })
            ->exists();
    }

    /**
     * @return object|null
     */
    private function findGlobalCurrencyByCode(string $code)
    {
        $query = DB::table('currency')->where('code', $code);
        if (Schema::hasColumn('currency', 'company_id')) {
            $query->where('company_id', 0);
        }

        return $query->orderBy(CurrencySchemaHelper::primaryKeyColumn())->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayloadFromLegacyRow(object $row, int $legacyId, string $code): array
    {
        $tags = $this->buildTagsFromLegacyRow($row);
        $name = (string) (($row->Name ?? '') !== '' ? $row->Name : $code);

        $payload = [
            'code' => $code,
            'symbol' => (string) (($row->Symbol ?? '') !== '' ? $row->Symbol : $code),
            'company_id' => 0,
            'status' => 1,
            'country' => (string) ($row->Contry ?? ''),
        ];

        if (Schema::hasColumn('currency', 'name')) {
            $payload['name'] = $name;
        }

        if (Schema::hasColumn('currency', 'currency')) {
            $payload['currency'] = Schema::hasColumn('currency', 'name') ? $code : $name;
        }

        if (Schema::hasColumn('currency', 'tags')) {
            $payload['tags'] = json_encode($tags, JSON_UNESCAPED_UNICODE);
        }

        if (Schema::hasColumn('currency', 'created_at')) {
            $payload['created_at'] = now();
            $payload['updated_at'] = now();
        } elseif (Schema::hasColumn('currency', 'created')) {
            $payload['created'] = now();
        }

        $payload = CurrencySchemaHelper::applyPrimaryKeyToPayload($payload, $legacyId);

        return $this->filterPayloadToColumns($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTagsFromLegacyRow(object $row): array
    {
        $tags = [
            'main_category' => trim((string) ($row->GroupID ?? '')),
            'country_category' => trim((string) ($row->Contry ?? '')),
            'main_category_es' => trim((string) ($row->GroupIdspa ?? '')),
            'name_es' => trim((string) ($row->Namespa ?? '')),
        ];

        if ((string) ($row->default ?? '') === 'selected') {
            $tags['default'] = 'selected';
        }

        return array_filter($tags, fn ($value) => ! $this->isEmptyValue($value));
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergeMissingCurrencyFields(array $existing, array $payload): array
    {
        $update = [];

        foreach ($payload as $column => $newValue) {
            if (in_array($column, ['id', 'currency_id', 'created_at', 'created'], true)) {
                continue;
            }

            if ($column === 'tags') {
                $mergedTags = $this->mergeTags(
                    $this->decodeTags($existing['tags'] ?? null),
                    $this->decodeTags($newValue)
                );
                if ($mergedTags !== $this->decodeTags($existing['tags'] ?? null)) {
                    $update['tags'] = json_encode($mergedTags, JSON_UNESCAPED_UNICODE);
                }

                continue;
            }

            if ($this->isEmptyValue($newValue)) {
                continue;
            }

            $oldValue = $existing[$column] ?? null;
            if ($this->isEmptyValue($oldValue)) {
                $update[$column] = $newValue;
            }
        }

        return $this->filterPayloadToColumns($update);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function filterPayloadToColumns(array $payload): array
    {
        $filtered = [];
        foreach ($payload as $column => $value) {
            if (Schema::hasColumn('currency', $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function insertLegacyCurrencyRow(array $payload, bool $preserveId = false): bool
    {
        $insert = $this->filterPayloadToColumns($payload);
        $pk = CurrencySchemaHelper::primaryKeyColumn();
        $fk = CurrencySchemaHelper::companiesCurrencyOwnerKey();
        $legacyId = (int) ($insert[$fk] ?? $insert[$pk] ?? $insert['currency_id'] ?? $insert['id'] ?? 0);

        if ($this->tryInsertCurrencyRow($insert)) {
            return $legacyId > 0 ? $this->currencyForeignKeyExists($legacyId) : true;
        }

        if (! $preserveId || $legacyId < 1) {
            return false;
        }

        $fallback = $insert;
        if ($pk === 'id' && isset($fallback['id'])) {
            unset($fallback['id']);
        }
        if ($fk === 'currency_id') {
            $fallback['currency_id'] = $legacyId;
        }

        if ($this->tryInsertCurrencyRow($fallback)) {
            return $this->currencyForeignKeyExists($legacyId);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $insert
     */
    private function tryInsertCurrencyRow(array $insert): bool
    {
        if ($insert === []) {
            return false;
        }

        try {
            DB::table('currency')->insert($insert);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  object|array<string, mixed>  $row
     */
    private function rowPrimaryKeyValue(object|array $row): int
    {
        $pk = CurrencySchemaHelper::primaryKeyColumn();
        if (is_array($row)) {
            return (int) ($row[$pk] ?? $row['id'] ?? $row['currency_id'] ?? 0);
        }

        return (int) ($row->{$pk} ?? $row->id ?? $row->currency_id ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeTags(mixed $tags): array
    {
        if (is_array($tags)) {
            return $tags;
        }

        if (! is_string($tags) || trim($tags) === '') {
            return [];
        }

        $decoded = json_decode($tags, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function mergeTags(array $existing, array $legacy): array
    {
        $merged = $existing;
        foreach ($legacy as $key => $value) {
            if ($this->isEmptyValue($value)) {
                continue;
            }
            if (! isset($merged[$key]) || $this->isEmptyValue($merged[$key])) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    private function currencyDisplayName(Currency $currency): string
    {
        if (Schema::hasColumn('currency', 'name')) {
            $name = trim((string) ($currency->name ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        if (Schema::hasColumn('currency', 'currency')) {
            $legacyName = trim((string) ($currency->currency ?? ''));
            if ($legacyName !== '' && ! Schema::hasColumn('currency', 'name')) {
                return $legacyName;
            }
        }

        return trim((string) ($currency->code ?? ''));
    }
}
