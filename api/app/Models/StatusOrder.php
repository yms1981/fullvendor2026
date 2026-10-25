<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\OrderStatusCodes;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class StatusOrder
 *
 * @property int $id_status
 * @property string|null $name_status_spanish
 * @property string|null $name_status_english
 * @property int|null $cod_status
 * @property string|null $color
 * @property int|null $disabled
 * @property string|null $sombrilla
 * @property string|null $color_icono
 * @property string|null $tipo_d
 * @property int|null $cannot_edit
 * @property int|null $can_show_data_asigned
 * @property int|null $send_email
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\StatusOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereCanShowDataAsigned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereCannotEdit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereCodStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereColorIcono($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereIdStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereNameStatusEnglish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereNameStatusSpanish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereSendEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereSombrilla($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereTipoD($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrder withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Statusorder',
    description: 'StatusOrder model'
)]
class StatusOrder extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'status_orders';
    protected $primaryKey = 'id_status';


    protected $casts = [
        'cod_status' => 'int',
        'disabled' => 'int',
        'cannot_edit' => 'int',
        'can_show_data_asigned' => 'int',
        'send_email' => 'int'
    ];

    protected $fillable = [
        'name_status_spanish',
        'name_status_english',
        'cod_status',
        'color',
        'disabled',
        'sombrilla',
        'color_icono',
        'tipo_d',
        'cannot_edit',
        'can_show_data_asigned',
        'send_email'
    ];

    public static function getIdByCode(int|OrderStatusCodes $code):int|bool {
        try {
            $intCode = is_a($code,OrderStatusCodes::class)?$code->value:$code;//overkill?
            /** @var StatusOrder $orderStatus */
            $orderStatus = self::where('cod_status', $intCode)->firstOrFail();
            return $orderStatus->id_status;
        }
        catch (ModelNotFoundException $e) {
            $orderStatus = self::create(['cod_status'=>OrderStatusCodes::SHOPPING]);
            if($orderStatus)
                return $orderStatus->id_status;
            return false;
        }

    }
}
