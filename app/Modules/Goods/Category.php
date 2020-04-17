<?php


namespace App\Modules\Goods;


use App\Models\GoodsCategory;
use App\Models\GoodsToCategory;

class Category
{
    private static $lastErrorMsg = "";

    private static $lastErrorCode = 0;

    public static function getLastErrorCode()
    {
        return self::$lastErrorCode;
    }

    public static function getLastErrorMsg()
    {
        return self::$lastErrorMsg;
    }

    /**
     * 保存商品类目关系
     * @param $gid
     * @param $categoryIds //二级类目id集，逗号分隔字符串
     * @return array   //返回被使用的类目id
     */
    public static function saveGoodsCategory($gid, $categoryIds)
    {
        if (empty($categoryIds))
            return [];
        $categoryIds = explode(',', $categoryIds);
        $categoryIds = GoodsCategory::query()->whereIn('id', $categoryIds)
            ->where('pid', '<>', 0)->pluck('id')->toArray();
        $oldCids = GoodsToCategory::query()->where('gid', $gid)->where('deleted_at', 0)
            ->pluck('cid')->toArray();
        //删除不用的
        $delIds = array_diff($oldCids, $categoryIds);
        if (!empty($delIds))
            GoodsToCategory::query()->where('gid', $gid)->whereIn('cid', $delIds)->update([
                'deleted_at' => time()
            ]);
        //加新的
        $newIds = array_diff($categoryIds, $oldCids);
        if (!empty($newIds)) {
            foreach ($newIds as $newId) {
                GoodsToCategory::query()->insert([
                    'gid' => $gid,
                    'cid' => $newId,
                    'created_at' => time()
                ]);
            }
        }
        return [
            'del_ids' => $delIds,
            'new_ids' => $newIds
        ];
    }

    /**
     * 查询商品类目信息
     * @param $gid
     * @return array
     */
    public static function getGoodsCategory($gid)
    {
        $cids = GoodsToCategory::query()->where('deleted_at', 0)->where('gid', $gid)
            ->pluck('cid')->toArray();
        if (empty($cids)) {
            return [];
        }
        return GoodsCategory::query()->select('id', 'name', 'pid', 'tiny_img')->with(['toParent' => function ($query) {
            $query->select('id', 'name', 'tiny_img');
        }])->whereIn('id', $cids)->get()->toArray();
    }
}
