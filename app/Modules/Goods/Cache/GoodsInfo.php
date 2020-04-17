<?php


namespace App\Modules\Goods\Cache;

use App\Modules\Goods\Sku;
use App\Modules\Goods\Spec;
use App\Utils\RedisHelper;

class GoodsInfo
{
    /**
     * 商品详情缓存
     * @param $gid
     * @return bool
     */
    public static function saveGoodsDetail($gid)
    {
        $goodsInfo = \App\Models\GoodsInfo::query()
            ->where('id', $gid)
            ->first()
            ->toArray();
        empty($goodsInfo['banner_imgs']) && $goodsInfo['banner_imgs'] = explode(',', $goodsInfo['banner_imgs']);
        empty($goodsInfo['banner_video']) && $goodsInfo['banner_video'] = explode(',', $goodsInfo['banner_video']);
        $goodsInfo['max_price'] = bcdiv($goodsInfo['max_price'], 100, 2);
        $goodsInfo['min_price'] = bcdiv($goodsInfo['min_price'], 100, 2);
        //获取商品的规格信息
        $goodsInfo['spec_info'] = Spec::getGoodsSpecInfo($gid);
        //获取sku信息
        $goodsInfo['sku_info'] = Sku::getSkuInfo($goodsInfo['spec_info'], $gid);
        unset($goodsInfo['status']);
        unset($goodsInfo['created_at']);
        unset($goodsInfo['updated_at']);
        unset($goodsInfo['deleted_at']);
        self::saveGoodsDetailCache($gid, 1, $goodsInfo);
        return true;
    }

    public static function saveGoodsDetailCache($gid, $switch = 1, $detail = [])
    {
        $switch == 1 && RedisHelper::connect()->hSet(Rediskey::GOODS_DETAIL, $gid, json_encode($detail, JSON_UNESCAPED_UNICODE));
        $switch == 0 && RedisHelper::connect()->hDel(Rediskey::GOODS_DETAIL, $gid);
        return true;
    }
}
