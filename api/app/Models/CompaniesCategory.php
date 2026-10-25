<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CompaniesCategory
 *
 * @property int $category_id
 * @property string|null $category_name
 * @property int|null $category_status
 * @property Carbon|null $category_created_at
 * @property Collection|Company[] $companies
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $companies_count
 * @method static \Database\Factories\CompaniesCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereCategoryCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereCategoryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CompaniesCategory withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Companiescategory',
    description: 'CompaniesCategory model'
)]
class CompaniesCategory extends Model
{

    use HasFactory;
    use Filterable;
use SoftDeletes;

    const CREATED_AT = 'category_created_at';
    protected $table = 'companies_categories';
    protected $primaryKey = 'category_id';


    protected $casts = [
        'category_status' => 'int',
        'category_created_at' => 'datetime'
    ];

    protected $fillable = [
        'category_name',
        'category_status',
        'category_created_at'
    ];

	public function companies()
	{
		return $this->hasMany(Company::class, 'company_category_id');
	}
}
