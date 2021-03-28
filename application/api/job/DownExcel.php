<?php


namespace app\api\job;


use app\api\controller\v2\Order;
use app\api\model\CompanyAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\CompanyStaffV;
use app\api\model\DinnerT;
use app\api\model\DinnerV;
use app\api\model\DownExcelT;
use app\api\model\FoodV;
use app\api\model\MaterialPriceV;
use app\api\model\OrderMaterialV;
use app\api\model\OrderSettlementV;
use app\api\model\OrderStatisticV;
use app\api\model\OrderTakeoutStatisticV;
use app\api\model\RechargeV;
use app\api\model\ShopOrderStatisticV;
use app\api\model\ShopOrderV;
use app\api\model\ShopProductT;
use app\api\model\UserBalanceV;
use app\api\service\AuthorService;
use app\api\service\CompanyService;
use app\api\service\DepartmentService;
use app\api\service\ExcelService;
use app\api\service\FoodService;
use app\api\service\GatewayService;
use app\api\service\LogService;
use app\api\service\MaterialService;
use app\api\service\NextMonthPayService;
use app\api\service\ShopService;
use app\api\service\Token;
use app\api\service\WalletService;
use app\lib\enum\DownEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\ShopEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use think\Db;
use think\Exception;
use think\queue\Job;
use app\api\service\OrderStatisticService as OrderStatisticServiceV1;

class DownExcel
{
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job $job 当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        try {


            // 有些消息在到达消费者时,可能已经不再需要执行了
            $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
            if (!$isJobStillNeedToBeDone) {
                $job->delete();
                return;
            }
            //执行excel导入
            $isJobDone = $this->doJob($data);
            if ($isJobDone) {
                // 如果任务执行成功，删除任务
                $code = $data['company_id'] . ":" . $data['u_id'] . ":" . $data['type'];
                LogService::saveJob("<warn>导出Excel任务执行成功！编号：$code" . "</warn>\n");
                $job->delete();
            } else {
                $job->delete();
                /*       if ($job->attempts() > 3) {
                           //通过这个方法可以检查这个任务已经重试了几次了
                           $code = $data['company_id'] . ":" . $data['u_id'] . ":" . $data['type'];
                           LogService::saveJob("<warn>导入excel已经重试超过3次，现在已经删除该任务编号：$code" . "</warn>\n");
                           $this->clearUploading($data['company_id'], $data['u_id'], $data['type']);
                           $job->delete();
                       } else {
                           $job->release(3); //重发任务
                       }*/
            }
        } catch (\Exception $e) {
            LogService::saveJob("<warn>导出Excel任务执行失败：" . $e->getMessage());
            $job->delete();
        }
    }

    /**
     * 该方法用于接收任务执行失败的通知
     * @param $data  string|array|... 发布任务时传递的数据
     */
    public function failed($data)
    {
        //可以发送邮件给相应的负责人员
        LogService::save("失败:" . json_encode($data));
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed $data 发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data)
    {
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doJob($data)
    {
        try {
            $excelType = $data['excel_type'];
            switch ($excelType) {
                case 'consumptionStatistic';
                    $this->exportConsumptionStatistic($data);
                    break;
                case 'consumptionStatisticWithAccount';
                    $this->exportConsumptionStatisticWithAccount($data);
                    break;
                case 'orderStatisticDetail';
                    $this->exportOrderStatisticDetail($data);
                    break;
                case 'orderSettlement';
                    $this->exportOrderSettlement($data);
                    break;
                case 'orderSettlementWithAccount';
                    $this->exportOrderSettlementWithAccount($data);
                    break;
                case 'orderStatistic';
                    $this->exportOrderStatistic($data);
                    break;
                case 'takeoutStatistic';
                    $this->exportTakeoutStatistic($data);
                    break;
                case 'face';
                    $this->exportFace($data);
                    break;
                case 'nextMonth';
                    $this->exportNextMonthPayStatistic($data);
                    break;
                case 'reception';
                    $this->receptionsForCMSOutput($data);
                    break;
                case 'receptionForApply';
                    $this->receptionsForApplyOutput($data);
                    break;
                case 'rechargeRecords';
                    $this->exportRechargeRecords($data);
                    break;
                case 'rechargeRecordsWithAccount';
                    $this->exportRechargeRecordsWithAccount($data);
                    break;
                case 'userBalance';
                    $this->exportUserBalance($data);
                    break;
                case 'userBalanceWithAccount';
                    $this->exportUserBalanceWithAccount($data);
                    break;
                case 'shopOrderStatisticToManager';
                    $this->exportOrderStatisticToManager($data);
                    break;
                case 'shopConsumptionStatistic';
                    $this->exportShopConsumptionStatistic($data);
                    break;
                case 'salesReportToManager';
                    $this->exportSalesReportToManager($data);
                    break;
                case 'foodMaterials';
                    $this->exportFoodMaterials($data);
                    break;
                case 'orderMaterials';
                    $this->exportOrderMaterials($data);
                    break;
                case 'materials';
                    $this->exportMaterials($data);
                    break;
                case 'staff';
                    $this->exportStaffs($data);
                    break;
            }
            return true;
        } catch (Exception $e) {
            LogService::saveJob("下载excel失败：error:" . $e->getMessage(), json_encode($data));
            return false;
        }

    }

    public function exportStaffs($data)
    {

        $company_id = $data['company_id'];
        $department_id = $data['department_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $downId = $data['down_id'];//检测企业是否包含刷卡消费
        $checkCard = (new CompanyService())->checkConsumptionContainsCard($company_id);
        //检测企业是否包含刷脸消费
        $checkFace = (new CompanyService())->checkConsumptionContainsFace($company_id);
        $staffs = CompanyStaffV::exportStaffs($company_id, $department_id);
        $staffs = (new DepartmentService())->prefixExportStaff($staffs, $checkCard, $checkFace);
        if ($checkCard && $checkFace) {
            $header = ['企业', '部门', '人员状态', '人员类型', '员工编号', '姓名', '手机号码', '卡号', '出生日期', '人脸识别ID', '归属饭堂'];
        } else
            if ($checkCard) {
                $header = ['企业', '部门', '人员状态', '人员类型', '员工编号', '姓名', '手机号码', '卡号', '出生日期', '归属饭堂'];
            } else
                if ($checkFace) {
                    $header = ['企业', '部门', '人员状态', '人员类型', '员工编号', '姓名', '手机号码', '人脸识别ID', '归属饭堂'];
                } else {

                    $header = ['企业', '部门', '人员状态', '人员类型', '员工编号', '姓名', '手机号码', '归属饭堂'];
                }

        $file_name = "企业员工导出";
        $url = (new ExcelService())->makeExcel2($header, $staffs, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportMaterials($data)
    {
        $params = $data['params'];
        $key = $data['key'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $selectField = (new MaterialService())->prefixSelectFiled($params);
        $materials = MaterialPriceV::exportMaterials($key, $selectField['field'], $selectField['value']);
        $header = ['序号', '企业名称', '饭堂名称', '材料名称', '单位', '金额-元'];
        $file_name = "材料价格明细";
        $url = (new ExcelService())->makeExcel2($header, $materials, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportOrderMaterials($data)
    {
        $company_id = $data['company_id'];
        $canteen_id = $data['canteen_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $statistic = OrderMaterialV::exportOrderMaterials($time_begin, $time_end, $canteen_id, $company_id);
        //获取该企业/饭堂下所有材料价格
        $materials = MaterialPriceV::materialsForOrder($canteen_id, $company_id);
        $statistic = (new OrderStatisticServiceV1())->prefixMaterials($statistic, $materials, true);
        $header = ['序号', '日期', '餐次', '材料名称', '材料数量', '订货数量', '单价', '总价'];
        $file_name = "材料明细下单表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel2($header, $statistic, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportFoodMaterials($data)
    {
        $params = $data['params'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $selectField = (new FoodService())->prefixSelectFiled($params);
        $foods = FoodV::exportFoodMaterials($selectField['field'], $selectField['value']);
        $foods = (new FoodService())->prefixFoodMaterials($foods);
        $header = ['企业', '饭堂', '餐次', '菜品', '材料名称', '数量', '单位'];
        $file_name = "菜品材料明细导出报表";
        $url = (new ExcelService())->makeExcelMerge2($header, $foods, $file_name, $SCRIPT_FILENAME, 4);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportSalesReportToManager($data)
    {
        $supplier_id = $data['supplier_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $products = ShopProductT::supplierProducts(1, 10000, $time_begin,
            $time_end, $supplier_id);
        $products = (new ShopService())->prefixExportSalesReport($products['data']);
        $header = ['序号', '名称', '单价（元）', '单位', '总进货量', '总销售量', '总销售额（元）'];
        $file_name = $time_begin . "-" . $time_end . "-进销报表";
        $url = (new ExcelService())->makeExcel2($header, $products, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportShopConsumptionStatistic($data)
    {
        $category_id = $data['category_id'];
        $status = $data['status'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $username = $data['username'];
        $product_id = $data['product_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $field = '';
        $supplier_id = 0;
        if (Token::getCurrentTokenVar('type') == 'supplier') {
            $supplier_id = (new AuthorService())->checkAuthorSupplier();
            $company_id = Token::getCurrentTokenVar('company_id');
        }
        if ($type == ShopEnum::STATISTIC_BY_CATEGORY) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByCategoryID(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'category_id';
        } else if ($type == ShopEnum::STATISTIC_BY_PRODUCT) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByProductID(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'product_id';
        } else if ($type == ShopEnum::STATISTIC_BY_STATUS) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByStatus(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
        } else if ($type == ShopEnum::STATISTIC_BY_DEPARTMENT) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByDepartmentID(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'department_id';
        } else if ($type == ShopEnum::STATISTIC_BY_USERNAME) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByUsername(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'staff_id';
        } else {
            throw new ParameterException();
        }

        if (empty($field)) {
            $statisticCount = 0;
        } else {
            $statisticCount = ShopOrderStatisticV::statisticCount($category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $field, $department_id, $username, $company_id);
        }

        $money = ShopOrderStatisticV::statisticMoney($category_id, $product_id,
            $status, $time_begin, $time_end, $supplier_id, $department_id,
            $username, $company_id);

        $statistics = (new ShopService())->prefixConsumptionStatistic($statistic['data'], $statisticCount, $money);
        $header = ['序号', '统计变量', '下单时间', '结束时间', '姓名', '部门', '类型', '商品名称', '单位', '数量', '商品总金额（元）'];
        $file_name = "消费订单汇总查询";
        $url = (new ExcelService())->makeExcel2($header, $statistics, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportOrderStatisticToManager($data)
    {
        $name = $data['name'];
        $phone = $data['phone'];
        $department_id = $data['department_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $status = $data['status'];
        $company_id = $data['company_id'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $statistic = ShopOrderV::exportOrderStatisticToManager($department_id, $name,
            $phone, $status, $time_begin, $time_end, $company_id);
        $statistic = $this->prefixOrderStatisticToExport($statistic);
        $header = ['序号', '下单时间', '结束时间', '姓名', '手机号', '商品数量', '商品金额（元）', '地址', '状态', '类型', '名称', '单位', '数量', '金额'];
        $file_name = "订单明细查询";
        $url = (new ExcelService())->makeExcelMerge2($header, $statistic, $file_name, $SCRIPT_FILENAME, 9);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);
    }

    public function exportUserBalance($data)
    {
        $department_id = $data['department_id'];
        $user = $data['user'];
        $phone = $data['phone'];
        $company_id = $data['company_id'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $checkCard = (new CompanyService())->checkConsumptionContainsCard($company_id);
        $staffs = UserBalanceV::exportUsersBalance($department_id, $user, $phone, $company_id, $checkCard);
        if ($checkCard) {
            $header = ['姓名', '员工编号', '卡号', '手机号码', '部门', '余额'];
        } else {
            $header = ['姓名', '员工编号', '手机号码', '部门', '余额'];
        }
        $file_name = "饭卡余额报表";
        $url = (new ExcelService())->makeExcel2($header, $staffs, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportUserBalanceWithAccount($data)
    {
        $department_id = $data['department_id'];
        $user = $data['user'];
        $phone = $data['phone'];
        $company_id = $data['company_id'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $accounts = CompanyAccountT::accountsWithSorts($company_id);
        $checkCard = (new CompanyService())->checkConsumptionContainsCard($company_id);
        $staffs = CompanyStaffT::staffsForExportsBalance($department_id, $user, $phone, $company_id);
        if ($checkCard) {
            $header = ['姓名', '员工编号', '卡号', '手机号码', '部门'];
        } else {
            $header = ['姓名', '员工编号', '手机号码', '部门'];
        }

        $header = (new WalletService())->prefixHeader($accounts, $header);
        $staffs = (new WalletService())->prefixExportBalanceWithAccount($staffs, $accounts, $checkCard);
        $file_name = "饭卡余额报表";
        $url = (new ExcelService())->makeExcel2($header, $staffs, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportRechargeRecordsWithAccount($data)
    {
        $admin_id = $data['admin_id'];
        $department_id = $data['department_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $username = $data['username'];
        $company_id = $data['company_id'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $type = $data['type'];
        $records = RechargeV::exportRechargeRecordsWithAccount($time_begin, $time_end, $type, $admin_id, $username, $company_id, $department_id);
        $header = ['创建时间', '部门', '姓名', "手机号", '账户名称', '充值金额', '充值途径', '充值人员', '备注'];
        $file_name = $time_begin . "-" . $time_end . "-充值记录明细";
        $url = (new ExcelService())->makeExcel2($header, $records, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    private function exportRechargeRecords($data)
    {
        $type = $data['type'];
        $admin_id = $data['admin_id'];
        $department_id = $data['department_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $username = $data['username'];
        $company_id = $data['company_id'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $records = RechargeV::exportRechargeRecords($time_begin, $time_end, $type,
            $admin_id, $username, $company_id, $department_id);
        $header = ['创建时间', '部门', '姓名', '手机号', '充值金额', '充值途径', '充值人员', '备注'];
        $file_name = $time_begin . "-" . $time_end . "-充值记录明细";
        $url = (new ExcelService())->makeExcel2($header, $records, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);
    }

    private function receptionsForCMSOutput($data)
    {
        $whereStr = '';
        $ordering_date = $data['ordering_date'];
        $apply_name = $data['apply_name'];
        $reception_code = $data['reception_code'];
        $department_id = $data['department_id'];
        $company_id = $data['company_id'];
        $dinner_id = $data['dinner_id'];
        $canteen_id = $data['canteen_id'];
        $reception_state = $data['reception_state'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        if (!empty($company_id)) {
            if ($company_id !== "ALL") {
                $whereStr .= 'and t7.id =' . $company_id . ' ';
            }
        }
        if (!empty($canteen_id)) {
            if ($canteen_id !== "ALL") {
                $whereStr .= 'and t3.id =' . $canteen_id . ' ';
            }
        }
        if (strlen($ordering_date)) {
            $whereStr .= 'and t2.ordering_date =' . "'$ordering_date'" . ' ';
        }
        if (!empty($dinner_id)) {
            if ($dinner_id !== "ALL") {
                $whereStr .= 'and t4.id =' . $dinner_id . ' ';
            }
        }
        if (!empty($department_id)) {
            if ($department_id !== "ALL") {
                $whereStr .= 'and t6.id =' . $department_id . ' ';
            }
        }
        if (strlen($apply_name)) {
            $whereStr .= 'and t5.username like' . '"%' . $apply_name . '%"' . ' ';
        }
        if (strlen($reception_code)) {
            $whereStr .= 'and t1.code_number like' . '"%' . $reception_code . '%"' . ' ';
        }
        if (!empty($reception_state)) {
            if ($reception_state !== "ALL") {
                $whereStr .= 'and t1.status =' . $reception_state . ' ';
            }
        }
        if ($whereStr !== '') {
            $sql = "select CONCAT(\"\t\", t2.code_number) as apply_code,CONCAT(\"\t\", t1.code_number) as reception_code,t3.name as canteen_name,t2.ordering_date,t4.name as dinner_name,t6.name as department_name,t5.username as apply_name,t2.money,(case when t1.`status` = 1 then '已使用' when t1.`status` = 2 then '未使用' when t1.`status` = 3 then '已取消' when t1.`status` = 4 then '已过期' end) as reception_state,(case when t1.status = 3 or t1.status = 4 then t1.update_time else COALESCE(t1.used_time,'') end) as used_time from canteen_reception_qrcode_t t1 left join canteen_reception_t t2 on t1.re_id = t2.id left join canteen_canteen_t t3 on t2.canteen_id = t3.id left join canteen_dinner_t t4 on t2.dinner_id=t4.id left join canteen_company_staff_t t5 on t2.staff_id = t5.id left join canteen_company_department_t t6 on t5.d_id = t6.id left join canteen_company_t t7 on t5.company_id = t7.id where 1=1 and t5.state=1 " . $whereStr . " order by t1.id desc ";
        } else {
            $sql = "select CONCAT(\"\t\", t2.code_number) as apply_code,CONCAT(\"\t\", t1.code_number) as reception_code,t3.name as canteen_name,t2.ordering_date,t4.name as dinner_name,t6.name as department_name,t5.username as apply_name,t2.money,(case when t1.`status` = 1 then '已使用' when t1.`status` = 2 then '未使用' when t1.`status` = 3 then '已取消' when t1.`status` = 4 then '已过期' end) as reception_state,(case when t1.status = 3 or t1.status = 4 then t1.update_time else COALESCE(t1.used_time,'') end) as used_time from canteen_reception_qrcode_t t1 left join canteen_reception_t t2 on t1.re_id = t2.id left join canteen_canteen_t t3 on t2.canteen_id = t3.id left join canteen_dinner_t t4 on t2.dinner_id = t4.id left join canteen_company_staff_t t5 on t2.staff_id = t5.id left join canteen_company_department_t t6 on t5.d_id = t6.id left join canteen_company_t t7 on t5.company_id = t7.id where 1=1 and t5.state =1 order by t1.id desc ";
        }
        $records = Db::query($sql);

        $header = ['申请编号', '接待票编号', '饭堂', '餐次日期', '餐次', '部门', '使用人', '金额', '状态', '消费时间/取消时间'];
        $file_name = "接待票统计表";
        $url = (new ExcelService())->makeExcel2($header, $records, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    private function receptionsForApplyOutput($data)
    {
        $ordering_date = $data['ordering_date'];
        $apply_name = $data['apply_name'];
        $apply_code = $data['apply_code'];
        $department_id = $data['department_id'];
        $company_id = $data['company_id'];
        $dinner_id = $data['dinner_id'];
        $canteen_id = $data['canteen_id'];
        $apply_state = $data['apply_state'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $whereStr = '';

        if (!empty($company_id)) {
            if ($company_id !== "ALL") {
                $whereStr .= 'and t5.id = ' . $company_id . ' ';
            }
        }
        if (!empty($canteen_id)) {
            $whereStr .= 'and t6.id = ' . $canteen_id . ' ';
        }
        if (strlen($ordering_date)) {
            $whereStr .= 'and t1.ordering_date = ' . "'$ordering_date'" . ' ';
        }
        if (!empty($dinner_id)) {
            if ($dinner_id !== "ALL") {
                $whereStr .= 'and t2.id = ' . $dinner_id . ' ';
            }
        }
        if (!empty($department_id)) {
            if ($department_id !== "ALL") {
                $whereStr .= 'and t4.id = ' . $department_id . ' ';
            }
        }
        if (strlen($apply_name)) {
            $whereStr .= 'and t3.username like' . '"%' . $apply_name . '%"' . ' ';
        }
        if (strlen($apply_code)) {
            $whereStr .= 'and t1.code_number like' . '"%' . $apply_code . '%"' . ' ';
        }
        if (!empty($apply_state)) {
            if ($apply_state !== "ALL") {
                $whereStr .= 'and t1.status = ' . $apply_state . ' ';
            }
        }
        if ($whereStr !== '') {
            $sql = "select CONCAT(\"\t\", t1.code_number) as apply_code,t1.create_time as apply_time,t1.ordering_date,t2.name as dinner_name, t4.name as department_name,t3.username as apply_name,t1.count,t1.money,sum(t1.count*t1.money) as sum,t1.remark,(case when t1.status = 1 then '审核中' when t1.status = 2 then '已生效' when t1.status = 3 then '审核不通过' when t1.status = 4 then '已撤销' end) as apply_state from canteen_reception_t t1 left join canteen_dinner_t t2 ON t1.dinner_id = t2.id left join canteen_company_staff_t t3 ON t1.staff_id = t3.id left join canteen_company_department_t t4 ON t3.d_id = t4.id left join canteen_company_t t5 ON t3.company_id = t5.id left join canteen_canteen_t t6 ON t1.canteen_id = t6.id where 1 = 1 and t3.state = 1 " . $whereStr . " group by  t1.code_number,t1.create_time,t1.ordering_date,t2.name,t4.name,t3.username,t1.count,t1.money,t1.remark,t1.status order by t1.create_time desc";
        } else {
            $sql = "select CONCAT(\"\t\", t1.code_number) as apply_code,t1.create_time as apply_time,t1.ordering_date,t2.name as dinner_name, t4.name as department_name,t3.username as apply_name,t1.count,t1.money,sum(t1.count*t1.money) as sum,t1.remark,(case when t1.status = 1 then '审核中' when t1.status = 2 then '已生效' when t1.status = 3 then '审核不通过' when t1.status = 4 then '已撤销' end) as apply_state from canteen_reception_t t1 left join canteen_dinner_t t2 ON t1.dinner_id = t2.id left join canteen_company_staff_t t3 ON t1.staff_id = t3.id left join canteen_company_department_t t4 ON t3.d_id = t4.id left join canteen_company_t t5 ON t3.company_id = t5.id left join canteen_canteen_t t6 ON t1.canteen_id = t6.id where 1 = 1 and t3.state = 1 group by  t1.code_number,t1.create_time,t1.ordering_date,t2.name,t4.name,t3.username,t1.count,t1.money,t1.remark,t1.status order by t1.create_time desc";
        }
        $records = Db::query($sql);

        $header = ['申请编号', '申请时间', '餐次日期', '餐次', '部门', '申请人', '数量', '金额', '合计', '申请原因', '状态'];
        $file_name = "接待票申请表";
        $url = (new ExcelService())->makeExcel2($header, $records, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    private function exportNextMonthPayStatistic($data)
    {
        $pay_method = $data['pay_method'];
        $status = $data['status'];
        $username = $data['username'];
        $department_id = $data['department_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $phone = $data['phone'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $statistic = (new NextMonthPayService())->nextMonthOutput($time_begin, $time_end,
            $company_id, $department_id, $status, $pay_method,
            $username, $phone);
        $header = ['序号', '时间', '部门', '姓名', '手机号码', '应缴费用', '缴费状态', '缴费时间', '缴费途径', '合计数量', '合计金额（元）', '备注'];
        $reports = (new NextMonthPayService())->prefixConsumptionStatistic($statistic);
        $file_name = "缴费查询报表";
        $url = (new ExcelService())->makeExcel2($header, $reports, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    private function exportFace($data)
    {
        $canteen_id = $data['canteen_id'];
        $dinner_id = $data['dinner_id'];
        $state = $data['state'];
        $name = $data['name'];
        $department_id = $data['department_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $phone = $data['phone'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $list = db('face_t')
            ->alias('t1')
            ->leftJoin('canteen_company_t t2', 't1.company_id = t2.id')
            ->leftJoin('canteen_canteen_t t3', 't1.canteen_id = t3.id')
            ->leftJoin('canteen_company_staff_t t4', 't1.staff_id = t4.id')
            ->leftJoin('canteen_company_department_t t5', 't4.d_id = t5.id')
            ->whereBetweenTime('t1.passDate', $time_begin, $time_end)
            ->where(function ($query) use ($name, $phone, $department_id, $state) {
                if (strlen($name)) {
                    $query->where('t4.username', 'like', '%' . $name . '%');
                }
                if (strlen($phone)) {
                    $query->where('t4.phone', 'like', '%' . $phone . '%');
                }
                if ($department_id != 0) {
                    $query->where('t5.id', $department_id);
                }
                if ($state != 0) {
                    $query->where('t1.temperatureResult', $state);
                }
            })
            ->where(function ($query) use ($company_id, $canteen_id, $dinner_id) {
                if ($dinner_id != 0) {
                    $query->where('t1.meal_id', $dinner_id);
                } else {
                    if ($canteen_id != 0) {
                        $query->where('t1.canteen_id', $canteen_id);
                    } else {
                        if ($company_id != 0) {
                            $query->where('t1.company_id', $company_id);
                        }
                    }
                }
            })
            ->field('t1.id,t1.passTime,t3.name as canteen_name,t1.meal_name,t5.name as department_name,t4.username,t4.phone,t1.temperature,(case when t1.temperatureResult = 1 then \'正常\' when t1.temperatureResult=2 then \'异常\' end) state')
            ->order('id asc')
            ->select();
        $dataList = [];
        if (count($list)) {
            foreach ($list as $k => $v) {
                array_push($dataList, [
                    'id' => $k + 1,
                    'passTime' => $v['passTime'],
                    'canteen_name' => $v['canteen_name'],
                    'meal_name' => $v['meal_name'],
                    'department_name' => $v['department_name'],
                    'username' => $v['username'],
                    'phone' => $v['phone'],
                    'temperature' => $v['temperature'],
                    'state' => $v['state']
                ]);
            }
        }
        $header = ['序号', '检测时间', '检测地点', '餐次', '部门', '姓名', '手机号码', '体温', '状态'];
        $file_name = "体温检测报表";
        $url = (new ExcelService())->makeExcel2($header, $data, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    public function exportTakeoutStatistic($data)
    {
        $canteen_id = $data['canteen_id'];
        $dinner_id = $data['dinner_id'];
        $ordering_date = $data['ordering_date'];
        $department_id = $data['department_id'];
        $status = $data['status'];
        $user_type = $data['user_type'];
        $company_ids = $data['company_id'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $records = OrderTakeoutStatisticV::exportStatistic($ordering_date,
            $company_ids, $canteen_id, $dinner_id, $status, $department_id,
            $user_type);
        $records = (new OrderStatisticServiceV1())->prefixExportTakeoutStatistic($records);
        $header = ['订餐号', '日期', '消费地点', '姓名', '手机号', '餐次', '金额（元）', '送货地点', '状态'];
        $file_name = $ordering_date . "-外卖管理报表";
        $url = (new ExcelService())->makeExcel2($header, $records, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    private function exportConsumptionStatistic($data)
    {
        $canteen_id = $data['canteen_id'];
        $status = $data['status'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $username = $data['username'];
        $staff_type_id = $data['staff_type_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $phone = $data['phone'];
        $order_type = $data['order_type'];
        $version = $data['version'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $locationName = (new  OrderStatisticServiceV1())->getLocationName($order_type, $canteen_id);
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

        switch ($data['type']) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_USERNAME:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, 1, 10000, $version);
                break;
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_CANTEEN:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_STATUS:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
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
        if (!$canteen_id) {
            $dinner = DinnerV::companyDinners2($company_id);
        } else {
            $dinner = DinnerT::dinnerNames($canteen_id);
        }
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
        $header = (new  OrderStatisticServiceV1())->addDinnerAndAccountToHeader($header, $dinner);
        $reports = (new  OrderStatisticServiceV1())->prefixConsumptionStatistic($statistic, $dinner, $time_begin, $time_end);
        $reportName = $fileNameArr[$status];
        $file_name = $reportName . "(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel2($header, $reports, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    private function exportConsumptionStatisticWithAccount($data)
    {
        $canteen_id = $data['canteen_id'];
        $status = $data['status'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $username = $data['username'];
        $staff_type_id = $data['staff_type_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $phone = $data['phone'];
        $order_type = $data['order_type'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $locationName = (new  OrderStatisticServiceV1())->getLocationName($order_type, $canteen_id);
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
        switch ($data['type']) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_USERNAME:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, 1, 10000, $version);
                break;
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_CANTEEN:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_STATUS:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            default:
                throw new ParameterException();
        }
        if ($type == OrderEnum::STATISTIC_BY_USERNAME) {
            $statistic = $info['consumptionRecords']['data'];
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


        $header = (new  OrderStatisticServiceV1())->addDinnerAndAccountToHeader($header, $dinner, $accounts);
        $reports = (new  OrderStatisticServiceV1())->prefixConsumptionStatisticWithAccount($statistic, $accountRecords, $accounts, $dinner, $time_begin, $time_end);
        $reportName = $fileNameArr[$status];
        $file_name = $reportName . "(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel2($header, $reports, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);
    }

    private function exportOrderStatisticDetail($data)
    {
        $canteen_id = $data['canteen_id'];
        $dinner_id = $data['dinner_id'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $name = $data['name'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_ids = $data['company_ids'];
        $phone = $data['phone'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $list = OrderStatisticV::exportDetail($company_ids, $time_begin,
            $time_end, $name,
            $phone, $canteen_id, $department_id,
            $dinner_id, $type);
        $list = (new OrderStatisticServiceV1())->prefixOrderStatisticDetail($list);
        $header = ['订单ID', '订餐日期', '消费地点', '部门', '姓名', '号码', '餐次', '订餐类型', '份数', '金额', '订餐状态', '明细', '合计'];
        $file_name = "订餐明细报表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel2($header, $list, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    private function exportOrderSettlement($data)
    {
        $canteen_id = $data['canteen_id'];
        $dinner_id = $data['dinner_id'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $name = $data['name'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_ids = $data['company_id'];
        $phone = $data['phone'];
        $consumption_type = $data['consumption_type'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $records = OrderSettlementV::exportOrderSettlement(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        $records = (new OrderStatisticServiceV1())->prefixExportOrderSettlement($records);
        $header = ['序号', '消费日期', '消费时间', '部门', '姓名', '手机号', '消费地点', '消费类型', '餐次', '金额', '备注'];
        $file_name = "消费明细报表（" . $time_begin . "-" . $time_end . "）";
        $url = (new ExcelService())->makeExcel2($header, $records, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }

    private function exportOrderSettlementWithAccount($data)
    {
        $canteen_id = $data['canteen_id'];
        $dinner_id = $data['dinner_id'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $name = $data['name'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_ids = $data['company_id'];
        $phone = $data['phone'];
        $consumption_type = $data['consumption_type'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $records = OrderSettlementV::exportOrderSettlementWithAccount(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        $records = (new OrderStatisticServiceV1())->prefixExportOrderSettlementWithAccount($records);
        $header = ['序号', '消费日期', '消费时间', '部门', '姓名', '手机号', '消费地点', '账户名称', '消费类型', '餐次', '金额', '备注'];
        $file_name = "消费明细报表（" . $time_begin . "-" . $time_end . "）";
        $url = (new ExcelService())->makeExcel2($header, $records, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);
    }

    public function exportOrderStatistic($data)
    {

        $canteen_id = $data['canteen_id'];
        $company_ids = $data['company_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $downId = $data['down_id'];
        $SCRIPT_FILENAME = $data['SCRIPT_FILENAME'];
        $list = OrderStatisticV::exportStatistic($time_begin, $time_end, $company_ids, $canteen_id);
        $header = ['日期', '公司', '消费地点', '餐次', '订餐份数'];
        $file_name = "订餐统计报表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel2($header, $list, $file_name, $SCRIPT_FILENAME);
        $url = config('setting.domain') . $url;
        $this->saveExcel($downId, $url, $file_name);

    }


    private function saveExcel($downId, $url, $file_name)
    {
        $excel = DownExcelT::get($downId);
        $excel->status = DownEnum::DOWN_SUCCESS;
        $excel->name = $file_name;
        $excel->url = $url;
        $excel->save();

        $sendData = [
            'type' => 'down_excel',
            'file_name' => $file_name,
            'url' => $url
        ];
        GatewayService::sendToMachine($excel->admin_id, json_encode($sendData));

    }


}