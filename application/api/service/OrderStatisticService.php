<?php


namespace app\api\service;


use app\api\model\CompanyStaffT;
use app\api\model\DinnerT;
use app\api\model\MaterialPriceV;
use app\api\model\MaterialReportDetailT;
use app\api\model\MaterialReportDetailV;
use app\api\model\MaterialReportT;
use app\api\model\OrderConsumptionV;
use app\api\model\OrderMaterialV;
use app\api\model\OrderSettlementV;
use app\api\model\OrderStatisticV;
use app\api\model\OrderT;
use app\api\model\OrderTakeoutStatisticV;
use app\api\model\StaffCanteenV;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
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
        $header = ['日期', '公司', '消费地点', '餐次', '消费人数'];
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
        return $list;
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
        $header = ['订单ID', '订餐日期', '消费地点', '部门', '姓名', '餐次', '订餐类型', '明细'];
        $file_name = "订餐明细报表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $list, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    private function prefixOrderStatisticDetail($list)
    {
        foreach ($list as $k => $v) {
            $foods = $v['foods'];
            unset($list[$k]['foods']);
            $detail = [];
            foreach ($foods as $k2 => $v2) {
                array_push($detail, $v2['name'] . '*' . $v2['count']);
            }
            $list[$k]['foods'] = implode('  ', $detail);
        }
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

    public function exportOrderSettlement(
        $name, $phone, $canteen_id, $department_id, $dinner_id,
        $consumption_type, $time_begin, $time_end, $company_ids)
    {
        $records = OrderSettlementV::exportOrderSettlement(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids);
        $records = $this->prefixExportOrderSettlement($records);
        $header = ['序号', '消费时间', '部门', '姓名', '手机号', '消费地点', '消费类型', '餐次'];
        $file_name = "消费明细报表（" . $time_begin . "-" . $time_end . "）";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private function prefixExportOrderSettlement($data)
    {
        $dataList = [];
        if (count($data)) {
            foreach ($data as $k => $v) {

                if ($v['type'] == 'recharge') {
                    $consumption_type = "补录";
                } else {
                    if ($v['booking'] == CommonEnum::STATE_IS_OK) {
                        $consumption_type = $v['used'] == CommonEnum::STATE_IS_OK ? "订餐就餐" : "订餐未就餐";
                    } else {
                        $consumption_type = "未订餐就餐";
                    }
                }

                array_push($dataList, [
                    'number' => $k + 1,
                    'used_time' => $v['used_time'],
                    'department' => $v['department'],
                    'username' => $v['username'],
                    'phone' => $v['phone'],
                    'canteen' => $v['canteen'],
                    'consumption_type' => $consumption_type,
                    'dinner' => $v['dinner'],
                ]);
            }
        }
        return $dataList;
    }

    private function prefixSettlementConsumptionType($data)
    {
        if (count($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['used_time'] = $v['used'] == CommonEnum::STATE_IS_OK ? $v['used_time'] : "-";
                if ($v['type'] == 'recharge') {
                    $data[$k]['consumption_type'] = "补录";
                    continue;
                }
                if ($v['booking'] == CommonEnum::STATE_IS_OK) {
                    $data[$k]['consumption_type'] = $v['used'] == CommonEnum::STATE_IS_OK ? "订餐就餐" : "订餐未就餐";
                } else {
                    $data[$k]['consumption_type'] = "未订餐就餐";
                }
            }

        }
        return $data;
    }

    public function takeoutStatistic($page, $size,
                                     $ordering_date, $company_ids,
                                     $canteen_id, $dinner_id, $status, $department_id, $user_type)
    {
        $records = OrderTakeoutStatisticV::statistic($page, $size,
            $ordering_date, $company_ids, $canteen_id, $dinner_id, $status, $department_id, $user_type);
        return $records;
    }

    public function takeoutStatisticForOfficial($page, $size,
                                                $ordering_date, $dinner_id, $status, $department_id)
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $records = OrderTakeoutStatisticV::officialStatistic($page, $size,
            $ordering_date, $dinner_id, $status, $department_id, $canteen_id);
        return $records;
    }


    public function exportTakeoutStatistic($ordering_date, $company_ids,
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

    private function prefixExportTakeoutStatistic($records)
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
        $statistic['data'] = $this->prefixMaterials($statistic['data'], $materials);
        return [
            'list' => $statistic,
            'money' => $this->getMaterialMoney($time_begin, $time_end, $canteen_id, 0, $materials)
        ];
    }

    private function prefixMaterials($data, $materials, $statisticMoney = false)
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

    public function updateOrderMaterial($params)
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

    public function orderMaterials($time_begin, $time_end, $canteen_id, $company_id)
    {
        $statistic = OrderMaterialV::orderMaterials($time_begin, $time_end, $canteen_id, $company_id);
        //获取该企业/饭堂下所有材料价格
        $materials = MaterialPriceV::materialsForOrder($canteen_id, $company_id);
        $statistic = $this->prefixMaterials($statistic, $materials);
        return $statistic;
    }


    private function checkMaterialCanUpdate($canteen_id, $time_begin, $time_end)
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

    public function materialReports($page, $size, $time_begin, $time_end, $canteen_id)
    {
        $list = MaterialReportT::reports($page, $size, $time_begin, $time_end, $canteen_id);
        return $list;
    }

    public function materialReport($report_id, $page, $size)
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

    private function getMaterialMoney($time_begin, $time_end, $canteen_id, $report_id, $materials)
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

    public function consumptionStatistic($canteen_id, $status, $type,
                                         $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $page, $size)
    {
        switch ($type) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                return $this->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id);
                break;
            case OrderEnum::STATISTIC_BY_USERNAME:
                return $this->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $page, $size);
                break;
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                return $this->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id);
                break;
            case OrderEnum::STATISTIC_BY_CANTEEN:
                return $this->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id);
                break;
            case OrderEnum::STATISTIC_BY_STATUS:
                return $this->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id);
                break;
            default:
                throw new ParameterException();
        }
    }

    public function exportConsumptionStatistic($canteen_id, $status, $type,
                                               $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id)
    {
        switch ($type) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                $info = $this->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id);
                break;
            case OrderEnum::STATISTIC_BY_USERNAME:
                $info = $this->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, 1, 10000);
                break;
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                $info = $this->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id);
                break;
            case OrderEnum::STATISTIC_BY_CANTEEN:
                $info = $this->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id);
                break;
            case OrderEnum::STATISTIC_BY_STATUS:
                $info = $this->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id);
                break;
            default:
                throw new ParameterException();
        }
        if ($type == OrderEnum::STATISTIC_BY_USERNAME) {
            $statistic = $info['statistic']['data'];
        } else {
            $statistic = $info['statistic'];
        }
        $allMoney = empty($info['allMoney']) ? 0 : $info['allMoney'];
        $allCount = empty($info['allCount']) ? 0 : $info['allCount'];
        $reports = $this->prefixConsumptionStatistic($statistic, $allMoney, $allCount);
        $header = ['序号', '统计变量', '开始时间', '结束时间', '姓名', '部门', '餐次', '数量', '金额（元）'];
        $file_name = "消费总报表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcelMerge($header, $reports, $file_name, 6);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    private function prefixConsumptionStatistic($statistic, $allMoney, $allCount)
    {
        $dataList = [];
        if (!empty($statistic)) {
            $i = 2;
            foreach ($statistic as $k => $v) {
                // $v['dinnerStatistic']
                $dinner_statistic = array_key_exists('dinnerStatistic', $v) ? $v['dinnerStatistic'] : $v['dinner_statistic'];
                if (empty($dinner_statistic)) {
                    array_push($dataList, [
                        'number' => $k + 1,
                        'statistic' => $v['statistic'],
                        'time_begin' => '/',
                        'time_end' => '/',
                        'username' => empty($v['username']) ? '' : $v['username'],
                        'department' => empty($v['department']) ? '' : $v['department'],
                        'dinner' => '',
                        'order_count' => 0,
                        'order_money' => 0,
                        'merge' => CommonEnum::STATE_IS_FAIL,
                        'start' => 0,
                        'end' => 0
                    ]);
                    $i++;
                    continue;
                }
                $all_order_count = 0;
                $all_order_money = 0;
                foreach ($dinner_statistic as $k2 => $v2) {
                    array_push($dataList, [
                        'number' => $k + 1,
                        'statistic' => $v['statistic'],
                        'time_begin' => '/',
                        'time_end' => '/',
                        'username' => empty($v['username']) ? '' : $v['username'],
                        'department' => empty($v['department']) ? '' : $v['department'],
                        'dinner' => $v2['dinner'],
                        'order_count' => $v2['order_count'],
                        'order_money' => $v2['order_money'],
                        'merge' => CommonEnum::STATE_IS_OK,
                        'start' => $k2 == 0 ? $i : $i - 1,
                        'end' => $i
                    ]);
                    $all_order_count += $v2['order_count'];
                    $all_order_money += $v2['order_money'];
                    $i++;
                }
                //  $i = $i + 1;
                array_push($dataList, [
                    'number' => '',
                    'statistic' => '',
                    'time_begin' => '',
                    'time_end' => '',
                    'username' => '',
                    'department' => '',
                    'dinner' => "合计",
                    'order_count' => $all_order_count,
                    'order_money' => $all_order_money,
                    'merge' => CommonEnum::STATE_IS_OK,
                    'start' => $i - 1,
                    'end' => $i
                ]);

            }
        }

        array_push($dataList, [
            'number' => '合计',
            'statistic' => '',
            'time_begin' => '',
            'time_end' => '',
            'username' => '',
            'department' => '',
            'dinner' => '',
            'order_count' => $allCount,
            'order_money' => $allMoney,
            'merge' => CommonEnum::STATE_IS_FAIL,
            'start' => 0,
            'end' => 0,
        ]);
        return $dataList;
    }

    private function consumptionStatisticByDepartment($canteen_id, $status, $department_id,
                                                      $username, $staff_type_id, $time_begin,
                                                      $time_end, $company_id)
    {
        $statistic = OrderConsumptionV::consumptionStatisticByDepartment($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id);
        $statistic = $this->prefixStatistic($statistic, 'department', $time_begin, $time_end);
        return $statistic;

    }

    private function prefixStatistic($statistic, $field, $time_begin, $time_end)
    {
        $fieldArr = [];
        $data = [];
        $allMoney = 0;
        $allCount = 0;
        if (count($statistic)) {
            foreach ($statistic as $k => $v) {
                $allMoney += $v['order_money'];
                $allCount += $v['order_count'];
                if (in_array($v[$field], $fieldArr)) {
                    continue;
                }
                array_push($fieldArr, $v[$field]);

            }
            foreach ($fieldArr as $k => $v) {
                $dinnerStatistic = [];
                foreach ($statistic as $k2 => $v2) {
                    if ($v == $v2[$field]) {
                        array_push($dinnerStatistic, [
                            'dinner_id' => $v2['dinner_id'],
                            'dinner' => $v2['dinner'],
                            'order_count' => $v2['order_count'],
                            'order_money' => $v2['order_money'],
                        ]);
                    }
                }
                array_push($data, [
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
            'allMoney' => round($allMoney, 1),
            'allCount' => $allCount
        ];
    }

    private function consumptionStatisticByUsername($canteen_id, $status, $department_id,
                                                    $username, $staff_type_id, $time_begin,
                                                    $time_end, $company_id, $page, $size)
    {
        //获取人员信息
        $users = StaffCanteenV::getStaffsForStatistic($company_id, $canteen_id, $page, $size, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end);
        $data = $users['data'];
        foreach ($data as $k => $v) {
            $data[$k]['time_begin'] = $time_begin;
            $data[$k]['time_end'] = $time_end;
            $data[$k]['dinnerStatistic'] = OrderConsumptionV::userDinnerStatistic($v['staff_id'], $status,
                $time_begin, $time_end);
        }
        $statistic = OrderConsumptionV::consumptionStatisticByUsername($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id);
        $users['data'] = $data;
        return [
            'statistic' => $users,
            'allMoney' => round($statistic['order_money'], 1),
            'allCount' => $statistic['order_count']
        ];
    }


    private function consumptionStatisticByStatus($canteen_id, $status, $department_id,
                                                  $username, $staff_type_id, $time_begin,
                                                  $time_end, $company_id)
    {
        $statistic = OrderConsumptionV::consumptionStatisticByStatus($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id);
        $statistic = $this->prefixStatistic($statistic, 'status', $time_begin, $time_end);
        return $statistic;

    }

    private function consumptionStatisticByCanteen($canteen_id, $status, $department_id,
                                                   $username, $staff_type_id, $time_begin,
                                                   $time_end, $company_id)
    {
        $statistic = OrderConsumptionV::consumptionStatisticByCanteen($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id);
        $statistic = $this->prefixStatistic($statistic, 'canteen', $time_begin, $time_end);
        return $statistic;

    }

    private function consumptionStatisticByStaff($canteen_id, $status, $department_id,
                                                 $username, $staff_type_id, $time_begin,
                                                 $time_end, $company_id)
    {
        $statistic = OrderConsumptionV::consumptionStatisticByStaff($canteen_id, $status, $department_id,
            $username, $staff_type_id, $time_begin,
            $time_end, $company_id);
        $statistic = $this->prefixStatistic($statistic, 'staff_type', $time_begin, $time_end);
        return $statistic;

    }

    public function exportMaterialReports($report_id)
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

    private function prefixMaterialReports($report)
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

    public function exportOrderMaterials($time_begin, $time_end, $canteen_id)
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