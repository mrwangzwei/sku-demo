<?php


namespace App\Modules\Goods;


use App\Models\GoodsSpecTemp;

class SpecTemp
{
    public static function filterPids($ids)
    {
        $pTemp = GoodsSpecTemp::query()->where('pid', 0)->whereIn('id', $ids)->pluck('id')->toArray();
        return $pTemp;
    }
}
