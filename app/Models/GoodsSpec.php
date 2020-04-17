<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class GoodsSpec extends Model
{
    public $timestamps = false;

    protected $table = "goods_spec";

    protected $guarded = [];

    public function specValues()
    {
        return $this->hasMany(GoodsSpecValue::class, 'spec_id','id');
    }

}
