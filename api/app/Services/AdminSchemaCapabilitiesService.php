<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Metadatos de esquema para el panel admin MVC (sustituye table_exists/field_exists en CodeIgniter).
 */
class AdminSchemaCapabilitiesService
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'plan' => $this->planCapabilities(),
            'subscriptions' => $this->subscriptionsCapabilities(),
            'invoices' => $this->invoicesCapabilities(),
            'issuing_entity' => $this->issuingEntityCapabilities(),
            'companies' => $this->companiesCapabilities(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function planCapabilities(): array
    {
        $table = 'plan';
        $installed = Schema::hasTable($table);
        if (! $installed) {
            return [
                'installed' => false,
                'primaryKey' => 'plan_id',
                'columns' => [],
                'hasLangSlots' => false,
                'hasLanguage1Id' => false,
                'hasLanguage2Id' => false,
                'hasSingleLanguageId' => false,
                'hasStatus' => false,
                'hasLifecycleStatus' => false,
                'hasStripeFields' => false,
                'columnMaxLengths' => [],
            ];
        }

        $hasLanguage1 = Schema::hasColumn($table, 'language1_id');
        $hasLanguage2 = Schema::hasColumn($table, 'language2_id');
        $hasLanguageId = Schema::hasColumn($table, 'language_id');
        $hasStripeld = Schema::hasColumn($table, 'stripeld');
        $hasStripeName = Schema::hasColumn($table, 'stripeName');

        return [
            'installed' => true,
            'primaryKey' => $this->resolvePrimaryKeyColumn($table, ['plan_id', 'subscription_id']),
            'columns' => Schema::getColumnListing($table),
            'hasLangSlots' => $hasLanguage1 || $hasLanguageId,
            'hasLanguage1Id' => $hasLanguage1,
            'hasLanguage2Id' => $hasLanguage2,
            'hasSingleLanguageId' => ! $hasLanguage1 && $hasLanguageId,
            'hasStatus' => Schema::hasColumn($table, 'status'),
            'hasLifecycleStatus' => Schema::hasColumn($table, 'lifecycle_status'),
            'hasStripeFields' => $hasStripeld || $hasStripeName,
            'columnMaxLengths' => $this->stringColumnMaxLengths($table, ['title', 'description']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionsCapabilities(): array
    {
        $table = 'subscriptions';
        $installed = Schema::hasTable($table);

        return [
            'installed' => $installed,
            'primaryKey' => $installed
                ? $this->resolvePrimaryKeyColumn($table, ['subscription_id', 'id'])
                : 'subscription_id',
            'columns' => $installed ? Schema::getColumnListing($table) : [],
            'hasPlanId' => $installed && Schema::hasColumn($table, 'plan_id'),
            'hasStatus' => $installed && Schema::hasColumn($table, 'status'),
            'hasStripeSubscriptionId' => $installed && Schema::hasColumn($table, 'stripe_subscription_id'),
            'hasNotes' => $installed && Schema::hasColumn($table, 'notes'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicesCapabilities(): array
    {
        $table = 'invoices';
        $installed = Schema::hasTable($table);

        return [
            'installed' => $installed,
            'primaryKey' => $installed
                ? $this->resolvePrimaryKeyColumn($table, ['invoice_id', 'id'])
                : 'invoice_id',
            'columns' => $installed ? Schema::getColumnListing($table) : [],
            'hasEntidadId' => $installed && Schema::hasColumn($table, 'entidad_id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function issuingEntityCapabilities(): array
    {
        $table = 'issuing_entity';
        $installed = Schema::hasTable($table);

        return [
            'installed' => $installed,
            'primaryKey' => $installed
                ? $this->resolvePrimaryKeyColumn($table, ['entity_id', 'id'])
                : 'entity_id',
            'columns' => $installed ? Schema::getColumnListing($table) : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companiesCapabilities(): array
    {
        $table = 'companies';
        $installed = Schema::hasTable($table);
        $addressFields = ['full_address', 'address', 'country', 'state', 'city', 'zip_code', 'zone'];
        $presentAddress = [];
        if ($installed) {
            foreach ($addressFields as $col) {
                if (Schema::hasColumn($table, $col)) {
                    $presentAddress[] = $col;
                }
            }
        }

        return [
            'installed' => $installed,
            'primaryKey' => $installed
                ? $this->resolvePrimaryKeyColumn($table, ['company_id', 'id'])
                : 'company_id',
            'columns' => $installed ? Schema::getColumnListing($table) : [],
            'hasStripeId' => $installed && Schema::hasColumn($table, 'stripe_id'),
            'hasUseWhatsapp' => $installed && Schema::hasColumn($table, 'use_whatsapp'),
            'hasWhatsappInstance' => $installed && Schema::hasColumn($table, 'whatsapp_instance'),
            'hasWhatsappInstanceName' => $installed && Schema::hasColumn($table, 'whatsapp_instance_name'),
            'hasWhatsappApikey' => $installed && Schema::hasColumn($table, 'whatsapp_apikey'),
            'hasCompanyCategoryId' => $installed && Schema::hasColumn($table, 'company_category_id'),
            'hasCategoryId' => $installed && Schema::hasColumn($table, 'category_id'),
            'hasMaxClients' => $installed && Schema::hasColumn($table, 'max_number_of_clients'),
            'hasMaxOrders' => $installed && Schema::hasColumn($table, 'max_number_of_orders'),
            'hasMaxVendors' => $installed && Schema::hasColumn($table, 'max_number_of_vendors'),
            'addressFields' => $presentAddress,
        ];
    }

    /**
     * @param list<string> $candidates
     */
    private function resolvePrimaryKeyColumn(string $table, array $candidates): string
    {
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) {
                return $col;
            }
        }

        try {
            $row = DB::selectOne(
                'SELECT COLUMN_NAME AS column_name '
                . 'FROM information_schema.STATISTICS '
                . 'WHERE TABLE_SCHEMA = DATABASE() '
                . 'AND TABLE_NAME = ? '
                . "AND INDEX_NAME = 'PRIMARY' "
                . 'ORDER BY SEQ_IN_INDEX ASC '
                . 'LIMIT 1',
                [$table]
            );
            if ($row !== null && isset($row->column_name) && is_string($row->column_name) && $row->column_name !== '') {
                return $row->column_name;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $candidates[0] ?? 'id';
    }

    /**
     * @param list<string> $columns
     *
     * @return array<string, int>
     */
    private function stringColumnMaxLengths(string $table, array $columns): array
    {
        if ($columns === []) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $rows = DB::select(
                'SELECT COLUMN_NAME AS column_name, CHARACTER_MAXIMUM_LENGTH AS max_length '
                . 'FROM information_schema.COLUMNS '
                . 'WHERE TABLE_SCHEMA = DATABASE() '
                . 'AND TABLE_NAME = ? '
                . "AND COLUMN_NAME IN ($placeholders)",
                array_merge([$table], $columns)
            );
            $out = [];
            foreach ($rows as $row) {
                $name = (string) ($row->column_name ?? '');
                $len = (int) ($row->max_length ?? 0);
                if ($name !== '' && $len > 0) {
                    $out[$name] = $len;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
