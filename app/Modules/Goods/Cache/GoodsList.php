<?php


namespace App\Modules\Goods\Cache;


use App\Models\GoodsToCategory;
use App\Utils\Helper;
use App\Utils\RedisHelper;

class GoodsList
{
    /**
     * 缓存保存
     * @param $cid
     * @param int|array $gid
     * @param int $switch //0：删除；1：新增
     * @return bool
     */
    public static function categoryGoodsListCache($cid, $gid, int $switch = 1)
    {
        $redis = RedisHelper::connect();
        $switch == 1 && $redis->zAdd(sprintf(Rediskey::CATEGORY_GOODS_LIST, $cid), Helper::millitime(), $gid);
        $switch == 0 && $redis->zRem(sprintf(Rediskey::CATEGORY_GOODS_LIST, $cid), $gid);;
        return true;
    }

    /**
     * 以商品参照批量处理商品类目列表
     * @param $gid
     * @param int $switch
     * @param array $delCategoryIds
     * @return bool
     */
    public static function batchCategoryGoodsListCache($gid, $switch = 1, $delCategoryIds = [])
    {
        if ($switch == 1) {
            $categoryIds = GoodsToCategory::query()->where('deleted_at', 0)->where('gid', $gid)
                ->pluck('cid')->toArray();
        } else {
            $categoryIds = $delCategoryIds;
        }
        foreach ($categoryIds as $categoryId) {
            self::categoryGoodsListCache($categoryId, $gid, $switch);
        }
        return true;
    }
}
