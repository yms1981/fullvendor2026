<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerAddressNormalizerService;
use Illuminate\Console\Command;

class NormalizeCustomerAddressesCommand extends Command
{
    protected $signature = 'customers:normalize-addresses
                            {--company= : company_id opcional}
                            {--dry-run : Solo mostrar cambios sin guardar}
                            {--limit=0 : Máximo de clientes a procesar (0=todos)}';

    protected $description = 'Descompone commercial/dispatch address completas en state/city/zip/country y deja solo la calle en address.';

    public function handle(CustomerAddressNormalizerService $normalizer): int
    {
        $companyId = $this->option('company') !== null && $this->option('company') !== ''
            ? (int) $this->option('company')
            : null;
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $query = Customer::query()->orderBy('customer_id');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $updated = 0;
        $scanned = 0;

        foreach ($query->cursor() as $customer) {
            $scanned++;
            $before = [
                'commercial_address' => $customer->commercial_address,
                'commercial_country' => $customer->commercial_country,
                'commercial_state' => $customer->commercial_state,
                'commercial_city' => $customer->commercial_city,
                'commercial_zip_code' => $customer->commercial_zip_code,
                'commercial_delivery_address' => $customer->commercial_delivery_address,
                'dispatch_address' => $customer->dispatch_address,
                'dispatch_country' => $customer->dispatch_country,
                'dispatch_state' => $customer->dispatch_state,
                'dispatch_city' => $customer->dispatch_city,
                'dispatch_zip_code' => $customer->dispatch_zip_code,
                'dispatch_delivery_address' => $customer->dispatch_delivery_address,
            ];

            $after = $normalizer->normalizeCustomerColumns($before);
            $changed = false;
            foreach ($after as $k => $v) {
                $old = $before[$k] ?? null;
                $oldN = $old === null || $old === '' ? null : mb_strtoupper(trim((string) $old), 'UTF-8');
                $newN = $v === null || $v === '' ? null : (string) $v;
                if ($oldN !== $newN) {
                    $changed = true;
                    break;
                }
            }

            if (! $changed) {
                continue;
            }

            $updated++;
            if ($dryRun) {
                $this->line(sprintf(
                    '#%d | %s → [%s | %s | %s | %s | %s]',
                    (int) $customer->customer_id,
                    (string) ($before['commercial_address'] ?? ''),
                    (string) ($after['commercial_address'] ?? ''),
                    (string) ($after['commercial_state'] ?? ''),
                    (string) ($after['commercial_city'] ?? ''),
                    (string) ($after['commercial_zip_code'] ?? ''),
                    (string) ($after['commercial_country'] ?? '')
                ));
                continue;
            }

            $customer->fill($after);
            $customer->save();
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Escaneados {$scanned}, a actualizar {$updated}.");

        return self::SUCCESS;
    }
}
