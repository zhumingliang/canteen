<?php


namespace app\api\job;


use app\api\model\DinnerT;
use app\api\model\MaterialOrderT;
use app\api\model\NoticeUserT;
use app\api\model\RechargeCashT;
use app\api\model\RechargeSupplementT;
use app\api\service\CanteenService;
use app\api\service\DepartmentService;
use app\api\service\ExcelService;
use app\api\service\LogService;
use app\api\service\MaterialService;
use app\api\service\Token;
use app\api\service\WalletService;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;
use think\exception\ErrorException;
use think\queue\Job;
use zml\tp_tools\Redis;

class UploadExcel
{

    private $orderOnline = "online";
    private $orderChoice = "choice";

    /**
     * fire方法是消息队列默认调用的方法
     * @param Job $job 当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if (!$isJobStillNeedToBeDone) {
            $this->clearUploading($data['company_id'], $data['u_id'], $data['type']);
            $job->delete();
            return;
        }
        //执行excel导入
        $isJobDone = $this->doJob($data);
        if ($isJobDone) {
            // 如果任务执行成功，删除任务
            // $code = $data['company_id'] . ":" . $data['u_id'] . ":" . $data['type'];
            $code = $data['company_id'] . ":" . $data['type'];
            // $this->clearUploading($data['company_id'], $data['u_id'], $data['type']);
            LogService::saveJob("<warn>导入Excel任务执行成功！编号：$code" . "</warn>\n");
            $job->delete();
        } else {
            $job->delete();
            LogService::saveJob("<warn>导入Excel任务执行失败" . "</warn>\n");

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

            $type = $data['type'];
            if ($type == "rechargeCash") {
                return $this->uploadRechargeCash($data);
            } else if ($type == "rechargeCashWithAccount") {
                return $this->uploadRechargeCashWithAccount($data);
            } else if ($type == "supplement") {
                return $this->uploadSupplement($data);
            } else if ($type == "supplementWithAccount") {
                return $this->uploadSupplementWithAccount($data);
            } else if ($type == "staff") {
                return $this->uploadStaff($data);
            } else if ($type == "orderMaterial") {
                return $this->uploadOrderMaterial($data);
            }
            return true;
        } catch (Exception $e) {
            LogService::saveJob("上传excel失败：error:" . $e->getMessage(), json_encode($data));
            return false;
        }

    }

    public function uploadOrderMaterial($data)
    {
        $companyId = $data['company_id'];
        $day = date('Y-m-d');
        $fileName = $data['fileName'];
        $data = (new ExcelService())->importExcel($fileName);
        $canteens = (new CanteenService())->companyCanteens($companyId);
        $canteenArr = [];
        foreach ($canteens as $k => $v) {
            $canteenArr[$v['name']] = $v['id'];
        }
        $dataList = [];
        foreach ($data as $k => $v) {
            if ($k < 2) {
                continue;
            }
            $canteen = $v[0];
            $material = $v[1];
            $count = empty($v[2]) ? 0 : $v[2];
            $price = empty($v[3]) ? 0 : $v[3];
            $canteenId = $canteenArr[$canteen];
            $type = (new MaterialService())->checkCanteenOrderType($canteenId);
            if ($type == $this->orderOnline) {
                //检测材料是否已经新增
                array_push($dataList, [
                    'company_id' => $companyId,
                    'canteen_id' => $canteenId,
                    'material' => $material,
                    'day' => $day,
                    'count' => $count,
                    'price' => $price,
                    'status' => CommonEnum::STATE_IS_OK,
                    'type' => $type
                ]);

            } else if ($type == $this->orderChoice) {
                //获取所有餐次
                $dinners = DinnerT::dinners($canteenId);
                if ($dinners) {
                    foreach ($dinners as $k2 => $v2) {
                        array_push($dataList, [
                            'company_id' => $companyId,
                            'canteen_id' => $canteenId,
                            'dinner_id' => $v2['id'],
                            'material' => $material,
                            'day' => $day,
                            'count' => $count,
                            'price' => $price,
                            'status' => CommonEnum::STATE_IS_FAIL,
                            'type' => $type
                        ]);

                    }
                }

            }
        }
        $res = (new MaterialOrderT())->saveAll($dataList);
        if (!$res) {
            return false;
        }
        return true;
    }

    public
    function uploadStaff($data)
    {
        $company_id = $data['company_id'];
        $fileName = $data['fileName'];
        $staffs = (new DepartmentService())->uploadStaff($company_id, $fileName);
        if (!$staffs) {
            return false;
        }
        return true;
    }

    public
    function uploadSupplement($data)
    {
        $company_id = $data['company_id'];
        $admin_id = $data['u_id'];
        $fileName = $data['fileName'];
        $data = (new ExcelService())->importExcel($fileName);
        $dataList = (new WalletService())->prefixSupplementUploadData($company_id, $admin_id, $data);
        $cash = (new RechargeSupplementT())->saveAll($dataList);
        if (!$cash) {
            return false;
        }
        return true;
    }

    public
    function uploadSupplementWithAccount($data)
    {
        $company_id = $data['company_id'];
        $admin_id = $data['u_id'];
        $fileName = $data['fileName'];
        $data = (new ExcelService())->importExcel($fileName);
        $dataList = (new WalletService())->prefixSupplementUploadDataWithAccount($company_id, $admin_id, $data);
        $cash = (new RechargeSupplementT())->saveAll($dataList);
        if (!$cash) {
            return false;
        }
        return true;
    }

    public
    function uploadRechargeCashWithAccount($data)
    {
        $company_id = $data['company_id'];
        $admin_id = $data['u_id'];
        $fileName = $data['fileName'];
        $data = (new ExcelService())->importExcel($fileName);
        $dataList = (new WalletService())->prefixUploadDataWithAccount($company_id, $admin_id, $data);
        $cash = (new RechargeCashT())->saveAll($dataList);
        if (!$cash) {
            return false;
        }
        return true;
    }

    public
    function uploadRechargeCash($data)
    {
        $company_id = $data['company_id'];
        $admin_id = $data['u_id'];
        $fileName = $data['fileName'];
        $data = (new ExcelService())->importExcel($fileName);
        $dataList = (new WalletService())->prefixUploadData($company_id, $admin_id, $data);
        $cash = (new RechargeCashT())->saveAll($dataList);
        if (!$cash) {
            return false;
        }
        return true;
    }

    public
    function clearUploading($company_id, $u_id, $type)
    {

        try {
            $set = "uploadExcel";
            $code = "$company_id:$u_id:$type";
            Redis::instance()->sRem($set, $code);
        } catch (Exception $e) {
            LogService::save('clear:' . $e->getMessage());

        }


    }

}