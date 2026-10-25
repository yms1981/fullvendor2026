<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryGroup extends Model
{
    use SoftDeletes;

    protected $table = 'category_groups';
    protected $primaryKey = 'group_id';

    protected $casts = [
        'company_id' => 'int',
        'category_id' => 'int',
        'group_status' => 'int',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'company_id',
        'category_id',
        'group_name',
        'group_status',
        'sort_order',
        'image',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'cat_id');
    }

    public function subgroups()
    {
        return $this->hasMany(CategorySubgroup::class, 'group_id', 'group_id')
            ->orderBy('sort_order')
            ->orderBy('subgroup_name');
    }

    /**
     * No llamar "image" — choca con la FK UUID `category_groups.image`.
     */
    public function imageRecord()
    {
        return $this->belongsTo(Image::class, 'image', 'uuid');
    }
}
