<?php

namespace App\Services;

use App\Models\CompanyMessagingDaily;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Acumula envíos de email / SMS / WhatsApp por compañía y día.
 */
class CompanyMessagingStatsService
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public function record(int $companyId, string $channel, int $quantity = 1, ?string $statDate = null): void
    {
        if ($companyId <= 0 || $quantity <= 0) {
            return;
        }

        $column = match (strtolower(trim($channel))) {
            self::CHANNEL_EMAIL => 'email_count',
            self::CHANNEL_SMS => 'sms_count',
            self::CHANNEL_WHATSAPP => 'whatsapp_count',
            default => null,
        };
        if ($column === null) {
            return;
        }

        if (! Schema::hasTable('company_messaging_daily')) {
            return;
        }

        $date = $statDate !== null && $statDate !== ''
            ? $statDate
            : now()->toDateString();

        try {
            DB::transaction(function () use ($companyId, $column, $quantity, $date) {
                $row = CompanyMessagingDaily::query()
                    ->where('company_id', $companyId)
                    ->whereDate('stat_date', $date)
                    ->lockForUpdate()
                    ->first();

                if ($row) {
                    $row->increment($column, $quantity);
                    return;
                }

                CompanyMessagingDaily::query()->create([
                    'company_id' => $companyId,
                    'stat_date' => $date,
                    'email_count' => $column === 'email_count' ? $quantity : 0,
                    'sms_count' => $column === 'sms_count' ? $quantity : 0,
                    'whatsapp_count' => $column === 'whatsapp_count' ? $quantity : 0,
                ]);
            });
        } catch (Throwable $e) {
            // No bloquear el envío si falla el contador (p. ej. carrera en unique).
            try {
                DB::table('company_messaging_daily')->upsert(
                    [
                        'company_id' => $companyId,
                        'stat_date' => $date,
                        'email_count' => $column === 'email_count' ? $quantity : 0,
                        'sms_count' => $column === 'sms_count' ? $quantity : 0,
                        'whatsapp_count' => $column === 'whatsapp_count' ? $quantity : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    ['company_id', 'stat_date'],
                    [
                        $column => DB::raw("`{$column}` + {$quantity}"),
                        'updated_at' => now(),
                    ]
                );
            } catch (Throwable $e2) {
                Log::warning('CompanyMessagingStatsService: no se pudo registrar contador', [
                    'company_id' => $companyId,
                    'channel' => $channel,
                    'error' => $e2->getMessage(),
                ]);
            }
        }
    }

    public function recordEmail(int $companyId, int $quantity = 1): void
    {
        $this->record($companyId, self::CHANNEL_EMAIL, $quantity);
    }

    public function recordSms(int $companyId, int $quantity = 1): void
    {
        $this->record($companyId, self::CHANNEL_SMS, $quantity);
    }

    public function recordWhatsApp(int $companyId, int $quantity = 1): void
    {
        $this->record($companyId, self::CHANNEL_WHATSAPP, $quantity);
    }
}
