<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class GoodsCategory extends Model
{
    public $timestamps = false;

    protected $table = "goods_category";

    protected $guarded = [];

    public function hasChild()
    {
        return $this->hasMany(GoodsCategory::class, 'pid', 'id');
    }

    public function toParent()
    {
        return $this->belongsTo(GoodsCategory::class, 'pid', 'id');
    }
}
