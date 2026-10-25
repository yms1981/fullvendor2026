<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileChangeLog extends Model
{
    public const GROUP_PRODUCTS = 'products';
    public const GROUP_CUSTOMERS = 'customers';
    public const GROUP_CATEGORIES = 'categories';
    public const GROUP_ORDERS = 'orders';

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';

    public const GROUPS = [
        self::GROUP_PRODUCTS,
        self::GROUP_CUSTOMERS,
        self::GROUP_CATEGORIES,
        self::GROUP_ORDERS,
    ];

    protected $table = 'mobile_change_log';

    protected $fillable = [
        'company_id',
        'entity_group',
        'entity_id',
        'action',
        'changed_at',
    ];

    protected $casts = [
        'company_id' => 'int',
        'entity_id' => 'int',
        'changed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
