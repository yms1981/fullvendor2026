<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomerUser
 *
 * @property int $customer_id
 * @property int $user_id
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerUser withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Customeruser',
    description: 'CustomerUser model'
)]
class CustomerUser extends Model
{
    use SoftDeletes;

    protected $table = 'customer_user';
    public $incrementing = false;

    protected $casts = [
        'customer_id' => 'int',
        'user_id ' => 'int'
    ];

    protected $fillable = [
        'customer_id',
        'user_id'
    ];
}
