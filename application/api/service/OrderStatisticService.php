<?php


namespace app\api\service;


use app\api\model\DinnerT;
use app\api\model\MaterialPriceV;
use app\api\model\OrderMaterialUpdateT;
use app\api\model\OrderMaterialUpdateV;
use app\api\model\OrderMaterialV;
use app\api\model\OrderSettlementV;
use app\api\model\OrderStatisticV;
use app\api\model\OrderT;
use app\api\model\OrderTakeoutStatisticV;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;

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
        $company_id = 2;//Token::getCurrentTokenVar('company_id');
        $statistic = OrderMaterialV::orderMaterialsStatistic($page, $size, $time_begin, $time_end, $canteen_id, $company_id);
        //获取该企业/饭堂下所有材料价格
        $materials = MaterialPriceV::materialsForOrder($canteen_id, $company_id);
        //获取指定修改记录
        $updateRecords = OrderMaterialUpdateV::orderRecords($time_begin, $time_end, $canteen_id, $company_id);
        $statistic['data'] = $this->prefixMaterials($statistic['data'], $materials, $updateRecords);
        return $statistic;
    }

    private function prefixMaterials($data, $materials, $updateRecords)
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

    public function updateOrderMaterial($title, $detail_id, $material, $count, $price)
    {
        $this->checkMaterialCanUpdate($detail_id, $material);
        $update = OrderMaterialUpdateT::create([
            'title' => $title,
            'detail_id' => $detail_id,
            'material' => $material,
            'count' => $count,
            'price' => $price,
            'state' => CommonEnum::STATE_IS_OK,
            'admin_id' => Token::getCurrentUid()
        ]);
        if (!$update) {
            throw new SaveException();
        }

    }

    private function checkMaterialCanUpdate($detail_id, $material)
    {
        $count = OrderMaterialUpdateT::where('detail_id', $detail_id)
            ->where('material', $material)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count();
        if ($count) {
            throw new SaveException(['msg' => '已经修改，不能重复修改']);
        }


    }
}