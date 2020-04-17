<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class GoodsToCategory extends Model
{
    public $timestamps = false;

    protected $table = "goods_to_category";

    protected $guarded = [];
}
