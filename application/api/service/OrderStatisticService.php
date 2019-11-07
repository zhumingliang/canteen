<?php


namespace app\api\service;


use app\api\model\DinnerT;
use app\api\model\MaterialPriceV;
use app\api\model\MaterialReportDetailT;
use app\api\model\MaterialReportDetailV;
use app\api\model\MaterialReportT;
use app\api\model\OrderMaterialV;
use app\api\model\OrderSettlementV;
use app\api\model\OrderStatisticV;
use app\api\model\OrderT;
use app\api\model\OrderTakeoutStatisticV;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;

class OrderStatisticService
{
    public function statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size)
    {
        $list = OrderStatisticV::statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size);
        return $list;

    }

    public function orderStatisticDetail($company_ids, $time_begin,
                                         $time_end, $page, $size, $name,
                                         $phone, $canteen_id, $department_id,
                                         $dinner_id)
    {
        $list = OrderStatisticV::detail($company_ids, $time_begin,
            $time_end, $page, $size, $name,
            $phone, $canteen_id, $department_id,
            $dinner_id);
        return $list;
    }

    public function orderSettlement($page, $size,
                                    $name, $phone, $canteen_id, $department_id, $dinner_id,
                                    $consumption_type, $time_begin, $time_end, $company_ids)
    {
        $records = OrderSettlementV::orderSettlement($page, $size,
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids);
        $records['data'] = $this->prefixSettlementConsumptionType($records['data']);
        return $records;
    }

    private function prefixSettlementConsumptionType($data)
    {
        if (count($data)) {
            foreach ($data as $k => $v) {
                if ($v['type'] == 'recharge') {
                    $data[$k]['consumption_type'] = 4;
                    continue;
                }
                if ($v['booking'] == CommonEnum::STATE_IS_OK) {
                    $data[$k]['consumption_type'] = $v['used'] == CommonEnum::STATE_IS_OK ? 1 : 2;
                } else {
                    $data[$k]['consumption_type'] = 3;
                }
            }

        }
        return $data;
    }

    public function takeoutStatistic($page, $size,
                                     $ordering_date, $company_ids,
                                     $canteen_id, $dinner_id, $used)
    {
        $records = OrderTakeoutStatisticV::statistic($page, $size,
            $ordering_date, $company_ids, $canteen_id, $dinner_id, $used);
        return $records;
    }

    public function infoToPrint($id)
    {
        $info = OrderT::infoToPrint($id);
        if ($info->type != 2) {
            throw new ParameterException(['msg' => '该订单不为外卖订单']);
        }
        $dinner = DinnerT::get($info->d_id);
        $info['hidden'] = $dinner->fixed;
        return $info;
    }


    public function orderMaterialsStatistic($page, $size, $time_begin, $time_end, $canteen_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $statistic = OrderMaterialV::orderMaterialsStatistic($page, $size, $time_begin, $time_end, $canteen_id, $company_id);
        //获取该企业/饭堂下所有材料价格
        $materials = MaterialPriceV::materialsForOrder($canteen_id, $company_id);
        //获取指定修改记录
        $updateRecords = MaterialReportDetailV::orderRecords($time_begin, $time_end, $canteen_id, $company_id);
        $statistic['data'] = $this->prefixMaterials($statistic['data'], $materials, $updateRecords);
        return $statistic;
    }

    private function prefixMaterials($data, $materials, $updateRecords = array())
    {
        if (count($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['material_price'] = 0;
                $data[$k]['material_count'] = $v['order_count'];
                if (count($updateRecords)) {
                    foreach ($updateRecords as $k3 => $v3) {
                        if ($v['detail_id'] == $v3['detail_id'] && $v['material'] == $v3['material']) {
                            $data[$k]['material_price'] = $v3['price'];
                            $data[$k]['material_count'] = $v3['count'];
                            unset($updateRecords[$k3]);
                            break;
                        }

                    }

                }
                if (count($materials)) {
                    foreach ($materials as $k2 => $v2) {
                        if ($v['material'] == $v2['name']) {
                            $data[$k]['material_price'] = $v2['price'];
                        }

                    }
                }
            }
        }
        return $data;
    }

    private function prefixMaterialsForReport($data, $materials, $updateRecords)
    {
        if (count($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['material_price'] = 0;
                $data[$k]['material_count'] = $v['order_count'];
                $data[$k]['update'] = CommonEnum::STATE_IS_OK;
                $update = CommonEnum::STATE_IS_FAIL;
                if (count($updateRecords)) {
                    foreach ($updateRecords as $k3 => $v3) {
                        if ($v['detail_id'] == $v3['detail_id'] && $v['material'] == $v3['material']) {
                            $data[$k]['material_price'] = $v3['price'];
                            $data[$k]['material_count'] = $v3['count'];
                            unset($updateRecords[$k3]);
                            $data[$k]['update'] = CommonEnum::STATE_IS_FAIL;
                            $update = CommonEnum::STATE_IS_OK;
                            break;
                        }

                    }

                }
                if ($update == CommonEnum::STATE_IS_FAIL && count($materials)) {
                    foreach ($materials as $k2 => $v2) {
                        if ($v['material'] == $v2['name']) {
                            $data[$k]['material_price'] = $v2['price'];
                        }

                    }
                }
            }
        }
        return $data;
    }

    public function updateOrderMaterial($params)
    {
        try {
            Db::startTrans();
            $this->checkMaterialCanUpdate($params['canteen_id'], $params['time_begin'], $params['time_end']);
            $report = MaterialReportT::create([
                'company_id' => Token::getCurrentTokenVar('company_id'),
                'canteen_id' => $params['canteen_id'],
                'title' => $params['title'],
                'time_begin' => $params['time_begin'],
                'time_end' => $params['time_end'],
                'admin_id' => Token::getCurrentUid(),
            ]);
            if (!$report) {
                throw new SaveException(['msg' => '新增报表失败']);
            }
            $materials = $params['materials'];
            $materials = json_decode($materials, true);
            if (empty($materials)) {
                throw new ParameterException(['msg' => '参数格式错误']);
            }
            $dataList = [];
            foreach ($materials as $k => $v) {
                array_push($dataList, [
                    'report_id' => $report->id,
                    'material' => $v['material'],
                    'count' => $v['count'],
                    'price' => $v['price'],
                    'detail_id' => $v['detail_id'],
                    'ordering_date' => $v['ordering_date'],
                    'state' => CommonEnum::STATE_IS_OK
                ]);

            }
            $detail = (new MaterialReportDetailT())->saveAll($dataList);
            if (!$detail) {
                throw new SaveException(['msg' => '保存报表详情失败']);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;
        }
    }

    private function checkMaterialCanUpdate($canteen_id, $time_begin, $time_end)
    {
        $time_begin = 'date_format("' . $time_begin . '","%Y-%m-%d")';
        $time_end = 'date_format("' . $time_end . '","%Y-%m-%d")';
        $sql = '(time_begin > ' . $time_begin . ' and time_begin < ' . $time_end .
            ') or ( time_end > ' . $time_begin . ' and ' . 'time_end < ' . $time_end . ')' .
            ' or (time_begin < ' . $time_begin . ' and time_end > ' . '$time_end )';
        $count = MaterialReportT::where('canteen_id', $canteen_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->whereRaw($sql)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count();
        if ($count) {
            throw new SaveException(['msg' => '已经修改，不能重复修改']);
        }

    }

    public function materialReports($page, $size, $time_begin, $time_end, $canteen_id)
    {
        $list = MaterialReportT::reports($page, $size, $time_begin, $time_end, $canteen_id);
        return $list;
    }
}