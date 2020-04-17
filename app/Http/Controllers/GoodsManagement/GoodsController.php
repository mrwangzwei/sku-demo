<?php


namespace App\Http\Controllers\GoodsManagement;


use App\Http\Controllers\Controller;
use App\Models\GoodsInfo;
use App\Models\GoodsSkuInfo;
use App\Models\GoodsToCategory;
use App\Modules\Goods\Cache\GoodsList;
use App\Modules\Goods\Category;
use App\Modules\Goods\Goods;
use App\Modules\Goods\Sku;
use App\Modules\Goods\Spec;
use App\Utils\RedisHelper;
use App\Utils\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GoodsController extends Controller
{

    /**
     * 新增商品
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $requestLimit = RedisHelper::requestLimitPass("goods-store");
        if(!$requestLimit)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "超速了");
        $param = $request->all();
        $v = Validator::make($param, [
            "goods_name" => 'required|string|max:100',
            "description" => 'nullable|string|max:100',
            "main_img" => 'required|url',
            "pay_type" => [
                'required',
                'integer',
                Rule::in([1, 2, 3])
            ],
            'spec_info' => 'required|array',
            'spec_info.*.id' => 'required|integer',
            'spec_info.*.tid' => 'required|integer',
            'spec_info.*.name' => 'required',
            'spec_info.*.spec_value' => 'required|array',
            'spec_info.*.spec_value.*.id' => 'required|integer',
            'spec_info.*.spec_value.*.tid' => 'required|integer',
            'spec_info.*.spec_value.*.name' => 'required',
            'sku_info' => 'required|array',
            'sku_info.*.id' => "required|integer",
            'sku_info.*.sku_no' => "nullable",
            'sku_info.*.sku_name' => "nullable|string|max:100",
            'sku_info.*.description' => "nullable|string|max:100",
            'sku_info.*.price' => "required|numeric|between:0.01,9999999999",
            'sku_info.*.stock' => "required|integer|between:0,9999999999",
            'sku_info.*.spec_info' => "required|array",
            'sku_info.*.spec_info.spec_value_ids' => "required|array",
            'sku_info.*.spec_info.spec_values' => "required|array",
            'category_ids' => 'nullable|string'
        ]);
        if ($v->fails())
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, $v->errors()->first());
        $saveRes = Goods::saveGoods($param);
        if (!$saveRes)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, Goods::getLastErrorMsg());
        return ResponseHelper::success();
    }

    /**
     * 更新商品
     * @param $gid
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($gid, Request $request)
    {
        $requestLimit = RedisHelper::requestLimitPass("goods-update");
        if(!$requestLimit)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "超速了");
        $param = $request->all();
        $v = Validator::make($param, [
            "goods_name" => 'required|string|max:100',
            "description" => 'nullable|string|max:100',
            "main_img" => 'required|url',
            "pay_type" => [
                'required',
                'integer',
                Rule::in([1, 2, 3])
            ],
            'spec_info' => 'required|array',
            'spec_info.*.id' => 'required|integer',
            'spec_info.*.tid' => 'required|integer',
            'spec_info.*.name' => 'required',
            'spec_info.*.spec_value' => 'required|array',
            'spec_info.*.spec_value.*.id' => 'required|integer',
            'spec_info.*.spec_value.*.tid' => 'required|integer',
            'spec_info.*.spec_value.*.name' => 'required',
            'sku_info' => 'required|array',
            'sku_info.*.id' => "required|integer",
            'sku_info.*.sku_no' => "nullable",
            'sku_info.*.sku_name' => "nullable|string|max:100",
            'sku_info.*.description' => "nullable|string|max:100",
            'sku_info.*.price' => "required|numeric|between:0.01,9999999999",
            'sku_info.*.stock' => "required|integer|between:0,9999999999",
            'sku_info.*.spec_info' => "required|array",
            'sku_info.*.spec_info.spec_value_ids' => "required|array",
            'sku_info.*.spec_info.spec_values' => "required|array",
            'category_ids' => 'nullable|string'
        ]);
        if ($v->fails())
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, $v->errors()->first());
        $exist = GoodsInfo::query()->select('id')->where('id', $gid)->where('deleted_at', 0)->first();
        if (!$exist)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "商品不存在");
        $saveRes = Goods::saveGoods($param, $gid);
        if (!$saveRes)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, Goods::getLastErrorMsg());
        return ResponseHelper::success();
    }

    /**
     * 商品详情
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $goodsInfo = GoodsInfo::query()->where('id', $id)->where('deleted_at', 0)->first();
        if (!$goodsInfo)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "商品不存在");
        !empty($goodsInfo['banner_imgs']) && $goodsInfo['banner_imgs'] = explode(',', $goodsInfo['banner_imgs']);
        !empty($goodsInfo['banner_video']) && $goodsInfo['banner_video'] = explode(',', $goodsInfo['banner_video']);
        $goodsInfo['max_price'] = bcdiv($goodsInfo['max_price'], 100, 2);
        $goodsInfo['min_price'] = bcdiv($goodsInfo['min_price'], 100, 2);
        //获取商品的规格信息
        $goodsInfo['spec_info'] = Spec::getGoodsSpecInfo($id);

        //获取商品的分类信息
        $goodsInfo['category'] = Category::getGoodsCategory($id);

        //获取sku信息
        $goodsInfo['sku_info'] = Sku::getSkuInfo($goodsInfo['spec_info'], $id);

        return ResponseHelper::success([
            'goods_info' => $goodsInfo
        ]);
    }

    /**
     * 根据给定规格构造库存规格信息（接口）
     * @param $gid
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function combineSkuSpec($gid, Request $request)
    {
        $param = $request->all();
        $v = Validator::make($param, [
            'spec_info' => [
                'required',
                'array'
            ],
            'spec_info.*.id' => [
                'required'
            ],
            'spec_info.*.name' => [
                'required',
                'string',
                'max:100'
            ],
            'spec_info.*.spec_value' => [
                'required',
                'array'
            ],
            'spec_info.*.spec_value.*.id' => [
                'required'
            ],
            'spec_info.*.spec_value.*.name' => [
                'required',
                'string',
                'max:100'
            ]
        ]);
        if ($v->fails())
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, $v->errors()->first());
        if ($gid != 0) {
            $goodsInfo = GoodsInfo::query()->select('id')->where('id', $gid)->where('deleted_at', 0)->first();
            if (!$goodsInfo)
                return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "商品不存在");
        }
        $skuInfo = Sku::getSkuInfo($param['spec_info'], $gid);
        return ResponseHelper::success([
            'sku_info' => $skuInfo
        ]);
    }

    /**
     * 删除商品
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $goodsInfo = GoodsInfo::query()->select('id')->where('id', $id)->where('deleted_at', 0)->first();
        if (!$goodsInfo)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "商品不存在");
        $res = Goods::deleteGoods($id);
        if (!$res)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, Goods::getLastErrorMsg());
        return ResponseHelper::success();
    }

    /**
     * 商品上下架
     * @param $gid
     * @return \Illuminate\Http\JsonResponse
     */
    public function switch($gid)
    {
        $requestLimit = RedisHelper::requestLimitPass("goods-switch");
        if(!$requestLimit)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "超速了");
        $goodsInfo = GoodsInfo::query()->where('id', $gid)->where('deleted_at', 0)->first();
        if (!$goodsInfo)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "商品不存在");
        $switch = $goodsInfo->status ^ 1;
        $goodsInfo->status = $switch;
        $goodsInfo->save();
        $categoryIds = [];
        if ($switch == 0)
            $categoryIds = GoodsToCategory::query()->where('deleted_at', 0)->where('gid', $gid)
                ->pluck('cid')->toArray();
        //更新类目商品列表缓存
        GoodsList::batchCategoryGoodsListCache($gid, $switch, $categoryIds);
        return ResponseHelper::success([
            'switch' => $switch
        ]);
    }
}
