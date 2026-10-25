<?php

namespace App\Models\Normalize;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Normalization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Normalization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Normalization query()
 * @method static \Illuminate\Database\Eloquent\Builder|Normalization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Normalization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Normalization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Normalization whereUpdatedAt($value)
 * @property string $time
 * @property int $seconds
 * @property int|null $amount_normalized
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Normalization whereAmountNormalized($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Normalization whereSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Normalization whereTime($value)
 * @mixin \Eloquent
 */
class Normalization extends Model
{
    protected $connection = 'normalize';
    public $timestamps = true;
    protected $fillable = ['name','time','seconds','amount_normalized'];
}
