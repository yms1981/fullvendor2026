<?php

namespace App\Models;

use App\Enums\CompanyUserTypes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class CompanyUser
 *
 * @property int $company_id
 * @property int $user_id
 * @property string $type
 * @package App\Models
 * @property int|null $profileable_id
 * @property string|null $profileable_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser whereProfileableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser whereProfileableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyUser whereUserId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Companyuser',
    description: 'CompanyUser model'
)]
class CompanyUser extends Pivot
{
    protected $table = 'company_user';
    public $incrementing = false;

    protected $casts = [
        'company_id' => 'int',
        'user_id' => 'int',
        'type' => 'string',
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'profileable_id',
        'profileable_type',
    ];

    public function profileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function typeModel()
    {
        return match ($this->type) {
            CompanyUserTypes::Seller->value => \App\Models\Vendor::where('user_id', $this->user_id)
                ->where('company_id', $this->company_id)
                ->first(),
            CompanyUserTypes::Customer->value => \App\Models\Customer::where('user_id', $this->user_id)
                ->where('company_id', $this->company_id)
                ->first(),
            CompanyUserTypes::Warehouse->value => \App\Models\WarehouseUser::where('user_id', $this->user_id)
                ->where('company_id', $this->company_id)
                ->first(),
            default => null,
        };
    }

//    public function managerProfile(): BelongsTo
//    {
//        return $this->belongsTo(ManagerProfiles::class, 'manager_profile_id', 'rol_id'); //table renamed to manager_profiles
//    }
//
//    public function sellerProfile(): BelongsTo
//    {
//        return $this->belongsTo(SellerProfiles::class, 'seller_profile_id', 'profile_id'); //table renamed to seller_profiles
//    }
}
