<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class GoodsSpecTemp extends Model
{
    public $timestamps = false;

    protected $table = "goods_spec_template";

    protected $guarded = [];

    public function specName()
    {
        return $this->belongsTo(GoodsSpecTemp::class, 'pid', 'id');
    }

    public function specValues()
    {
        return $this->hasMany(GoodsSpecTemp::class, 'pid', 'id');
    }
}
