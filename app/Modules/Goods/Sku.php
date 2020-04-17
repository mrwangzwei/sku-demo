<?php


namespace App\Modules\Goods;


use App\Models\GoodsInfo;
use App\Models\GoodsSkuInfo;
use Illuminate\Support\Facades\DB;

class Sku
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
     * 保存sku信息
     * @param $gid
     * @param $skuInfo
     * @param $specInfo
     * @return bool
     */
    public static function saveSkuInfo($gid, $skuInfo, $specInfo)
    {
        $oldSkuIds = GoodsSkuInfo::query()->where('gid', $gid)->where('deleted_at', 0)
            ->pluck('id')->toArray();
        $newSkuIds = [];
        foreach ($skuInfo as $sku) {
            $sku['id'] != 0 && $newSkuIds[] = $sku['id'];
            if (empty($sku['sku_no'])) {
                continue;
            }
            $exist = GoodsSkuInfo::query()->where('id', '<>', $sku['id'])->where('sku_no', $sku['sku_no'])->first();
            if ($exist) {
                self::$lastErrorMsg = "sku编码不唯一({$sku['sku_no']})";
                return false;
            }
        }
        //奇怪的东西混进来了，传入的skuid不属于此商品
        if (!empty(array_diff($newSkuIds, $oldSkuIds))) {
            self::$lastErrorMsg = "传入的旧sku信息有误";
            return false;
        }
        $delSkuIds = array_diff($oldSkuIds, $newSkuIds);
        //删除不要的sku
        if (!empty($delSkuIds)) {
            GoodsSkuInfo::query()->whereIn('id', $delSkuIds)->update([
                'deleted_at' => time()
            ]);
        }
        $maxPrice = 0;
        $minPrice = 9999999999;
        foreach ($skuInfo as $sku) {
            if ($sku['price'] < $minPrice)
                $minPrice = $sku['price'];
            if ($sku['price'] > $maxPrice)
                $maxPrice = $sku['price'];
            $skuSpecValueIds = [];
            foreach ($specInfo as $spec) {
                if (empty(array_diff($spec['spec_values'], $sku['spec_info']['spec_values']))) {
                    $skuSpecValueIds = $spec['spec_value_ids'];
                }
            }
            if ($sku['id'] != 0) {
                //更新的要验证一下规格id是不是匹配，不匹配就是脏请求
                $oldSkuSpecValueIds = GoodsSkuInfo::query()->where('id', $sku['id'])->value('spec_value_ids');
                $oldSkuSpecValueIds = explode(',', $oldSkuSpecValueIds);
                if (!empty(array_diff($skuSpecValueIds, $oldSkuSpecValueIds)) || !empty(array_diff($oldSkuSpecValueIds, $skuSpecValueIds))) {
                    self::$lastErrorMsg = "sku规格信息有误";
                    return false;
                }
                GoodsSkuInfo::query()->where('id', $sku['id'])->update([
                    'sku_name' => $sku['sku_name'] ?? "",
                    'description' => $sku['description'] ?? "",
                    'price' => bcmul($sku['price'], 100),
                    'stock' => DB::raw("stock+{$sku['stock']}"),
                    'spec_values' => implode(',', $sku['spec_info']['spec_values']),
                    'updated_at' => time()
                ]);
            } else {
                $skuNo = $sku['sku_no'] ?? "";
                if (empty($skuNo) && env("AUTO_CREATE_SKUNO", false) == true) {
                    $skuNo = str_pad((string)mt_rand(1, 9999999999), 10, 0, STR_PAD_RIGHT) .
                        time() . str_pad((string)mt_rand(1, 9999999999), 10, 0, STR_PAD_LEFT);
                }
                GoodsSkuInfo::query()->insertGetId([
                    'gid' => $gid,
                    'sku_no' => $skuNo,
                    'sku_name' => $sku['sku_name'] ?? "",
                    'description' => $sku['description'] ?? "",
                    'price' => bcmul($sku['price'], 100),
                    'stock' => $sku['stock'],
                    'spec_value_ids' => implode(',', $skuSpecValueIds),
                    'spec_values' => implode(',', $sku['spec_info']['spec_values']),
                    'status' => 1,
                    'created_at' => time()
                ]);
            }
        }
        //更新商品最高/低价
        GoodsInfo::query()->where('id', $gid)->update([
            'max_price' => bcmul($maxPrice, 100),
            'min_price' => bcmul($minPrice, 100)
        ]);
        return true;
    }

    /**
     * 获取sku信息（商品详情使用,需要拼装规格信息）
     * @param $gid
     * @param $specInfo
     * @return array
     */
    public static function getSkuInfo($specInfo, $gid)
    {
        $specInfo = Spec::combineSpecInfo($specInfo);
        $skuList = GoodsSkuInfo::query()
            ->select('id', 'sku_no', 'sku_name', 'description', 'price', 'stock', 'spec_value_ids', 'spec_values', 'status', 'created_at')
            ->where('gid', $gid)->where('deleted_at', 0)
            ->get()->toArray();
        $skuInfo = [];
        foreach ($specInfo as $spec) {
            $skuRes = [
                'id' => 0,
                'sku_no' => "",
                'sku_name' => "",
                'description' => "",
                'price' => 0,
                'stock' => 0,
                'spec_info' => [
                    'spec_value_ids' => $spec['spec_value_ids'],
                    'spec_values' => $spec['spec_values'],
                ],
                'status' => 0,
                'created_at' => 0
            ];
            if (!empty($skuList)) {
                foreach ($skuList as $sku) {
                    $skuSpecIds = explode(',', $sku['spec_value_ids']);
                    if (empty(array_diff($skuSpecIds, $spec['spec_value_ids'])) && empty(array_diff($spec['spec_value_ids'], $skuSpecIds))) {
                        $sku['spec_info']['spec_value_ids'] = $skuSpecIds;
                        $sku['spec_info']['spec_values'] = explode(',', $sku['spec_values']);
                        $sku['price'] = bcdiv($sku['price'], 100, 2);
                        $skuRes = $sku;
                    }
                }
            }
            $skuInfo[] = $skuRes;
        }
        return $skuInfo;
    }
}
