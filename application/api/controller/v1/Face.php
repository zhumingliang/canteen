<?php


namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\model\OfficialTemplateT;
use app\api\service\ExcelService;
use app\api\service\LogService;
use app\lib\enum\CommonEnum;
use app\lib\exception\AuthException;
use app\lib\exception\SuccessMessageWithData;
use app\lib\weixin\Template;
use think\Db;
use think\Exception;
use think\facade\Request;

class Face extends BaseController
{
    /**
     * 接收人脸机上传的数据
     */
    public function receiveFaceData()
    {
        try {
            $params = Request::param();
            $deviceNo = $params['deviceNo'];
            $passTime = $params['passTime'];
            $canteenInfo = db('machine_t')
                ->alias('t1')
                ->leftJoin('canteen_canteen_t t2', 't1.belong_id = t2.id')
                ->where('face_id', $deviceNo)
                ->where('t1.state', CommonEnum::STATE_IS_OK)
                ->field('belong_id,t2.name,company_id')
                ->find();
            if (empty($canteenInfo)) {
                $data = ['errorCode' => '200', 'msg' => '饭堂设备未关联人脸机'];
            }
            $canteen_id = $canteenInfo['belong_id'];
            $canteen_name = $canteenInfo['name'];
            $company_id = $canteenInfo['company_id'];
            $staffID = db('company_staff_t')
                ->alias('t1')
                ->leftJoin('canteen_company_department_t t2', 't1.d_id = t2.id')
                ->where('phone', $params['phone'])
                ->where('company_id', $company_id)
                ->where('t1.state', CommonEnum::STATE_IS_OK)
                ->field('t1.id,t2.name')
                ->find();
            if (empty($staffID)) {
                $staff_id = 0;
            } else {
                $staff_id = $staffID['id'];
                $department = $staffID['name'];
            }
            Db::query('call canteenGetMeal(:canteen_id,:time,
                @result,@meal_id,@meal_name)',
                [
                    'canteen_id' => $canteen_id,
                    'time' => date('H:i:s', strtotime($passTime))
                ]);
            $resultSet = Db::query('select @result,@meal_id,@meal_name');
            $meal_id = $resultSet[0]['@meal_id'];
            $meal_name = $resultSet[0]['@meal_name'];
            $data = [
                'deviceNo' => $params['deviceNo'],
                'snapType' => $params['snapType'],
                'compareResult' => $params['compareResult'],
                'passTime' => $params['passTime'],
                'passDate' => date('Y-m-d', strtotime($passTime)),
                'idNumber' => $params['idNumber'],
                'name' => $params['name'],
                'company' => $params['company'],
                'department' => $params['department'],
                'phone' => $params['phone'],
                'recognitionPhoto' => $params['recognitionPhoto'],
                'temperature' => $params['temperature'],
                'temperatureResult' => $params['temperatureResult'],
                'normalNumber' => $params['normalNumber'],
                'meal_id' => $meal_id,
                'meal_name' => $meal_name,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
                'canteen_id' => $canteen_id,
                'company_id' => $company_id,
                'staff_id' => $staff_id
            ];
            db('face_t')
                ->data($data)
                ->insert();
            $templateData = OfficialTemplateT::template('temperature');
            if ($params['temperature'] >= $templateData['remark']) {
                $midInfo = db('canteen_module_t')
                    ->alias('t1')
                    ->leftJoin('canteen_system_canteen_module_t t2', 't1.m_id = t2.id')
                    ->where('t1.c_id', $company_id)
                    ->where('t1.state', CommonEnum::STATE_IS_OK)
                    ->where('t2.state', CommonEnum::STATE_IS_OK)
                    ->where('t2.name', '体温检测通知')
                    ->field('t1.id as id')
                    ->find();
                if (!empty($midInfo)) {
                    $mid = $midInfo['id'];
                    $adminInfo = db('admin_t')
                        ->alias('t1')
                        ->leftJoin('canteen_admin_module_t t2', 't1.id = t2.admin_id')
                        ->where('t1.state', CommonEnum::STATE_IS_OK)
                        ->where('t1.c_id', $company_id)
                        ->where('find_in_set(:id,t2.rules)', ['id' => $mid])
                        ->field('phone')
                        ->select();
                    if (!empty($adminInfo)) {
                        foreach ($adminInfo as $k) {
                            $openidInfo = db('user_t')
                                ->where('phone', $k['phone'])
                                ->where('current_company_id', $company_id)
                                ->field('openid')
                                ->find();
                            if (!empty($openidInfo)) {
                                $res = $this->sendRefundTemplate($openidInfo['openid'], $department, $params['name'], $canteen_name, date('Y-m-d H:i:s', strtotime($passTime)), $params['temperature']);
                                if ($res['errcode'] != 0) {
                                    LogService::save(json_encode($res));
                                }
                            }
                        }
                    }
                }
            }
            $data = ['errorCode' => '100', 'msg' => '操作成功'];

        } catch (Exception $e) {
            $this->pr_log($e, '报错');
            $data = ['errorCode' => '200', 'msg' => '调用失败'];
        }
        return json($data);
    }

    /* 毫秒时间戳转换成日期 */
    private function msecdate($time)
    {
        $tag = 'Y-m-d H:i:s';
        $a = substr($time, 0, 10);
        $b = substr($time, 10);
        $date = date($tag, $a) . '.' . $b;
        return $date;
    }

    /**
     *查询人脸体温检测报表
     */
    public function getFaceData($page = 1, $size = 10, $name = '',
                                $phone = '',
                                $canteen_id = 0,
                                $department_id = 0,
                                $dinner_id = 0,
                                $state = 0
    )
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_id = Request::param('company_id');
        $data = $this->getFace($page, $size, $name, $phone, $canteen_id,
            $department_id, $dinner_id, $time_begin, $time_end, $company_id, $state);
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     *导出人脸体温检测报表
     */
    public function exportFaceData($name = '',
                                   $phone = '',
                                   $canteen_id = 0,
                                   $department_id = 0,
                                   $dinner_id = 0,
                                   $state = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_id = Request::param('company_id');
        $data = $this->exportFace($name, $phone, $canteen_id,
            $department_id, $dinner_id, $time_begin, $time_end, $company_id, $state);
        $header = ['序号', '检测时间', '检测地点', '餐次', '部门', '姓名', '手机号码', '体温', '状态'];
        $file_name = "体温检测报表";
        $url = (new ExcelService())->makeExcel($header, $data, $file_name);
        $data = ['url' => 'http://' . $_SERVER['HTTP_HOST'] . $url];
        return json(new SuccessMessageWithData(['data' => $data]));
    }


    private function getFace($page, $size, $name, $phone, $canteen_id, $department_id, $dinner_id, $time_begin, $time_end, $company_id, $state)
    {
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
                }
                if ($canteen_id != 0) {
                    $query->where('t1.canteen_id', $canteen_id);
                }
                if ($company_id != 0) {
                    $query->where('t1.company_id', $company_id);
                }
            })
            ->field('t1.id,t1.passTime,t2.name as company_name,t3.name as canteen_name,t1.meal_name,t4.username,t4.phone,t5.name as department_name,t1.temperature,(case when t1.temperatureResult = 1 then \'正常\' when t1.temperatureResult=2 then \'异常\' end) state')
            ->order('id asc')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

    private function exportFace($name, $phone, $canteen_id, $department_id, $dinner_id, $time_begin, $time_end, $company_id, $state)
    {
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
        return $dataList;
    }

    /**
     * 发送微信模板消息
     */
    private function sendRefundTemplate($openid, $department, $username, $canteen_name, $passTime, $temperature)
    {
        $data = [
            'first' => $department . $username . '于' . $passTime . '在' . $canteen_name . "检测体温异常，具体如下：",
            'keyword1' => $temperature . '°C',
            'keyword2' => '体温异常',
            'remark' => "建议您进一步确认该用户的体温情况。"
        ];
        $templateConfig = OfficialTemplateT::template('temperature');
        if ($templateConfig) {
            $res = (new Template())->send($openid, $templateConfig->template_id, $templateConfig->url, $data);
            if ($res['errcode'] != 0) {
                LogService::save(json_encode($res));
            }
        }
    }

    /**
     * [ 写入日志 -简约]
     * @param array,string  $log_content [内容]
     * @param string $keyp [文件名]
     */
    private function pr_log($log_content, $keyp)
    {
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/logs';
        $log_filename = $path . date("Ymd");
        !is_dir($log_filename) && mkdir($log_filename, 0755, true);
        if (is_array($log_content)) {
            $log_content = $this->JSONReturn($log_content);
        }
        file_put_contents($log_filename . date("d"), '[' . date("Y-m-d H:i:s") . ']' . PHP_EOL . $log_content . PHP_EOL . PHP_EOL, FILE_APPEND);
    }

    private function JSONReturn($result)
    {
        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}