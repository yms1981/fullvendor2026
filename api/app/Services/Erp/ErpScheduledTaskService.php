<?php

namespace App\Services\Erp;

use App\Models\Company;
use App\Models\CompanyScheduledTask;
use App\Models\CompanyScheduledTaskLog;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ErpScheduledTaskService
{
    public function ensureTasksForCompany(Company $company): void
    {
        foreach (CompanyScheduledTask::taskTypes() as $type) {
            CompanyScheduledTask::query()->firstOrCreate(
                [
                    'company_id' => (int) $company->company_id,
                    'task_type' => $type,
                ],
                [
                    'enabled' => false,
                    'frequency' => CompanyScheduledTask::FREQUENCY_MANUAL,
                ]
            );
        }
    }

    /**
     * @return array{status: string, message: string, summary?: array<string, mixed>}
     */
    public function runTask(CompanyScheduledTask $task, string $trigger = CompanyScheduledTaskLog::TRIGGER_MANUAL): array
    {
        $startedAt = now();
        $company = Company::query()->find($task->company_id);
        if (! $company) {
            return $this->finish($task, 'error', 'Company not found.', [], $trigger, $startedAt);
        }

        if (! (bool) ($company->erp_enabled ?? false)) {
            return $this->finish($task, 'error', 'ERP connection is disabled.', [], $trigger, $startedAt);
        }

        try {
            $client = ErpSyncClient::forCompany($company);
            $summary = match ($task->task_type) {
                CompanyScheduledTask::TYPE_SYNC_CUSTOMERS => $this->syncCustomers($company, $client),
                CompanyScheduledTask::TYPE_SYNC_PRODUCTS => $this->syncProducts($company, $client),
                CompanyScheduledTask::TYPE_SYNC_CATEGORIES => $this->syncCategories($company, $client),
                CompanyScheduledTask::TYPE_SYNC_CUSTOMER_BALANCES => $this->syncCustomerBalances($company, $client),
                CompanyScheduledTask::TYPE_SYNC_PRODUCT_INVENTORIES => $this->syncProductInventories($company, $client),
                CompanyScheduledTask::TYPE_SYNC_PRODUCT_IMAGES => $this->syncProductImages($company, $client),
                default => throw new \InvalidArgumentException('Unknown task type: '.$task->task_type),
            };

            $message = $this->summarizeMessage($task->task_type, $summary);

            return $this->finish($task, 'success', $message, $summary, $trigger, $startedAt);
        } catch (Throwable $e) {
            Log::error('erp.scheduled_task.failed', [
                'company_id' => $task->company_id,
                'task_type' => $task->task_type,
                'error' => $e->getMessage(),
            ]);

            return $this->finish($task, 'error', $e->getMessage(), [], $trigger, $startedAt);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function syncCustomers(Company $company, ErpSyncClient $client): array
    {
        $rows = $client->fetchAll('/external/fv-sync/customers');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $taxId = trim((string) ($row['tax_id'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            if ($taxId === '' && $email === '') {
                $skipped++;
                continue;
            }

            $query = Customer::query()->where('company_id', $company->company_id);
            if ($taxId !== '') {
                $query->whereRaw('UPPER(TRIM(tax_id)) = ?', [mb_strtoupper($taxId, 'UTF-8')]);
            } else {
                $query->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower($email, 'UTF-8')]);
            }

            $customer = $query->first();
            $isNew = ! $customer;
            if (! $customer) {
                $customer = new Customer;
                $customer->company_id = (int) $company->company_id;
                $customer->customer_status = 1;
            }

            $customer->fill([
                'name' => (string) ($row['name'] ?? $customer->name ?? ''),
                'business_name' => $row['business_name'] ?? $customer->business_name,
                'tax_id' => $taxId !== '' ? $taxId : $customer->tax_id,
                'email' => $email !== '' ? $email : $customer->email,
                'phone' => $row['phone'] ?? $customer->phone,
                'cell_phone' => $row['cell_phone'] ?? $customer->cell_phone,
                'notes' => $row['notes'] ?? $customer->notes,
                'commercial_address' => $row['commercial_address'] ?? $customer->commercial_address,
                'commercial_delivery_address' => $row['commercial_delivery_address'] ?? $customer->commercial_delivery_address,
                'commercial_country' => $row['commercial_country'] ?? $customer->commercial_country,
                'commercial_state' => $row['commercial_state'] ?? $customer->commercial_state,
                'commercial_city' => $row['commercial_city'] ?? $customer->commercial_city,
                'commercial_zone' => $row['commercial_zone'] ?? $customer->commercial_zone,
                'commercial_zip_code' => $row['commercial_zip_code'] ?? $customer->commercial_zip_code,
                'dispatch_address' => $row['dispatch_address'] ?? $customer->dispatch_address,
                'dispatch_delivery_address' => $row['dispatch_delivery_address'] ?? $customer->dispatch_delivery_address,
                'dispatch_country' => $row['dispatch_country'] ?? $customer->dispatch_country,
                'dispatch_state' => $row['dispatch_state'] ?? $customer->dispatch_state,
                'dispatch_city' => $row['dispatch_city'] ?? $customer->dispatch_city,
                'dispatch_zone' => $row['dispatch_zone'] ?? $customer->dispatch_zone,
                'dispatch_zip_code' => $row['dispatch_zip_code'] ?? $customer->dispatch_zip_code,
                'dispatch_shipping_notes' => $row['dispatch_shipping_notes'] ?? $customer->dispatch_shipping_notes,
            ]);
            $customer->save();

            if (array_key_exists('balance', $row)) {
                DB::table('customers')
                    ->where('customer_id', $customer->customer_id ?? $customer->getKey())
                    ->update(['balance' => (float) $row['balance']]);
            }

            $isNew ? $created++ : $updated++;
        }

        return compact('created', 'updated', 'skipped') + ['total' => count($rows)];
    }

    /**
     * @return array<string, mixed>
     */
    private function syncProducts(Company $company, ErpSyncClient $client): array
    {
        $rows = $client->fetchAll('/external/fv-sync/products');
        $mapped = [];
        foreach ($rows as $row) {
            $mapped[] = [
                'sku' => $row['sku'] ?? '',
                'name' => $row['name'] ?? '',
                'sale_price' => $row['price'] ?? null,
                'stock' => $row['stock'] ?? ($row['existencia'] ?? null),
                'barcode' => $row['barcode'] ?? null,
                'descriptions' => $row['description'] ?? null,
                'images' => $row['images'] ?? [],
                'categories' => $row['categories'] ?? [],
            ];
        }

        return (new ErpProductSyncFromErpService)->sync((int) $company->company_id, $mapped);
    }

    /**
     * @return array<string, mixed>
     */
    private function syncCategories(Company $company, ErpSyncClient $client): array
    {
        $rows = $client->fetchAll('/external/fv-sync/categories');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }

            $nameColumn = \App\Support\CategoryNameColumn::name();
            $category = Category::query()
                ->where('company_id', $company->company_id)
                ->whereRaw('UPPER(TRIM('.$nameColumn.')) = ?', [mb_strtoupper($name, 'UTF-8')])
                ->first();

            if (! $category) {
                $category = new Category;
                $category->company_id = (int) $company->company_id;
                $category->{$nameColumn} = $name;
                $category->category_status = 1;
                $category->save();
                $created++;
            } else {
                $updated++;
            }
        }

        return compact('created', 'updated', 'skipped') + ['total' => count($rows)];
    }

    /**
     * @return array<string, mixed>
     */
    private function syncCustomerBalances(Company $company, ErpSyncClient $client): array
    {
        $rows = $client->fetchAll('/external/fv-sync/customer-balances');
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $taxId = trim((string) ($row['tax_id'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $query = Customer::query()->where('company_id', $company->company_id);
            if ($taxId !== '') {
                $query->whereRaw('UPPER(TRIM(tax_id)) = ?', [mb_strtoupper($taxId, 'UTF-8')]);
            } elseif ($email !== '') {
                $query->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower($email, 'UTF-8')]);
            } else {
                $skipped++;
                continue;
            }

            $customer = $query->first();
            if (! $customer) {
                $skipped++;
                continue;
            }

            DB::table('customers')
                ->where('customer_id', $customer->customer_id ?? $customer->getKey())
                ->update(['balance' => (float) ($row['balance'] ?? 0)]);
            $updated++;
        }

        return compact('updated', 'skipped') + ['total' => count($rows)];
    }

    /**
     * @return array<string, mixed>
     */
    private function syncProductInventories(Company $company, ErpSyncClient $client): array
    {
        $rows = $client->fetchAll('/external/fv-sync/product-inventories');
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                $skipped++;
                continue;
            }

            $product = Product::query()
                ->where('company_id', $company->company_id)
                ->whereRaw('UPPER(TRIM(sku)) = ?', [mb_strtoupper($sku, 'UTF-8')])
                ->first();

            if (! $product) {
                $skipped++;
                continue;
            }

            $stock = (float) ($row['stock'] ?? $row['existencia'] ?? 0);
            $product->stock = $stock;
            $product->status = $stock > 0 ? 1 : (int) ($product->status ?? 0);
            $product->modified_on = now();
            $product->save();
            $updated++;
        }

        return compact('updated', 'skipped') + ['total' => count($rows)];
    }

    /**
     * Encola imágenes del ERP y descarga de a una (cola + log por archivo).
     *
     * @return array<string, mixed>
     */
    private function syncProductImages(Company $company, ErpSyncClient $client): array
    {
        // Sin límite de productos: se encola desde el listado ERP completo;
        // la descarga sigue de a poco (processLimit) vía cola + cron.
        // Encolar todo; drenar solo un lote corto (el cron sigue el resto).
        return (new ErpProductImageQueueService)->enqueueAndProcess(
            $company,
            $client,
            productLimit: 0,
            processLimit: 10
        );
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array{status: string, message: string, summary?: array<string, mixed>}
     */
    private function finish(
        CompanyScheduledTask $task,
        string $status,
        string $message,
        array $summary = [],
        string $trigger = CompanyScheduledTaskLog::TRIGGER_MANUAL,
        mixed $startedAt = null
    ): array {
        $finishedAt = now();
        $started = $startedAt instanceof \DateTimeInterface
            ? \Carbon\Carbon::instance(\DateTimeImmutable::createFromInterface($startedAt))
            : $finishedAt->copy();
        $durationMs = max(0, (int) $started->diffInMilliseconds($finishedAt));

        $task->last_run_at = $finishedAt;
        $task->last_status = $status;
        $task->last_message = mb_substr($message, 0, 2000);
        $task->next_run_at = $task->computeNextRunAt($finishedAt);
        $task->save();

        CompanyScheduledTaskLog::query()->create([
            'company_id' => (int) $task->company_id,
            'scheduled_task_id' => (int) $task->id,
            'task_type' => (string) $task->task_type,
            'trigger' => $trigger === CompanyScheduledTaskLog::TRIGGER_CRON
                ? CompanyScheduledTaskLog::TRIGGER_CRON
                : CompanyScheduledTaskLog::TRIGGER_MANUAL,
            'status' => $status,
            'message' => mb_substr($message, 0, 5000),
            'summary' => $summary !== [] ? $summary : null,
            'started_at' => $started,
            'finished_at' => $finishedAt,
            'duration_ms' => $durationMs,
        ]);

        $out = ['status' => $status, 'message' => $message];
        if ($summary !== []) {
            $out['summary'] = $summary;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function summarizeMessage(string $type, array $summary): string
    {
        $parts = [];
        foreach ([
            'total', 'needing_photo', 'created', 'updated', 'synced', 'skipped', 'failed',
            'products_with_images', 'products_failed', 'invalid_urls', 'pruned_pending',
            'images_downloaded', 'queued', 'already_queued', 'processed', 'downloaded', 'remaining_pending',
        ] as $key) {
            if (array_key_exists($key, $summary)) {
                $parts[] = $key.'='.$summary[$key];
            }
        }

        return $type.($parts !== [] ? (': '.implode(', ', $parts)) : ' ok');
    }
}
