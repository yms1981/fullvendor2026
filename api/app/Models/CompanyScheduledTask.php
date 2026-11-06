<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyScheduledTask extends Model
{
    public const TYPE_SYNC_CUSTOMERS = 'sync_customers';

    public const TYPE_SYNC_PRODUCTS = 'sync_products';

    public const TYPE_SYNC_CATEGORIES = 'sync_categories';

    public const TYPE_SYNC_CUSTOMER_BALANCES = 'sync_customer_balances';

    public const TYPE_SYNC_PRODUCT_INVENTORIES = 'sync_product_inventories';

    public const TYPE_SYNC_PRODUCT_IMAGES = 'sync_product_images';

    public const FREQUENCY_MANUAL = 'manual';

    public const FREQUENCY_EVERY_15_MIN = 'every_15_min';

    /** @deprecated Use FREQUENCY_EVERY_1_HOUR */
    public const FREQUENCY_HOURLY = 'hourly';

    public const FREQUENCY_EVERY_1_HOUR = 'every_1_hour';

    public const FREQUENCY_EVERY_2_HOURS = 'every_2_hours';

    public const FREQUENCY_EVERY_3_HOURS = 'every_3_hours';

    public const FREQUENCY_EVERY_4_HOURS = 'every_4_hours';

    public const FREQUENCY_EVERY_5_HOURS = 'every_5_hours';

    public const FREQUENCY_EVERY_6_HOURS = 'every_6_hours';

    public const FREQUENCY_DAILY = 'daily';

    /**
     * @return list<string>
     */
    public static function taskTypes(): array
    {
        return [
            self::TYPE_SYNC_CUSTOMERS,
            self::TYPE_SYNC_PRODUCTS,
            self::TYPE_SYNC_CATEGORIES,
            self::TYPE_SYNC_CUSTOMER_BALANCES,
            self::TYPE_SYNC_PRODUCT_INVENTORIES,
            self::TYPE_SYNC_PRODUCT_IMAGES,
        ];
    }

    /**
     * @return list<string>
     */
    public static function frequencies(): array
    {
        return [
            self::FREQUENCY_MANUAL,
            self::FREQUENCY_EVERY_15_MIN,
            self::FREQUENCY_EVERY_1_HOUR,
            self::FREQUENCY_EVERY_2_HOURS,
            self::FREQUENCY_EVERY_3_HOURS,
            self::FREQUENCY_EVERY_4_HOURS,
            self::FREQUENCY_EVERY_5_HOURS,
            self::FREQUENCY_EVERY_6_HOURS,
            self::FREQUENCY_DAILY,
        ];
    }

    protected $table = 'company_scheduled_tasks';

    protected $fillable = [
        'company_id',
        'task_type',
        'enabled',
        'frequency',
        'last_run_at',
        'last_status',
        'last_message',
        'next_run_at',
    ];

    protected $casts = [
        'company_id' => 'int',
        'enabled' => 'bool',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function computeNextRunAt(?\DateTimeInterface $from = null): ?\Carbon\Carbon
    {
        if (! $this->enabled || $this->frequency === self::FREQUENCY_MANUAL) {
            return null;
        }

        $from = $from ? \Carbon\Carbon::instance(\DateTimeImmutable::createFromInterface($from)) : now();

        return match ($this->frequency) {
            self::FREQUENCY_EVERY_15_MIN => $from->copy()->addMinutes(15),
            self::FREQUENCY_HOURLY, self::FREQUENCY_EVERY_1_HOUR => $from->copy()->addHours(1),
            self::FREQUENCY_EVERY_2_HOURS => $from->copy()->addHours(2),
            self::FREQUENCY_EVERY_3_HOURS => $from->copy()->addHours(3),
            self::FREQUENCY_EVERY_4_HOURS => $from->copy()->addHours(4),
            self::FREQUENCY_EVERY_5_HOURS => $from->copy()->addHours(5),
            self::FREQUENCY_EVERY_6_HOURS => $from->copy()->addHours(6),
            self::FREQUENCY_DAILY => $from->copy()->addDay(),
            default => null,
        };
    }
}
