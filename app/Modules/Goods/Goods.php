<?php


namespace App\Modules\Goods;


use App\Models\GoodsInfo;
use App\Models\GoodsSkuInfo;
use App\Models\GoodsSpec;
use App\Models\GoodsSpecValue;
use App\Models\GoodsToCategory;
use App\Modules\Goods\Cache\GoodsList;
use Illuminate\Support\Facades\DB;

class Goods
{
    private static $lastErrorMsg = "";

    private static $lastErrorCode = 0;

    //仅此模块使用，非系统错误码
    const errorCode = [
        'save_goods_error' => 4001,
        'spec_rule_error' => 4002,
        'spec_disable_save' => 4003,
        'update_goods_not_found' => 4004,
        'delete_goods_error' => 4005,
    ];

    public static function getLastErrorCode()
    {
        return self::$lastErrorCode;
    }

    public static function getLastErrorMsg()
    {
        return self::$lastErrorMsg;
    }

    /**
     * 保存商品信息（新增|更新）
     * @param array $param
     * @param int $gid
     * @return bool
     */
    public static function saveGoods(array $param, int $gid = 0)
    {
        //验证规格组合是否正确
        $specCombineEnable = Spec::checkSpecCombineEnable($param['spec_info'], $param['sku_info']);
        if (!$specCombineEnable) {
            self::$lastErrorCode = self::errorCode['spec_rule_error'];
            self::$lastErrorMsg = "规格信息组合有误";
            return false;
        }
        //验证规格是否可以保存
        $specInfo = Spec::checkSpecSaveEnable($param['spec_info']);
        if (!$specInfo) {
            self::$lastErrorCode = self::errorCode['spec_disable_save'];
            self::$lastErrorMsg = "有重复的规格信息";
            return false;
        }
        //是更新商品
        $goodsStatus = 0;
        if ($gid != 0) {
            $exist = GoodsInfo::query()->select('id', 'status')->where('id', $gid)->where('deleted_at', 0)->first();
            if (!$exist) {
                self::$lastErrorCode = self::errorCode['update_goods_not_found'];
                self::$lastErrorMsg = "未找到相关商品";
                return false;
            }
            $goodsStatus = $exist->status;
        }
        //开始保存信息
        DB::beginTransaction();
        try {
            //商品信息保存
            $gid = GoodsInfo::createOrUpdate($param, $gid);

            //保存规格信息并返回带全id的规格信息
            $specInfo = Spec::saveSpecInfo($gid, $specInfo);
            if (!$specInfo)
                throw new \Exception("传入的旧规格信息有误");

            //保存sku信息
            $skuInfoSave = Sku::saveSkuInfo($gid, $param['sku_info'], $specInfo);
            if (!$skuInfoSave)
                throw new \Exception(Sku::getLastErrorMsg());

            //保存商品类目信息
            $categoryIds = $param['category_ids'] ?? "";    //逗号分隔字符串
            $categoryIds = Category::saveGoodsCategory($gid, $categoryIds);

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            self::$lastErrorCode = self::errorCode['save_goods_error'];
            self::$lastErrorMsg = $t->getMessage();
            return false;
        }
        //更新商品详情缓存
        \App\Modules\Goods\Cache\GoodsInfo::saveGoodsDetail($gid);
        //上架的商品，保存到类目商品列表缓存
        if (!empty($categoryIds) && $goodsStatus == 1) {
            foreach ($categoryIds['del_ids'] as $delId) {
                GoodsList::categoryGoodsListCache($delId, $gid, 0);
            }
            foreach ($categoryIds['new_ids'] as $newId) {
                GoodsList::categoryGoodsListCache($newId, $gid);
            }
        }
        //其他缓存.....
        return true;
    }

    /**
     * 商品删除
     * @param $gid
     * @return bool
     */
    public static function deleteGoods($gid)
    {
        $newTime = time();
        DB::beginTransaction();
        try {
            //清商品库
            GoodsInfo::query()->where('id', $gid)->update([
                'deleted_at' => $newTime
            ]);
            //清sku库
            GoodsSkuInfo::query()->where('gid', $gid)->where('deleted_at', 0)->update([
                'deleted_at' => $newTime
            ]);
            //清规格信息库
            GoodsSpec::query()->where('gid', $gid)->where('deleted_at', 0)->update([
                'deleted_at' => $newTime
            ]);
            GoodsSpecValue::query()->where('gid', $gid)->where('deleted_at', 0)->update([
                'deleted_at' => $newTime
            ]);
            //清商品类目关系
            $delCategoryIds = GoodsToCategory::query()->where('gid', $gid)->where('deleted_at', 0)
                ->pluck('cid')->toArray();
            GoodsToCategory::query()->where('gid', $gid)->where('deleted_at', 0)->update([
                'deleted_at' => $newTime
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            self::$lastErrorCode = self::errorCode['delete_goods_error'];
            self::$lastErrorMsg = $e->getMessage();
            return false;
        }
        //清详情缓存
        \App\Modules\Goods\Cache\GoodsInfo::saveGoodsDetailCache($gid, 0);
        \App\Modules\Goods\Cache\GoodsInfo::saveGoodsMainInfoCache($gid, 0);
        //清类目商品列表缓存
        GoodsList::batchCategoryGoodsListCache($gid, 0, $delCategoryIds);

        return true;
    }
}
