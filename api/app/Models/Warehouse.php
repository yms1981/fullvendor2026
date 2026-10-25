<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory;
    use Filterable;

    protected $casts = [
        'name' => 'string',
        'company_id' => 'int',
        'address_id' => 'int',
    ];
    protected $fillable = [
        'name',
        'company_id',
        'address_id',
    ];

    public function address(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'warehouse_users', 'warehouse_id', 'user_id')
            ->withPivot('company_id');
    }
}
