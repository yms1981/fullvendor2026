<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategorySubgroup extends Model
{
    use SoftDeletes;

    protected $table = 'category_subgroups';
    protected $primaryKey = 'subgroup_id';

    protected $casts = [
        'company_id' => 'int',
        'group_id' => 'int',
        'subgroup_status' => 'int',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'company_id',
        'group_id',
        'subgroup_name',
        'subgroup_status',
        'sort_order',
        'image',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function group()
    {
        return $this->belongsTo(CategoryGroup::class, 'group_id', 'group_id');
    }

    /**
     * No llamar "image" — choca con la FK UUID `category_subgroups.image`.
     */
    public function imageRecord()
    {
        return $this->belongsTo(Image::class, 'image', 'uuid');
    }
}
