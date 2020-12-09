<?php


namespace app\api\service;


use app\api\model\AccountRecordsV;
use app\api\model\CanteenT;
use app\api\model\CompanyAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\DinnerT;
use app\api\model\MaterialPriceV;
use app\api\model\MaterialReportDetailT;
use app\api\model\MaterialReportDetailV;
use app\api\model\MaterialReportT;
use app\api\model\OrderConsumptionAccountV;
use app\api\model\OrderConsumptionV;
use app\api\model\OrderDetailT;
use app\api\model\OrderMaterialV;
use app\api\model\OrderParentT;
use app\api\model\OrderSettlementV;
use app\api\model\OrderStatisticV;
use app\api\model\OrderSubT;
use app\api\model\OrderT;
use app\api\model\OrderTakeoutStatisticV;
use app\api\model\ShopT;
use app\api\model\StaffCanteenV;
use app\api\model\SubFoodT;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\Num;
use think\Db;
use think\Exception;
use think\Model;
use think\Request;
use function Composer\Autoload\includeFile;

class OrderStatisticService
{


    public function statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size)
    {
        $list = OrderStatisticV::statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size);
        return $list;

    }

    public function exportStatistic($time_begin, $time_end, $company_ids, $canteen_id)
    {
        $list = OrderStatisticV::exportStatistic($time_begin, $time_end, $company_ids, $canteen_id);
        $header = ['日期', '公司', '消费地点', '餐次', '订餐份数'];
        $file_name = "订餐统计报表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $list, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    public function orderStatisticDetail($company_ids, $time_begin,
                                         $time_end, $page, $size, $name,
                                         $phone, $canteen_id, $department_id,
                                         $dinner_id, $type)
    {
        $list = OrderStatisticV::detail($company_ids, $time_begin,
            $time_end, $page, $size, $name,
            $phone, $canteen_id, $department_id,
            $dinner_id, $type);
        $list['data'] = $this->getTypeAttr($list['data']);
        return $list;
    }

    public function getTypeAttr($data, $value = '')
    {
        $status = [1 => '堂食', 2 => '外卖'];
        if ($value) {
            return $status[$value];
        }
        foreach ($data as $k => $v) {
            $data[$k]['type'] = $status[$v['type']];
            $data[$k]['status'] = $this->getStatus($v['ordering_date'], $v['state'], $v['meal_time_end'], $v['used']);
        }
        return $data;
    }

    public function exportOrderStatisticDetail($company_ids, $time_begin,
                                               $time_end, $name,
                                               $phone, $canteen_id, $department_id,
                                               $dinner_id, $type)
    {
        $list = OrderStatisticV::exportDetail($company_ids, $time_begin,
            $time_end, $name,
            $phone, $canteen_id, $department_id,
            $dinner_id, $type);
        $list = $this->prefixOrderStatisticDetail($list);
        $header = ['订单ID', '订餐日期', '消费地点', '部门', '姓名', '号码', '餐次', '订餐类型', '份数', '金额', '订餐状态', '明细', '合计'];
        $file_name = "订餐明细报表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $list, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    private function prefixOrderStatisticDetail($list)
    {
        $dataList = [];
        foreach ($list as $k => $v) {
            $consumptionType = $v['consumption_type'];
            $foods = $this->getOrderFoods($v['order_id'], $v['ordering_type'], $v['consumption_type']);
            $detail = [];
            if (count($foods)) {
                foreach ($foods as $k3 => $v3) {
                    array_push($detail, $v3['name'] . '*' . $v3['count']);
                }
            }
            $detail = implode('  ', $detail);
            if ($consumptionType == "one") {
                $data['order_id'] = $v['order_id'];
                $data['ordering_date'] = $v['ordering_date'];
                $data['canteen'] = $v['canteen'];
                $data['department'] = $v['department'];
                $data['username'] = $v['username'];
                $data['phone'] = $v['phone'];
                $data['dinner'] = $v['dinner'];
                $data['type'] = $this->getTypeAttr([], $v['type']);
                $data['count'] = Num::numToWord($v['count']) . "份";
                $data['money'] = ($v['order_money'] - $v['delivery_fee']) / $v['count'];
                $data['status'] = $this->getStatus($v['ordering_date'], $v['state'], $v['meal_time_end'], $v['used']);
                $data['foods'] = $detail;
                $data['all'] = $v['order_money'];
                array_push($dataList, $data);

            } else if ($consumptionType == "more") {
                if ($v['state'] == CommonEnum::STATE_IS_OK) {
                    $subOrder = OrderSubT::where('order_id', $v['order_id'])
                        ->where('state', CommonEnum::STATE_IS_OK)->select();
                } else {
                    $subOrder = OrderSubT::where('order_id', $v['order_id'])->select();
                }

                foreach ($subOrder as $k2 => $v2) {
                    $data['order_id'] = $v2['id'];
                    $data['ordering_date'] = $v['ordering_date'];
                    $data['canteen'] = $v['canteen'];
                    $data['department'] = $v['department'];
                    $data['username'] = $v['username'];
                    $data['phone'] = $v['phone'];
                    $data['dinner'] = $v['dinner'];
                    $data['type'] = $v['type'];
                    $data['count'] = "第" . Num::numToWord($v2['consumption_sort']) . "份";
                    $data['money'] = $v2['money'] + $v2['sub_money'];
                    $data['status'] = $this->getStatus($v['ordering_date'], $v2['state'], $v['meal_time_end'], $v2['used']);
                    $data['foods'] = $detail;
                    if ($k2 == count($subOrder) - 1) {
                        $data['all'] = $v['order_money'];
                    } else {
                        $data['all'] = '';
                    }
                    array_push($dataList, $data);
                }


            }
        }
        return $dataList;
    }


    private function getOrderFoods($orderId, $orderingType, $consumptionType)
    {
        if ($orderingType != "personal_choice") {
            return [];
        }
        if ($consumptionType == 'one') {
            return OrderDetailT::where('o_id', $orderId)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->select();
        } else if ($consumptionType == 'more') {
            return SubFoodT::where('o_id', $orderId)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->select();

        }
        return [];

    }

    private function getStatus($ordering_date, $state, $meal_time_end, $used)
    {
        if ($state != CommonEnum::STATE_IS_OK) {
            return "已取消";
        } else {
            $expiryDate = $ordering_date . ' ' . $meal_time_end;
            if (time() > strtotime($expiryDate)) {
                return "已结算";
            } else {
                if ($used == CommonEnum::STATE_IS_FAIL) {
                    return "已订餐";
                } else {
                    return "已结算";
                }
            }
        }
    }

    public function orderSettlement($page, $size,
                                    $name, $phone, $canteen_id, $department_id, $dinner_id,
                                    $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $records = OrderSettlementV::orderSettlement($page, $size,
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);

        $records['data'] = $this->prefixSettlementConsumptionType($records['data']);
        return $records;
    }

    public function orderSettlementWithAccount($page, $size,
                                               $name, $phone, $canteen_id, $department_id, $dinner_id,
                                               $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $records = OrderSettlementV::orderSettlementWithAccount($page, $size,
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        $records['data'] = $this->prefixSettlementConsumptionType($records['data']);

        return $records;
    }

    public function exportOrderSettlement(
        $name, $phone, $canteen_id, $department_id, $dinner_id,
        $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $records = OrderSettlementV::exportOrderSettlement(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        $records = $this->prefixExportOrderSettlement($records);
        $header = ['序号', '消费日期', '消费时间', '部门', '姓名', '手机号', '消费地点', '消费类型', '餐次', '金额', '备注'];
        $file_name = "消费明细报表（" . $time_begin . "-" . $time_end . "）";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    public function exportOrderSettlementWithAccount(
        $name, $phone, $canteen_id, $department_id, $dinner_id,
        $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $records = OrderSettlementV::exportOrderSettlementWithAccount(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        $records = $this->prefixExportOrderSettlementWithAccount($records);
        $header = ['序号', '消费日期', '消费时间', '部门', '姓名', '手机号', '消费地点', '账户名称', '消费类型', '餐次', '金额', '备注'];
        $file_name = "消费明细报表（" . $time_begin . "-" . $time_end . "）";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private function prefixExportOrderSettlementWithAccount($data)
    {
        ($data);
        $dataList = [];
        if (count($data)) {
            foreach ($data as $k => $v) {
                if ($v['type'] == 'recharge') {
                    $data[$k]['consumption_type'] = "系统补充";
                } else if ($v['type'] == 'deduction') {
                    $data[$k]['consumption_type'] = "系统补扣";
                } else if ($v['type'] == 'shop') {
                    if ($v['money'] > 0) {
                        $data[$k]['consumption_type'] = "小卖部消费";
                    } else {
                        $data[$k]['consumption_type'] = "小卖部退款";
                    }
                    $data[$k]['money'] = sprintf("%.2f", abs($v['money']));

                } else if ($v['type'] == 'canteen') {
                    if ($v['booking'] == CommonEnum::STATE_IS_OK) {
                        $data[$k]['consumption_type'] = $v['used'] == CommonEnum::STATE_IS_OK ? "订餐就餐" : "订餐未就餐";
                    } else {
                        $data[$k]['consumption_type'] = "未订餐就餐";
                    }
                }
                array_push($dataList, [
                    'number' => $k + 1,
                    'ordering_date' => $v['ordering_date'],
                    'used_time' => $v['used_time'],
                    'department' => $v['department'],
                    'username' => $v['username'],
                    'phone' => $v['phone'],
                    'canteen' => $v['canteen'],
                    'account' => $v['account'],
                    'consumption_type' => $data[$k]['consumption_type'],
                    'dinner' => $v['dinner'],
                    'money' => sprintf("%.2f", abs($v['money'])),
                    'remark' => $v['remark']
                ]);
            }
        }
        return $dataList;
    }

    private function prefixExportOrderSettlement($data)
    {
        ($data);
        $dataList = [];
        if (count($data)) {
            foreach ($data as $k => $v) {
                if ($v['type'] == 'recharge') {
                    $data[$k]['consumption_type'] = "系统补充";
                } else if ($v['type'] == 'deduction') {
                    $data[$k]['consumption_type'] = "系统补扣";
                } else if ($v['type'] == 'shop') {
                    if ($v['money'] > 0) {
                        $data[$k]['consumption_type'] = "小卖部消费";
                    } else {
                        $data[$k]['consumption_type'] = "小卖部退款";
                    }
                    $data[$k]['money'] = sprintf("%.2f", abs($v['money']));

                } else if ($v['type'] == 'canteen') {
                    if ($v['booking'] == CommonEnum::STATE_IS_OK) {
                        $data[$k]['consumption_type'] = $v['used'] == CommonEnum::STATE_IS_OK ? "订餐就餐" : "订餐未就餐";
                    } else {
                        $data[$k]['consumption_type'] = "未订餐就餐";
                    }
                }
                array_push($dataList, [
                    'number' => $k + 1,
                    'consumptionDate' => $v['ordering_date'],
                    'used_time' => $v['used_time'],
                    'department' => $v['department'],
                    'username' => $v['username'],
                    'phone' => $v['phone'],
                    'canteen' => $v['canteen'],
                    'consumption_type' => $data[$k]['consumption_type'],
                    'dinner' => $v['dinner'],
                    'money' => sprintf("%.2f", abs($v['money'])),
                    'remark' => $v['remark']
                ]);
            }
        }
        return $dataList;
    }

    private
    function prefixSettlementConsumptionType($data)
    {
        if (count($data)) {
            foreach ($data as $k => $v) {

                if ($v['type'] == 'recharge') {
                    $data[$k]['consumption_type'] = "系统补充";
                } else if ($v['type'] == 'deduction') {
                    $data[$k]['consumption_type'] = "系统补扣";
                } else if ($v['type'] == 'shop') {
                    if ($v['money'] > 0) {
                        $data[$k]['consumption_type'] = "小卖部消费";
                    } else {
                        $data[$k]['consumption_type'] = "小卖部退款";
                    }
                    $data[$k]['money'] = sprintf("%.2f", abs($v['money']));

                } else if ($v['type'] == 'canteen') {
                    if ($v['booking'] == CommonEnum::STATE_IS_OK) {
                        $data[$k]['consumption_type'] = $v['used'] == CommonEnum::STATE_IS_OK ? "订餐就餐" : "订餐未就餐";
                    } else {
                        $data[$k]['consumption_type'] = "未订餐就餐";
                    }
                }

            }
        }
        return $data;
    }

    public
    function takeoutStatistic($page, $size,
                              $ordering_date, $company_ids,
                              $canteen_id, $dinner_id, $status, $department_id, $user_type)
    {
        $records = OrderTakeoutStatisticV::statistic($page, $size,
            $ordering_date, $company_ids, $canteen_id, $dinner_id, $status, $department_id, $user_type);
        return $records;
    }

    public
    function takeoutStatisticForOfficial($page, $size,
                                         $ordering_date, $dinner_id, $status, $department_id)
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $records = OrderTakeoutStatisticV::officialStatistic($page, $size,
            $ordering_date, $dinner_id, $status, $department_id, $canteen_id);
        return $records;
    }


    public
    function exportTakeoutStatistic($ordering_date, $company_ids,
                                    $canteen_id, $dinner_id, $status, $department_id, $user_type)
    {
        $records = OrderTakeoutStatisticV::exportStatistic($ordering_date, $company_ids, $canteen_id, $dinner_id, $status, $department_id, $user_type);
        $records = $this->prefixExportTakeoutStatistic($records);
        $header = ['订餐号', '日期', '消费地点', '姓名', '手机号', '餐次', '金额（元）', '送货地点', '状态'];
        $file_name = $ordering_date . "-外卖管理报表";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private
    function prefixExportTakeoutStatistic($records)
    {
        $statusText = [
            1 => '已支付',
            2 => '已取消',
            3 => '已接单',
            4 => '已完成',
            5 => '已退款',
        ];
        if (count($records)) {
            foreach ($records as $k => $v) {
                unset($records[$k]['state']);
                unset($records[$k]['used']);
                unset($records[$k]['receive']);
                $records[$k]['status'] = $statusText[$v['status']];
            }
        }
        return $records;

    }

    public
    function infoToPrint($id, $consumptionType = 'one')
    {
        if ($consumptionType == "one") {
            $info = OrderT::infoToPrint($id);
            $dinner_id = $info['d_id'];
        } else {
            $info = OrderParentT::infoToPrint($id);
            $dinner_id = $info['dinner_id'];
        }
        if (!$info) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        if ($info['type'] != OrderEnum::EAT_OUTSIDER) {
            throw new ParameterException(['msg' => '该订单不为外卖订单']);
        }
        if ($consumptionType == "one") {
            $info = $this->prefixSubInfoToPrint($info);
        }
        $dinner = DinnerT::get($dinner_id);
        $info['dinner'] = $dinner->name;
        $info['hidden'] = $dinner->fixed;
        return $info;
    }

    private
    function prefixSubInfoToPrint($order)
    {
        $count = $order['count'];
        $money = $order['money'] / $count;
        $sub_money = $order['sub_money'] / $count;
        $subList = [];
        for ($i = 0; $i < $count; $i++) {
            array_push($subList, ['order_sort' => $i + 1, 'money' => $money, 'sub_money' => $sub_money]);
        }
        $order['sub'] = $subList;
        return $order;

    }

    public
    function orderMaterialsStatistic($page, $size, $time_begin, $time_end, $canteen_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $statistic = OrderMaterialV::orderMaterialsStatistic($page, $size, $time_begin, $time_end, $canteen_id, $company_id);
        //获取该企业/饭堂下所有材料价格
        $materials = MaterialPriceV::materialsForOrder($canteen_id, $company_id);
        $statistic['data'] = $this->prefixMaterials($statistic['data'], $materials);
        return [
            'list' => $statistic,
            'money' => $this->getMaterialMoney($time_begin, $time_end, $canteen_id, 0, $materials)
        ];
    }

    private
    function prefixMaterials($data, $materials, $statisticMoney = false)
    {
        $money = 0;
        if (count($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['number'] = $k + 1;
                $data[$k]['material_count'] = $v['order_count'];
                $data[$k]['material_price'] = 0;
                if (count($materials)) {
                    foreach ($materials as $k2 => $v2) {
                        if ($v['material'] == $v2['name']) {
                            $data[$k]['material_price'] = $v2['price'];
                        }
                    }
                }
                $material_money = $data[$k]['material_price'] * $data[$k]['material_count'];
                $data[$k]['money'] = $material_money;
                $money += $material_money;
            }
        }


        if ($statisticMoney) {
            array_push($data, [
                'number' => '合计',
                'ordering_date' => '',
                'dinner' => '',
                'material' => '',
                'order_count' => '',
                'material_count' => '',
                'material_price' => '',
                'money' => $money
            ]);
        }
        return $data;
    }

    public
    function updateOrderMaterial($params)
    {
        try {
            Db::startTrans();
            $this->checkMaterialCanUpdate($params['canteen_id'], $params['time_begin'], $params['time_end']);
            $company_id = Token::getCurrentTokenVar('company_id');
            $report = MaterialReportT::create([
                'company_id' => $company_id,
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

            //获取报表所有需要数据
            $detail = $this->orderMaterials($params['time_begin'], $params['time_end'], $params['canteen_id'], $company_id);
            $money = 0;
            $dataList = [];
            foreach ($detail as $k => $v) {
                $order_count = $v['order_count'];
                $order_price = $v['material_price'];
                $update_count = $v['order_count'];
                $update_price = $v['material_price'];
                if (count($materials)) {
                    foreach ($materials as $k2 => $v2) {
                        if ($v['dinner_id'] == $v2['dinner_id'] && $v['material'] == $v2['material']
                            && $v['ordering_date'] == $v2['ordering_date']) {
                            if (!empty($v2['material_count'])) {
                                $update_count = $v2['material_count'];
                            }
                            if (!empty($v2['material_price'])) {
                                $update_price = $v2['material_price'];
                            }
                            unset($materials[$k2]);
                            break;
                        }
                    }
                }
                array_push($dataList, [
                    'report_id' => $report->id,
                    'material' => $v['material'],
                    'order_count' => $order_count,
                    'order_price' => $order_price,
                    'update_count' => $update_count,
                    'update_price' => $update_price,
                    'dinner_id' => $v['dinner_id'],
                    'dinner' => $v['dinner'],
                    'ordering_date' => $v['ordering_date'],
                    'state' => CommonEnum::STATE_IS_OK
                ]);
                $money += $update_count * $update_price;
            }
            $detail = (new MaterialReportDetailT())->saveAll($dataList);
            if (!$detail) {
                throw new SaveException(['msg' => '保存报表详情失败']);
            }
            $report->money = $money;
            $report->save();
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;
        }
    }

    public
    function orderMaterials($time_begin, $time_end, $canteen_id, $company_id)
    {
        $statistic = OrderMaterialV::orderMaterials($time_begin, $time_end, $canteen_id, $company_id);
        //获取该企业/饭堂下所有材料价格
        $materials = MaterialPriceV::materialsForOrder($canteen_id, $company_id);
        $statistic = $this->prefixMaterials($statistic, $materials);
        return $statistic;
    }


    private
    function checkMaterialCanUpdate($canteen_id, $time_begin, $time_end)
    {
        $time_begin = 'date_format("' . $time_begin . '","%Y-%m-%d")';
        $time_end = 'date_format("' . $time_end . '","%Y-%m-%d")';
        $sql = '(time_begin > ' . $time_begin . ' and time_begin < ' . $time_end .
            ') or ( time_end > ' . $time_begin . ' and ' . 'time_end < ' . $time_end . ')' .
            ' or (time_begin < ' . $time_begin . ' and time_end > ' . $time_end . ')';
        $count = MaterialReportT::where('canteen_id', $canteen_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->whereRaw($sql)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count();
        if ($count) {
            throw new SaveException(['msg' => '已经修改，不能重复修改']);
        }

    }

    public
    function materialReports($page, $size, $time_begin, $time_end, $canteen_id)
    {
        $list = MaterialReportT::reports($page, $size, $time_begin, $time_end, $canteen_id);
        return $list;
    }

    public
    function materialReport($report_id, $page, $size)
    {
        $report = MaterialReportT::get($report_id);
        if (empty($report)) {
            throw new ParameterException(['msg' => '导出报表不存在']);
        }
        if ($report->state == CommonEnum::STATE_IS_FAIL) {
            throw new ParameterException(['msg' => '报表已废除']);
        }
        $records = MaterialReportDetailT::orderRecords($page, $size, $report_id);
        return [
            'list' => $records,
            'money' => $report->money
        ];


    }

    private
    function getMaterialMoney($time_begin, $time_end, $canteen_id, $report_id, $materials)
    {
        $money = 0;
        $allRecords = OrderMaterialV::allRecords($time_begin, $time_end, $canteen_id);
        if (!count($allRecords)) {
            return $money;
        }
        $updateRecords = array();
        if ($report_id) {
            $updateRecords = MaterialReportDetailT::statistic($report_id);
        }
        foreach ($allRecords as $k => $v) {
            $check = true;
            if (count($updateRecords)) {
                foreach ($updateRecords as $k2 => $v2) {
                    if (strtotime($v['ordering_date']) == strtotime($v2['ordering_date'])
                        && $v['dinner_id'] == $v2['dinner_id']
                        && $v['material'] == $v2['material']) {
                        $money += $v2['count'] * $v2['price'];
                        unset($updateRecords[$k2]);
                        $check = false;
                        break;
                    }
                }
            }
            if (count($materials) && $check) {
                if (count($materials)) {
                    foreach ($materials as $k3 => $v3) {
                        if ($v['material'] == $v3['name']) {
                            $money += $v['order_count'] * $v3['price'];
                            break;
                        }

                    }
                }
            }

        }
        return $money;
    }

    public
    function consumptionStatistic($canteen_id, $status, $type,
                                  $department_id, $username, $staff_type_id, $time_begin,
                                  $time_end, $company_id, $phone, $page, $size, $order_type)
    {
        $version = \think\facade\Request::param('version');
        switch ($type) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                return $this->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
            case OrderEnum::STATISTIC_BY_USERNAME:

                if ($version == 'v1') {
                    return $this->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $page, $size, $version);
                } else {
                    return $this->consumptionStatisticByUsernameWithAccount($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $page, $size, $version);
                }
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                return $this->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
            case OrderEnum::STATISTIC_BY_CANTEEN:
                return $this->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
            case OrderEnum::STATISTIC_BY_STATUS:
                return $this->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
            default:
                throw new ParameterException();
        }
    }

    public
    function exportConsumptionStatistic($canteen_id, $status, $type,
                                        $department_id, $username, $staff_type_id,
                                        $time_begin, $time_end, $company_id,
                                        $phone, $order_type)
    {
        $locationName = $this->getLocationName($order_type, $canteen_id);
        $fileNameArr = [
            0 => $locationName . "消费总报表",
            1 => $locationName . "订餐就餐消费总报表",
            2 => $locationName . "订餐未就餐消费总报表",
            3 => $locationName . "未订餐就餐消费总报表",
            4 => $locationName . "系统补充总报表",
            5 => $locationName . "系统补扣总报表",
            6 => $locationName . "小卖部消费总报表",
            7 => $locationName . "小卖部退款总报表"
        ];
        $version = \think\facade\Request::param('version');
        switch ($type) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                $info = $this->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_USERNAME:
                $info = $this->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, 1, 10000, $version);
                break;
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                $info = $this->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_CANTEEN:
                $info = $this->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_STATUS:
                $info = $this->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            default:
                throw new ParameterException();
        }
        if ($type == OrderEnum::STATISTIC_BY_USERNAME) {
            $statistic = $info['statistic']['data'];
        } else {
            $statistic = $info['statistic'];
        }

        $header = ['序号', '统计变量', '开始时间', '结束时间', '姓名', '部门'];
        //获取饭堂对应的餐次设置
        $dinner = DinnerT::dinnerNames($canteen_id);

        if ($order_type != "canteen") {
            array_push($dinner, [
                'id' => 0,
                'name' => "小卖部消费"
            ]);
            array_push($dinner, [
                'id' => 0,
                'name' => "小卖部退款"
            ]);
        }

        $header = $this->addDinnerAndAccountToHeader($header, $dinner);
        $reports = $this->prefixConsumptionStatistic($statistic, $dinner, $time_begin, $time_end);
        $reportName = $fileNameArr[$status];
        $file_name = $reportName . "(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $reports, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    public
    function exportConsumptionStatisticWithAccount($canteen_id, $status, $type,
                                                   $department_id, $username, $staff_type_id,
                                                   $time_begin, $time_end, $company_id,
                                                   $phone, $order_type)
    {
        $locationName = $this->getLocationName($order_type, $canteen_id);
        $fileNameArr = [
            0 => $locationName . "消费总报表",
            1 => $locationName . "订餐就餐消费总报表",
            2 => $locationName . "订餐未就餐消费总报表",
            3 => $locationName . "未订餐就餐消费总报表",
            4 => $locationName . "系统补充总报表",
            5 => $locationName . "系统补扣总报表",
            6 => $locationName . "小卖部消费总报表",
            7 => $locationName . "小卖部退款总报表"
        ];
        $version = \think\facade\Request::param('version');
        switch ($type) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                $info = $this->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_USERNAME:
                $info = $this->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, 1, 10000, $version);
                break;
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                $info = $this->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_CANTEEN:
                $info = $this->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_STATUS:
                $info = $this->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            default:
                throw new ParameterException();
        }
        if ($type == OrderEnum::STATISTIC_BY_USERNAME) {
            $statistic = $info['consumptionRecords']['statistic']['data'];
        } else {
            $statistic = $info['consumptionRecords']['statistic'];
        }
        $accountRecords = $info['accountRecords'];

        $header = ['序号', '统计变量', '开始时间', '结束时间', '姓名', '部门'];
        //获取饭堂对应的餐次设置
        $dinner = DinnerT::dinnerNames($canteen_id);
        $accounts = CompanyAccountT:: accountsWithSorts($company_id);
        if ($order_type != "canteen") {
            array_push($dinner, [
                'id' => 0,
                'name' => "小卖部消费"
            ]);
            array_push($dinner, [
                'id' => 0,
                'name' => "小卖部退款"
            ]);
        }


        $header = $this->addDinnerAndAccountToHeader($header, $dinner, $accounts);
        $reports = $this->prefixConsumptionStatisticWithAccount($statistic, $accountRecords, $accounts, $dinner, $time_begin, $time_end);
        $reportName = $fileNameArr[$status];
        $file_name = $reportName . "(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $reports, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }


    private function getLocationName($orderType, $locationID)
    {
        if ($orderType == "canteen") {
            $location = CanteenT::canteen($locationID);
        } else {
            $location = ShopT::shop($locationID);
        }
        if ($location) {
            return $location->name;
        }
        return '';
    }


    private
    function addDinnerAndAccountToHeader($header, $dinner, $accounts = [])
    {
        if (count($dinner)) {
            foreach ($dinner as $k => $v) {
                array_push($header, $v['name'] . "数量", $v['name'] . '金额（元）');
            }
        }
        array_push($header, "总数量");
        if (count($accounts)) {
            foreach ($accounts as $k => $v) {
                array_push($header, $v['name'] . '合计（元）');
            }
        }
        array_push($header, '总金额（元）');
        return $header;

    }

    private
    function prefixConsumptionStatistic($statistic, $dinner, $time_begin, $time_end)
    {
        $dataList = [];
        $endData = $this->addDinnerToStatistic($dinner, $time_begin, $time_end);
        if (!empty($statistic)) {
            foreach ($statistic as $k => $v) {
                $dinner_statistic = array_key_exists('dinnerStatistic', $v) ? $v['dinnerStatistic'] : $v['dinner_statistic'];
                $data = $this->addDinnerToStatistic($dinner, $time_begin, $time_end);
                $data['number'] = $k + 1;
                $data['statistic'] = $v['statistic'];
                $data['username'] = empty($v['username']) ? '' : $v['username'];
                $data['department'] = empty($v['department']) ? '' : $v['department'];
                if (empty($dinner_statistic)) {
                    continue;
                }
                foreach ($dinner_statistic as $k2 => $v2) {
                    if (key_exists($v2['dinner_id'] . $v2['dinner'] . 'count', $data)) {
                        $data[$v2['dinner_id'] . $v2['dinner'] . 'count'] = $v2['order_count'];
                        $endData[$v2['dinner_id'] . $v2['dinner'] . 'count'] += $v2['order_count'];
                        $data['allCount'] += $v2['order_count'];
                        $endData['allCount'] += $v2['order_count'];
                    }
                    if (key_exists($v2['dinner_id'] . $v2['dinner'] . 'money', $data)) {
                        $data[$v2['dinner_id'] . $v2['dinner'] . 'money'] = $v2['order_money'];
                        $endData[$v2['dinner_id'] . $v2['dinner'] . 'money'] += $v2['order_money'];
                        $data['allMoney'] += $v2['order_money'];
                        $endData['allMoney'] += $v2['order_money'];
                    }

                }
                array_push($dataList, $data);
            }
        }
        array_push($dataList, $endData);
        return $dataList;
    }

    private
    function prefixConsumptionStatisticWithAccount($statistic, $accountRecords, $accounts, $dinner, $time_begin, $time_end)
    {
        $dataList = [];
        $endData = $this->addDinnerToStatistic($dinner, $time_begin, $time_end, $accounts);
        if (!empty($statistic)) {
            foreach ($statistic as $k => $v) {
                $dinner_statistic = array_key_exists('dinnerStatistic', $v) ? $v['dinnerStatistic'] : $v['dinner_statistic'];
                $data = $this->addDinnerToStatistic($dinner, $time_begin, $time_end, $accounts);
                $data['number'] = $k + 1;
                $data['statistic'] = $v['statistic'];
                $data['username'] = empty($v['username']) ? '' : $v['username'];
                $data['department'] = empty($v['department']) ? '' : $v['department'];
                if (empty($dinner_statistic)) {
                    continue;
                }
                foreach ($dinner_statistic as $k2 => $v2) {
                    if (key_exists($v2['dinner_id'] . $v2['dinner'] . 'count', $data)) {
                        $data[$v2['dinner_id'] . $v2['dinner'] . 'count'] = $v2['order_count'];
                        $endData[$v2['dinner_id'] . $v2['dinner'] . 'count'] += $v2['order_count'];
                        $data['allCount'] += $v2['order_count'];
                        $endData['allCount'] += $v2['order_count'];
                    }
                    if (key_exists($v2['dinner_id'] . $v2['dinner'] . 'money', $data)) {
                        $data[$v2['dinner_id'] . $v2['dinner'] . 'money'] = $v2['order_money'];
                        $endData[$v2['dinner_id'] . $v2['dinner'] . 'money'] += $v2['order_money'];
                        $data['allMoney'] += $v2['order_money'];
                        $endData['allMoney'] += $v2['order_money'];
                    }

                }

                foreach ($accountRecords as $k2 => $v2) {
                    if ($v['statistic_id'] == $v2['statistic_id']) {
                        foreach ($accounts as $k3 => $v3) {
                            if ($v2['account_id'] == $v3['id']) {
                                $data[$v3['id'] . $v3['name']] = $v2['money'];
                                $endData[$v3['id'] . $v3['name']] += $v2['money'];
                                break;
                            }

                        }

                    }

                }
                array_push($dataList, $data);
            }
        }
        array_push($dataList, $endData);
        return $dataList;
    }

    private
    function addDinnerToStatistic($dinner, $time_begin, $time_end, $accounts = [])
    {
        $data = [
            'number' => '合计',
            'statistic' => '',
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'username' => '',
            'department' => '',
        ];
        foreach ($dinner as $k => $v) {
            $data[$v['id'] . $v['name'] . 'count'] = 0;
            $data[$v['id'] . $v['name'] . 'money'] = 0;
        }
        $data['allCount'] = 0;

        if (count($accounts)) {
            foreach ($accounts as $k => $v) {
                $data[$v['id'] . $v['name']] = 0;
            }
        }
        $data['allMoney'] = 0;
        return $data;

    }

    private
    function consumptionStatisticByDepartment($canteen_id, $status, $department_id,
                                              $username, $staff_type_id, $time_begin,
                                              $time_end, $company_id, $phone, $order_type, $version)
    {

        if ($version == "v1") {
            $statistic = OrderConsumptionV::consumptionStatisticByDepartment($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            $statistic = $this->prefixStatistic($statistic, 'department', $time_begin, $time_end, $status);
            return $statistic;
        } else if ($version == "v2") {
            $statistic = OrderConsumptionAccountV::consumptionStatisticByDepartment($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            $statistic = $this->prefixStatistic($statistic, 'department', $time_begin, $time_end, $status);

            $accountRecords = AccountRecordsV::consumptionStatisticByDepartment($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            return [
                'consumptionRecords' => $statistic,
                'accountRecords' => $accountRecords
            ];
        }


    }

    private
    function prefixStatistic($statistic, $field, $time_begin, $time_end, $status)
    {
        $fieldArr = [];
        $data = [];
        $allMoney = 0;
        $allCount = 0;
        if (count($statistic)) {
            foreach ($statistic as $k => $v) {
                $orderMoney = $v['order_money'];//$status ? abs($v['order_money']) : $v['order_money'];
                $orderCount = $v['order_count'];
                $allMoney += $orderMoney;
                $allCount += $orderCount;
                if (in_array($v[$field], $fieldArr)) {
                    continue;
                }
                if (!key_exists($v['statistic_id'], $fieldArr)) {
                    $fieldArr[$v['statistic_id']] = $v[$field];
                }
                // array_push($fieldArr, $v[$field]);

            }

            foreach ($fieldArr as $k => $v) {
                $dinnerStatistic = [];
                foreach ($statistic as $k2 => $v2) {
                    if ($v == $v2[$field]) {
                        array_push($dinnerStatistic, [
                            'dinner_id' => $v2['dinner_id'],
                            'dinner' => $v2['dinner'],
                            'order_count' => $v2['order_count'],
                            //'order_money' => $status ? abs($v2['order_money']) : $v2['order_money']]);
                            'order_money' => $v2['order_money']
                        ]);
                    }
                }
                array_push($data, [
                    'statistic_id' => $k,
                    'statistic' => $v,
                    'time_begin' => $time_begin,
                    'time_end' => $time_end,
                    $field => $v,
                    'dinnerStatistic' => $dinnerStatistic
                ]);

            }
        }
        return [
            'statistic' => $data,
            'allMoney' => round($allMoney, 2),
            'allCount' => $allCount
        ];
    }


    private
    function consumptionStatisticByUsername($canteen_id, $status, $department_id,
                                            $username, $staff_type_id, $time_begin,
                                            $time_end, $company_id, $phone, $order_type, $page, $size, $version)
    {

        $users = OrderConsumptionV::userStatistic($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id, $phone, $order_type, $page, $size);

        $statistic = OrderConsumptionV::userDinnerStatistic($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id, $phone, $order_type, $page, $size);

        if (!count($users)) {
            return $users;
        }
        $data = $users['data'];
        foreach ($data as $k => $v) {
            $data[$k]['time_begin'] = $time_begin;
            $data[$k]['time_end'] = $time_end;
            $dinnerStatistic = [];
            foreach ($statistic as $k2 => $v2) {
                if ($v['staff_id'] == $v2['staff_id']) {
                    array_push($dinnerStatistic, $statistic[$k2]);
                    unset($statistic[$k2]);
                }
                $data[$k]['dinnerStatistic'] = $dinnerStatistic;
            }
        }
        $users['data'] = $data;

        $statistic = OrderConsumptionV::consumptionStatisticByUsername($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id);

        return [
            'allMoney' => $statistic['order_money'],
            'allCount' => $statistic['order_count'],
            'statistic' => $users
        ];
    }


    private
    function consumptionStatisticByUsernameWithAccount($canteen_id, $status, $department_id,
                                                       $username, $staff_type_id, $time_begin,
                                                       $time_end, $company_id, $phone, $order_type, $page, $size, $version)
    {

        $users = OrderConsumptionAccountV::userStatistic($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id, $phone, $order_type, $page, $size);

        $statistic = OrderConsumptionAccountV::userDinnerStatistic($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id, $phone, $order_type, $page, $size);

        if (!count($users)) {
            return $users;
        }
        $data = $users['data'];
        foreach ($data as $k => $v) {
            $data[$k]['time_begin'] = $time_begin;
            $data[$k]['time_end'] = $time_end;
            $dinnerStatistic = [];
            foreach ($statistic as $k2 => $v2) {
                if ($v['staff_id'] == $v2['staff_id']) {
                    array_push($dinnerStatistic, $statistic[$k2]);
                    unset($statistic[$k2]);
                }
                $data[$k]['dinnerStatistic'] = $dinnerStatistic;
            }
        }
        $users['data'] = $data;

        $statistic = OrderConsumptionAccountV::consumptionStatisticByUsername($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id);

        $accountRecords = AccountRecordsV::userDinnerStatistic($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id, $phone, $order_type);
        return [
            'consumptionRecords' => $users,
            'accountRecords' => $accountRecords,
            'allMoney' => $statistic['order_money'],
            'allCount' => $statistic['order_count']
        ];

    }


    private
    function consumptionStatisticByStatus($canteen_id, $status, $department_id,
                                          $username, $staff_type_id, $time_begin,
                                          $time_end, $company_id, $phone, $order_type, $version)
    {
        if ($version == "v1") {
            $statistic = OrderConsumptionV::consumptionStatisticByStatus($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            $statistic = $this->prefixStatistic($statistic, 'status', $time_begin, $time_end, $status);
            return $statistic;
        } else if ($version == "v2") {
            $statistic = OrderConsumptionAccountV::consumptionStatisticByStatus($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            $statistic = $this->prefixStatistic($statistic, 'status', $time_begin, $time_end, $status);

            $accountRecords = AccountRecordsV::consumptionStatisticByStatus($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            return [
                'consumptionRecords' => $statistic,
                'accountRecords' => $accountRecords
            ];
        }


    }

    private
    function consumptionStatisticByCanteen($canteen_id, $status, $department_id,
                                           $username, $staff_type_id, $time_begin,
                                           $time_end, $company_id, $phone, $order_type, $version)
    {
        if ($version == "v1") {
            $statistic = OrderConsumptionV::consumptionStatisticByCanteen($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            $statistic = $this->prefixStatistic($statistic, 'canteen', $time_begin, $time_end, $status);

            return $statistic;

        } else if ($version == "v2") {
            $statistic = OrderConsumptionAccountV::consumptionStatisticByCanteen($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            $statistic = $this->prefixStatistic($statistic, 'canteen', $time_begin, $time_end, $status);

            $accountRecords = AccountRecordsV::consumptionStatisticByCanteen($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            return [
                'consumptionRecords' => $statistic,
                'accountRecords' => $accountRecords
            ];
        }


    }

    private
    function consumptionStatisticByStaff($canteen_id, $status, $department_id,
                                         $username, $staff_type_id, $time_begin,
                                         $time_end, $company_id, $phone, $order_type, $version)
    {
        if ($version == "v1") {
            $statistic = OrderConsumptionV::consumptionStatisticByStaff($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            $statistic = $this->prefixStatistic($statistic, 'staff_type', $time_begin, $time_end, $status);

            return $statistic;

        } else if ($version == "v2") {
            $statistic = OrderConsumptionAccountV::consumptionStatisticByStaff($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            $statistic = $this->prefixStatistic($statistic, 'staff_type', $time_begin, $time_end, $status);

            $accountRecords = AccountRecordsV::consumptionStatisticByStaff($canteen_id, $status, $department_id,
                $username, $staff_type_id, $time_begin,
                $time_end, $company_id, $phone, $order_type);
            return [
                'consumptionRecords' => $statistic,
                'accountRecords' => $accountRecords
            ];

        }

    }

    public
    function exportMaterialReports($report_id)
    {
        $header = ['序号', '日期', '饭堂', '餐次', '材料名称', '单位', '材料数量', '订货数量', '单价', '总价'];

        $report = MaterialReportT::exportReports($report_id);
        $file_name = $report['title'];
        $report = $this->prefixMaterialReports($report);
        $url = (new ExcelService())->makeExcel($header, $report, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private
    function prefixMaterialReports($report)
    {
        $dataList = [];
        if (!empty($report['detail'])) {
            $detail = $report['detail'];
            foreach ($detail as $k => $v) {
                array_push($dataList, [
                    'number' => $k + 1,
                    'order_date' => $v['ordering_date'],
                    'canteen' => $report['canteen']['name'],
                    'dinner' => $v['dinner'],
                    'material' => $v['material'],
                    'unit' => 'kg',
                    'order_count' => $v['order_count'],
                    'update_count' => $v['update_count'],
                    'update_price' => $v['update_price'],
                    'money' => $v['update_price'] * $v['update_count'],
                ]);
            }
        }

        array_push($dataList, [
            'number' => '合计',
            'ordering_date' => '',
            'canteen' => '',
            'dinner' => '',
            'material' => '',
            'unit' => 'kg',
            'order_count' => '',
            'material_count' => '',
            'material_price' => '',
            'money' => empty($report['money']) ? 0 : $report['money']
        ]);
        return $dataList;

    }

    public
    function exportOrderMaterials($time_begin, $time_end, $canteen_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $statistic = OrderMaterialV::exportOrderMaterials($time_begin, $time_end, $canteen_id, $company_id);
        //获取该企业/饭堂下所有材料价格
        $materials = MaterialPriceV::materialsForOrder($canteen_id, $company_id);
        $statistic = $this->prefixMaterials($statistic, $materials, true);
        $header = ['序号', '日期', '餐次', '材料名称', '材料数量', '订货数量', '单价', '总价'];
        $file_name = "材料明细下单表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $statistic, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

}