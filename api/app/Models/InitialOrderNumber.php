<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int|null $company_id
 * @property int|null $initial_number
 * @property-read \App\Models\Company|null $company
 * @method static \Database\Factories\InitialOrderNumberFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber query()
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber whereInitialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InitialOrderNumber whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Initialordernumber',
    description: 'InitialOrderNumber model'
)]
class InitialOrderNumber extends Model
{
    use HasFactory;
    use Filterable;

    protected $table = 'initial_order_number';
    protected $primaryKey = 'company_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'initial_number',
    ];

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
