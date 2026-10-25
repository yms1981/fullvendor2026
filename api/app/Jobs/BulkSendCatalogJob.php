<?php

namespace App\Jobs;

use App\Actions\SendCatalogService;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkSendCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Company $company;
    protected \Illuminate\Database\Eloquent\Collection $customers;
    protected array $catalogIds;
    protected array $requestData;

    public function __construct(Company $company, $customers, array $catalogIds, array $requestData)
    {
        $this->company = $company;
        $this->customers = $customers;
        $this->catalogIds = $catalogIds;
        $this->requestData = $requestData;
    }

    public function handle(SendCatalogService $service): void
    {
        $errors = [];
        $attempts = 0;

        foreach ($this->customers as $customer) {
            foreach ($this->catalogIds as $catalogId) {
                $attempts++;
                try {
                    $service->execute($this->company, $customer, (int) $catalogId, $this->requestData);
                } catch (\Throwable $e) {
                    $msg = "Catálogo {$catalogId}, cliente {$customer->customer_id}: " . $e->getMessage();
                    Log::error('BulkSendCatalogJob: ' . $msg);
                    $errors[] = $msg;
                }
            }
        }

        if ($attempts > 0 && count($errors) === $attempts) {
            throw new \RuntimeException(
                count($errors) === 1
                    ? $errors[0]
                    : ('Todos los envíos fallaron. Primero: ' . $errors[0])
            );
        }

        if ($errors !== []) {
            Log::warning('BulkSendCatalogJob: envíos parciales con errores', ['count' => count($errors), 'sample' => array_slice($errors, 0, 3)]);
        }
    }
}
