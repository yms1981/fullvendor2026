<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $email
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property Customer|null $customer
 */
class CustomerEmail extends Model
{
    protected $table = 'customer_emails';

    protected $casts = [
        'customer_id' => 'int',
        'is_primary' => 'bool',
    ];

    protected $fillable = [
        'customer_id',
        'email',
        'is_primary',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}
