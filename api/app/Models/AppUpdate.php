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
 * Class AppUpdate
 *
 * @property Carbon|null $fecha
 * @property int $company_id
 * @property int $updatenumber
 * @property int $ID
 * @property Company $company
 * @package App\Models
 * @method static \Database\Factories\AppUpdateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate query()
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate whereID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|AppUpdate whereUpdatenumber($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Appupdate',
    description: 'AppUpdate model'
)]
class AppUpdate extends Model
{
    use HasFactory;
    use Filterable;
    protected $table = 'app_updates';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    public $timestamps = false;

    protected $casts = [
        'fecha' => 'datetime',
        'company_id' => 'int',
        'updatenumber' => 'int',
        'ID' => 'int'
    ];

    protected $fillable = [
        'fecha',
        'company_id',
        'updatenumber'
    ];


}
