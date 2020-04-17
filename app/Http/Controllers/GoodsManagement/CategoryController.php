<?php


namespace App\Http\Controllers\GoodsManagement;


use App\Http\Controllers\Controller;
use App\Models\GoodsCategory;
use App\Models\GoodsSpecTemp;
use App\Models\GoodsSpecTempToCategory;
use App\Modules\Goods\SpecTemp;
use App\Utils\ResponseHelper;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * 类目列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $id = $request->input('id', 0);
        $category = GoodsCategory::query()->select('id', 'name', 'tiny_img', 'pid')->with([
            "hasChild" => function ($query) {
                $query->select('id', 'name', 'tiny_img', 'pid');
            }
        ])
            ->where('pid', $id)
            ->get()->toArray();
        return ResponseHelper::success([
            'category' => $category
        ]);
    }

    /**
     * 获取类目下的规格模板
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function categorySpecTemp($id)
    {
        $tempIds = GoodsSpecTempToCategory::query()->where('cid', $id)->pluck('tid')->toArray();
        $categorySpec = [];
        if (!empty($tempIds)) {
            $categorySpec = GoodsSpecTemp::query()->select('id', 'spec_name', 'pid')
                ->with(['specValues' => function ($query) {
                    $query->select('id', 'spec_value', 'pid');
                }])->whereIn('id', $tempIds)->get()->toArray();
        }
        return ResponseHelper::success([
            'category_spec' => $categorySpec
        ]);
    }

    /**
     * 保存类目和规格模板的关系
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function linkCategorySpecTemp(Request $request)
    {
        $tids = $request->input('tids', "");
        $cid = $request->input('cid', 0);
        if (empty($tids) || empty($cid)) {
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, '参数有误');
        }
        $category = GoodsCategory::query()->where('id', $cid)->first();
        if (!$category)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, '类目不存在');
        if ($category->pid == 0)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, '不能使用一级类目绑定模板');
        $tids = SpecTemp::filterPids($tids);
        $oldIds = GoodsSpecTempToCategory::query()->where('cid', $cid)->pluck('tid')->toArray();
        $delIds = array_diff($oldIds, $tids);
        $newIds = array_diff($tids, $oldIds);
        if (!empty($delIds)) {
            GoodsSpecTempToCategory::query()->whereIn('tid', $delIds)->delete();
        }
        if (!empty($newIds)) {
            foreach ($newIds as $newId) {
                GoodsSpecTempToCategory::query()->insert([
                    'cid' => $cid,
                    'tid' => $newId,
                    'created_at' => time()
                ]);
            }
        }
        return ResponseHelper::success();
    }
}
