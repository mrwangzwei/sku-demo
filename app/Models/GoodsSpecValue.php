<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class GoodsSpecValue extends Model
{
    public $timestamps = false;

    protected $table = "goods_spec_value";

    protected $guarded = [];

    public function specName()
    {
        return $this->belongsTo(GoodsSpec::class, 'spec_id','id');
    }
}
