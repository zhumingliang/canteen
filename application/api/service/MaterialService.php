<?php


namespace app\api\service;


use app\api\model\DinnerT;
use app\api\model\FoodMaterialT;
use app\api\model\MaterialOrderT;
use app\api\model\MaterialPriceT;
use app\api\model\MaterialPriceV;
use app\api\model\MaterialReportDetailT;
use app\api\model\MaterialReportT;
use app\api\model\OrderParentT;
use app\api\model\OrderT;
use app\api\validate\Order;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;
use function GuzzleHttp\Promise\each_limit;

class MaterialService extends BaseService
{
    private $orderOnline = "online";
    private $orderChoice = "choice";

    public function save($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $material = MaterialPriceT::create($params);
        if (!$material) {
            throw  new SaveException();
        }
    }

    public function uploadMaterials($canteen_id, $materials_excel)
    {
        $date = (new ExcelService())->saveExcel($materials_excel);
        $this->prefixMaterials($canteen_id, $date);
    }


    public function prefixMaterials($canteen_id, $data)
    {

        $materials = [];
        foreach ($data as $k => $v) {
            if ($k == 1) {
                continue;
            }
            $materials[] = [
                'name' => $v[0],
                'price' => $v[1],
                'unit' => $v[2],
                'state' => CommonEnum::STATE_IS_OK,
                'admin_id' => Token::getCurrentUid(),
                'c_id' => $canteen_id,
            ];
        }
        if (empty($materials)) {
            throw new SaveException(['msg' => '上传文件为空']);
        }
        $res = (new MaterialPriceT())->saveAll($materials);
        if (!$res) {
            throw  new SaveException();
        }
    }


    public function materials($page, $size, $key, $params)
    {
        $selectField = $this->prefixSelectFiled($params);
        $materials = MaterialPriceV::materials($page, $size, $key, $selectField['field'], $selectField['value']);
        return $materials;

    }

    public function exportMaterials($key, $params)
    {
        $selectField = $this->prefixSelectFiled($params);
        $materials = MaterialPriceV::exportMaterials($key, $selectField['field'], $selectField['value']);
        $header = ['序号', '企业名称', '饭堂名称', '材料名称', '单位', '金额-元'];
        $url = (new ExcelService())->makeExcel($header, $materials, "材料价格明细");
        return config('setting.domain') . $url;
    }

    public function saveFoodMaterial($params)
    {

        //检测是否重复添加
        $check = FoodMaterialT::checkFoodMaterialExits($params['f_id'], $params['name']);
        if ($check) {
            throw new ParameterException(['msg' => "菜品材料已存在，不能重复添加"]);
        }
        $params['state'] = CommonEnum::STATE_IS_OK;
        $material = FoodMaterialT::create($params);
        if (!$material) {
            throw new SaveException();
        }

    }

    public function updateFoodMaterial($params)
    {
        $material = FoodMaterialT::update($params);
        if (!$material) {
            throw new UpdateException();
        }

    }

    public function saveOrderMaterial($params)
    {
        //检测饭堂就餐类别：预定餐/个人选菜
        $companyId = $params['company_id'];
        $canteenId = $params['canteen_id'];
        $day = date('Y-m-d');
        $material = $params['material'];
        $type = $this->checkCanteenOrderType($canteenId);
        if (MaterialOrderT::checkExits($canteenId, $day, $material)) {
            throw new ParameterException(['msg' => "材料已经新增，不能重复新增"]);
        }
        if ($type == $this->orderOnline) {
            //检测材料是否已经新增
            $data = [
                'company_id' => $companyId,
                'canteen_id' => $canteenId,
                'material' => $material,
                'day' => $day,
                'count' => $params['count'],
                'price' => $params['price'],
                'status' => CommonEnum::STATE_IS_OK,
                'type' => $type
            ];
            if (!MaterialOrderT::create($data)) {
                throw new SaveException();
            }

        } else if ($type == $this->orderChoice) {
            //获取所有餐次
            $dinners = DinnerT::dinners($canteenId);
            if (!$dinners) {
                throw new ParameterException(['msg' => "餐次设置异常"]);
            }
            $dataList = [];
            foreach ($dinners as $k => $v) {
                array_push($dataList, [
                    'company_id' => $companyId,
                    'canteen_id' => $canteenId,
                    'dinner_id' => $v['id'],
                    'material' => $material,
                    'day' => $day,
                    'count' => $params['count'],
                    'price' => $params['price'],
                    'status' => CommonEnum::STATE_IS_FAIL,
                    'type' => $type
                ]);

            }
            if (!(new MaterialOrderT())->saveAll($dataList)) {
                throw new SaveException();
            }

        } else {
            throw new ParameterException(['msg' => "订餐类型异常"]);
        }
    }

    private function checkCanteenOrderType($canteenId)
    {
        $order = OrderT::where('c_id', $canteenId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('booking', CommonEnum::STATE_IS_OK)
            ->order('create_time desc')
            ->find();
        if ($order) {
            return $order->ordering_type == "online" ? "online" : "choice";
        }
        $order = OrderParentT::where('canteen_id', $canteenId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('booking', CommonEnum::STATE_IS_OK)
            ->order('create_time desc')
            ->find();
        if (!$order) {
            throw new SaveException(['msg' => "订餐方式未知"]);
        }
        return $order->ordering_type == "online" ? "online" : "choice";
    }

    public function updateOrderMaterial($params)
    {
        $info = MaterialOrderT::get($params['id']);
        if (!$info) {
            throw new ParameterException(['msg' => "材料不存在"]);
        }
        unset($params['id']);
        if (!MaterialOrderT::where('canteen_id', $info->canteen_id)
            ->where('day', $info->day)
            ->where('material', $info->material)
            ->update($params)) {
            throw new UpdateException();
        }

    }

    public function deleteOrderMaterial($id)
    {
        $info = MaterialOrderT::get($id);
        if (!$info) {
            throw new ParameterException(['msg' => "材料不存在"]);
        }
        if (!MaterialOrderT::where('canteen_id', $info->canteen_id)
            ->where('day', $info->day)
            ->where('material', $info->material)
            ->update([
                'state' => CommonEnum::STATE_IS_FAIL
            ])) {
            throw new UpdateException();
        }
    }

    public function orderMaterials($timeBegin, $timeEnd, $companyId, $canteenId, $page, $size)
    {
        if (!$canteenId && !$companyId) {
            throw new ParameterException(['msg' => "未选择企业和饭堂"]);
        }
        $data = MaterialOrderT::orderMaterials($timeBegin, $timeEnd, $companyId, $canteenId, $page, $size);
        return $data;

    }

    public function orderMaterialReport($title, $ids)
    {
        Db::startTrans();
        try {
            $materials = MaterialOrderT::materials($ids);
            if (!$materials) {
                throw new ParameterException();
            }
            $companyId = 0;
            $canteenId = 0;
            //生成报表
            $report = MaterialReportT::create([
                'title' => $title,
                'state' => CommonEnum::STATE_IS_OK,
                'admin_id' => 1// Token::getCurrentUid()
            ]);
            if (!$report) {
                throw new SaveException();
            }
            //检测是否有已经生成报表参数
            $data = [];
            foreach ($materials as $k => $v) {
                if (!$companyId || !$canteenId) {
                    $companyId = $v['company_id'];
                    $canteenId = $v['canteen_id'];
                }
                if ($v['report'] == CommonEnum::STATE_IS_OK) {
                    throw new ParameterException([
                        'msg' => "材料：" . $v['material'] . '已经生成报表'
                    ]);
                }
                array_push($data, [
                    'report_id' => $report->id,
                    'material' => $v['material'],
                    'company_id' => $companyId,
                    'canteen_id' => $canteenId,
                    'dinner_id' => $v['dinner_id'],
                    'count' => $v['count'],
                    'price' => $v['price'],
                    'order_count' => $v['order_count'],
                    'ordering_date' => $v['create_time']

                ]);
            }

            $detail = (new MaterialReportDetailT())->saveAll($data);
            if (!$detail) {
                throw new SaveException();
            }
            $report->company_id = $companyId;
            $report->canteen_id = $canteenId;
            if (!$report->save()) {
                throw new UpdateException();
            }

            $update = MaterialOrderT::where('id', 'in', $ids)
                ->update([
                    'report_id' => $report->id,
                    'report' => CommonEnum::STATE_IS_OK
                ]);
            if (!$update) {
                throw new UpdateException();
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    public function orderMaterialReportCancel($id)
    {
        Db::startTrans();
        try {
            if (!MaterialReportT::update([
                'id' => $id,
                'state' => CommonEnum::STATE_IS_FAIL
            ])) {
                throw new UpdateException();
            }
            //恢复材料明细状态
            if (!MaterialOrderT::where('report_id', $id)->update([
                'report' => CommonEnum::STATE_IS_FAIL,
                'report_id' => 0
            ])) {
                throw new UpdateException();
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function orderMaterialReports($timeBegin, $timeEnd, $companyId, $canteenId, $page, $size)
    {
        if (!$canteenId && !$companyId) {
            throw new ParameterException(['msg' => "未选择企业和饭堂"]);
        }
        return MaterialReportT::reports($timeBegin, $timeEnd, $companyId, $canteenId, $page, $size);
    }

    public function orderMaterialReportDetail($id)
    {
        $report = MaterialReportT::get($id);
        $info = MaterialReportDetailT::info($id);
        return [
            'title' => $report->title,
            'detail' => $info
        ];
    }


}