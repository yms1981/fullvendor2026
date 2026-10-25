<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ImportHistory
 *
 * @property int $import_id
 * @property int $company_id
 * @property Carbon|null $import_date
 * @property string $importedfile
 * @property int $records_imported
 * @property Company $company
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\ImportHistoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereImportDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereImportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereImportedfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereRecordsImported($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ImportHistory withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Importhistory',
    description: 'ImportHistory model'
)]
class ImportHistory extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'import_history';
    protected $primaryKey = 'import_id';


    protected $casts = [
        'company_id' => 'int',
        'import_date' => 'datetime',
        'records_imported' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'import_date',
        'importedfile',
        'records_imported'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}
}
