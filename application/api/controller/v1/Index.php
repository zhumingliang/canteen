<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\job\DownExcel;
use app\api\job\SendTemplate;
use app\api\job\UploadExcel;
use app\api\model\AccountRecordsT;
use app\api\model\AutomaticT;
use app\api\model\CanteenT;
use app\api\model\CompanyAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\CompanyT;
use app\api\model\ConsumptionLogT;
use app\api\model\ConsumptionRecordsV;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\model\FoodDayStateT;
use app\api\model\LogT;
use app\api\model\OfficialTemplateT;
use app\api\model\OrderConsumptionV;
use app\api\model\OrderingV;
use app\api\model\OrderParentT;
use app\api\model\OrderPrepareFoodT;
use app\api\model\OrderPrepareT;
use app\api\model\OrderSubT;
use app\api\model\OrderT;
use app\api\model\OrderUnusedV;
use app\api\model\PayNonghangConfigT;
use app\api\model\PayT;
use app\api\model\PayWxT;
use app\api\model\PunishmentStrategyT;
use app\api\model\RechargeCashT;
use app\api\model\RechargeSupplementT;
use app\api\model\RechargeV;
use app\api\model\StaffCardT;
use app\api\model\StaffQrcodeT;
use app\api\model\UserBalanceV;
use app\api\service\AccountService;
use app\api\service\AddressService;
use app\api\service\CanteenService;
use app\api\service\CompanyService;
use app\api\service\ConsumptionService;
use app\api\service\DepartmentService;
use app\api\service\ExcelService;
use app\api\service\FoodService;
use app\api\service\GatewayService;
use app\api\service\LogService;
use app\api\service\NextMonthPayService;
use app\api\service\NoticeService;
use app\api\service\OrderService;
use app\api\service\OrderStatisticService;
use app\api\service\QrcodeService;
use app\api\service\SendSMSService;
use app\api\service\ShopService;
use app\api\service\TakeoutService;
use app\api\service\Token;
use app\api\service\WalletService;
use app\api\service\WeiXinService;
use app\lib\Date;
use app\lib\enum\CommonEnum;
use app\lib\enum\FoodEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\enum\StrategyEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\Num;
use app\lib\printer\Printer;
use app\lib\weixin\Template;
use GatewayClient\Gateway;
use think\captcha\Captcha;
use think\Db;
use think\db\Where;
use think\Exception;
use think\facade\Env;
use think\facade\Request;
use think\Queue;
use zml\tp_tools\Aes;
use zml\tp_tools\Redis;
use function GuzzleHttp\Psr7\str;

class
Index extends BaseController
{
    /** @var string 任务周期 */
    public $expression = '* * * * * *';

    public function index()
    {


        // return json(new SuccessMessage());
        /*   $accounts = (new AccountService())
               ->getAccountBalance(95, $staffId, $staff->d_id);*/

        $data = Request::param();
        $data['SCRIPT_FILENAME'] = '';
        $data['down_id'] = '';
        $data['version'] = 'v1';
        (new DownExcel())->exportConsumptionStatistic($data);
        //  (new WalletService())->checkSupplementData(144, dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/upload/test.xlsx');
        /* $data['company_id'] = 134;
         $data['u_id'] = 1;
         $data['fileName'] = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/upload/test.xlsx';

         (new UploadExcel())->uploadSupplement($data);*/

        //  (new OrderT())->saveAll($data3);

        /*     $orders = OrderT::where('company_id', 122)
                 ->where('id', '>', 203133)
                 ->where('id', '<', 214781)
                 ->where('used', CommonEnum::STATE_IS_FAIL)
                 ->select();
             $data = [];
             foreach ($orders as $k => $v) {
                 if ($v['used'] == 1) {
                     array_push($data, [
                         'id' => $v['id'],
                         'consumption_type' => "no_meals_ordered",
                         'money' => $v['no_meal_money'],
                         'sub_money' => $v['no_meal_sub_money']
                     ]);
                 }

             }

             (new OrderT())->saveAll($data);*/

        /*$data = [
            'company_ids' => "69,82,99,100,103,110,106,107",
            'canteen_id' => 0,
            'user_type' => 1,
            'status' => 6,
            'phone' => "",
            'department_id' => "",
            'dinner_id' => "",
            'time_begin' => "2021-02-01",
            'ordering_date' => "2021-03-30",
            'down_id' => 86,
            'SCRIPT_FILENAME' => "/www/wwwroot/test-api.51canteen.cn/canteen/public/index.php"
        ];
        (new DownExcel())->exportTakeoutStatistic($data);*/
        /*if ($type == 1) {
            $adminId = 1;
            $group = 'canteen:admin';
            Gateway::joinGroup($client_id, $group);
            Gateway::bindUid($client_id, $adminId);

        } else if ($type == 2) {
            $data = [
                'type' => 'down_excel',
                'file_name' => '111',
                'url' => "http://"
            ];
            $data = json_encode($data);
            GatewayService::sendToMachine(1, $data);
        }*/

        /* $a = [];
         $pay = PayWxT::where('id', '>', 1652)->select();
         foreach ($pay as $k => $v) {
             if (!in_array($v['out_trade_no'], $a)) {
                 array_push($a, $v['out_trade_no']);
                 PayT::update([
                     'pay' => PayEnum::PAY_SUCCESS
                 ], [
                     'order_num' => [
                         $v['out_trade_no']
                     ]

                 ]);
             }

         }
         $b = implode(',', $a);*/


        $day = date('Y-m-d H:i:s', strtotime('+30 minute',
            time()));
        $page = Request::param('page');
        $size = 100;
        $data = CompanyStaffT::where('company_id', 150)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->paginate($size, false, ['page' => $page])->toArray();

        $staffs = $data['data'];
        foreach ($staffs as $k => $v) {
            StaffQrcodeT::update([
                'minute' => 30,
                'expiry_date' => $day
            ], ['s_id' => $v['id']]
            );
        }

    }

    private function prefixOffLine($companyId, $canteenId, $offlineData)
    {

        $success = [];
        $fail = [];

        $mealedOrder = $offlineData['mealedOrder'];
        $noBookingOrder = $offlineData['noBookingOrder'];
        if (count($mealedOrder)) {
            foreach ($mealedOrder as $k => $v) {
                $machineId = $v['machineId'];
                $orderId = $v['orderId'];
                $usedTime = $v['usedTime'];
                $strategyType = $v['strategyType'];
                $res = self::prefixOfflineConsumption($companyId, $canteenId, $orderId, $strategyType, 0, 0, $usedTime);
                if ($res['msg'] != "success") {
                    array_push($fail, ['machineId' => $machineId,
                        'errorMsg' => $res['msg']]);
                } else {
                    array_push($success, $machineId);
                }

            }

        }
        if (count($noBookingOrder)) {
            foreach ($noBookingOrder as $k => $v) {
                $machineId = $v['machineId'];
                $staffId = $v['staffId'];
                $dinnerId = $v['dinnerId'];
                $usedTime = $v['usedTime'];
                $strategyType = $v['strategyType'];
                $res = self::prefixOfflineConsumption($companyId, $canteenId, 0, $strategyType, $staffId, $dinnerId, $usedTime);
                if ($res['msg'] != "success") {
                    array_push($fail, ['machineId' => $machineId,
                        'errorMsg' => $res['msg']]);
                } else {
                    array_push($success, $machineId);
                }

            }
        }

        $returnData = [
            'success' => $success,
            'fail' => $fail
        ];

    }

    private static function prefixOfflineConsumption($companyId, $canteenId, $orderId, $strategyType, $staffId, $dinnerId, $usedTime)
    {


        $sql = " call canteenOfflineConsumption (%s,%s,%s,'%s' ,%s ,%s ,'%s',@resCode,@resMessage);";
        $sql = sprintf($sql, $orderId, $canteenId, $companyId, $strategyType, $staffId, $dinnerId, $usedTime);
        $sql2 = "select @resCode,@resMessage";
        Db::query($sql);

        $resultSet = Db::query($sql2);
        print_r($resultSet);
        $errorCode = $resultSet[0]['@resCode'];
        $resMessage = $resultSet[0]['@resMessage'];
        return [
            'code' => $errorCode,
            'msg' => $resMessage
        ];
    }


    public function autoUpFoods()
    {
        try {
            $nextDay = (new FoodService())->getNextAuto(3, 4, "2021-02-23");
            print_r($nextDay);

            /*  //查询出今日需要处理的自动上架
              $w = date('w');
              $auto = AutomaticT::auto2($w);
              print_r($auto);
              if (count($auto)) {
                  foreach ($auto as $k => $v) {
                      $repeatWeek = $v['repeat_week'];
                      $repeatDay = $this->getRepeatDay($repeatWeek);
                    //  $this->upAll($v, $repeatDay);
                  }
              }*/
        } catch (\Exception $e) {
            echo $e->getMessage();


        }


    }

    public function upAll($auto, $day)
    {
        $canteenId = $auto['canteen_id'];
        $dinnerId = $auto['dinner_id'];
        $foodDay = FoodDayStateT::FoodStatus($canteenId, $dinnerId, $day);
        $foodList = [];
        $alreadyFoods = [];
        $cancelFoods = [];
        if (count($foodDay)) {
            foreach ($foodDay as $k => $v) {
                if (in_array($v['f_id'], $alreadyFoods) || in_array($v['f_id'], $cancelFoods)) {
                    continue;
                }
                if ($v['status'] != FoodEnum::STATUS_DOWN) {
                    array_push($foodList, [
                        'id' => $v['id'],
                        'status' => FoodEnum::STATUS_UP
                    ]);
                    array_push($alreadyFoods, $v['f_id']);

                } else {
                    array_push($cancelFoods, $v['f_id']);

                }
            }
        }

        print_r($cancelFoods);

        if ($auto) {
            if (!count($auto['foods'])) {
                throw new ParameterException(['msg' => "自动上架菜品未设置"]);
            }
            $autoFoods = $auto['foods'];
            foreach ($autoFoods as $k => $v) {
                if (in_array($v['food_id'], $alreadyFoods) || in_array($v['food_id'], $cancelFoods)) {
                    continue;
                } else {
                    array_push($foodList, [
                        'f_id' => $v['food_id'],
                        'status' => FoodEnum::STATUS_UP,
                        'day' => $day,
                        'user_id' => 0,
                        'canteen_id' => $canteenId,
                        'default' => CommonEnum::STATE_IS_FAIL,
                        'dinner_id' => $dinnerId
                    ]);
                    array_push($alreadyFoods, $v['food_id']);

                }

            }
        }

        print_r($foodList);

        /* if (count($foodList)) {
             $save = (new FoodDayStateT())->saveAll($foodList);
             if (!$save) {
                 throw new SaveException(['msg' => '上架失败']);
             }
         }*/

    }


    private function getRepeatDay($repeatWeek)
    {
        $w = date('w') == 0 ? 7 : date('w');
        $repeatWeek = $repeatWeek == 0 ? 7 : $repeatWeek;
        return addDay(7 + ($repeatWeek - $w), \date('Y-m-d'));

    }


    // $cash = (new RechargeSupplementT())->saveAll($dataList);
    /*$company = CompanyT::where('state', CommonEnum::STATE_IS_OK)->select();
    $account = [];
    foreach ($company as $k => $v) {
        $data = [
            'company_id' => $v['id'],
            'type' => 1,
            'department_all' => 1,
            'name' => '个人账户',
            'fixed_type' => 1,
            'clear' => CommonEnum::STATE_IS_FAIL,
            'sort' => 1,
            'state' => CommonEnum::STATE_IS_OK
        ];
        array_push($account, $data);
    }
    $nonghang = PayNonghangConfigT::
    where('state', CommonEnum::STATE_IS_OK)->select();
    foreach ($nonghang as $k => $v) {
        $data = [
            'company_id' => $v['company_id'],
            'type' => 1,
            'department_all' => 1,
            'name' => '农行账户',
            'fixed_type' => 2,
            'clear' => CommonEnum::STATE_IS_FAIL,
            'sort' => 2,
            'state' => CommonEnum::STATE_IS_OK
        ];
        array_push($account, $data);
    }

    (new CompanyAccountT())->saveAll($account);*/


    public
    function test($param = "")
    {


        /*   echo UserBalanceV::userBalance(94,'13822329629');
          // print_r(UserBalanceV::userBalance2(5637)) ;
           echo UserBalanceV::userBalance2(5549);*/

        /*  $phone = "13702717833";
          $dinner = [155, 156];
          foreach ($dinner as $k => $v) {
              $dinnerId = $v;
              $dateExits = [];
              $parent = OrderParentT::where('phone', $phone)
                  ->where('dinner_id', $dinnerId)
                  ->where('ordering_date', '>=', "2020-12-01")
                  ->where('state', CommonEnum::STATE_IS_OK)
                  ->order('ordering_date')
                  ->select()->toArray();
              foreach ($parent as $k2 => $v2) {
                  $orderingDate = $v2['ordering_date'];
                  if (in_array($orderingDate, $dateExits)) {
                      //   OrderParentT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $v2['id']]);
                      //  OrderSubT::update(['state' => CommonEnum::STATE_IS_FAIL], ['order_id' => $v2['id']]);
                  } else {
                      array_push($dateExits, $orderingDate);
                  }

              }

          }*/

    }

    public
    function token()
    {
        return json(\app\api\service\Token::getCurrentTokenVar());

    }


    public
    function clearAccounts()
    {

        Db::startTrans();
        try {
            //获取需要清除余额的账户
            $account = CompanyAccountT::clearAccounts();

            if (!count($account)) {
                return true;
            }
            foreach ($account as $k => $v) {
                $accountId = $v['id'];
                if ($accountId != 208) {
                    continue;
                }
                //检测是否清零时间
                if (!$this->checkClearTime($v['next_time'])) {
                    continue;
                }
                $clearData = [];
                //获取账户所有用户的余额
                $staffBalance = AccountRecordsT::staffBalance($accountId);
                if (!count($staffBalance)) {
                    continue;
                }
                foreach ($staffBalance as $k2 => $v2) {
                    if (abs($v2['money']) > 0) {
                        array_push($clearData, [
                            'account_id' => $accountId,
                            'company_id' => $v2['company_id'],
                            'consumption_date' => date('Y-m-d'),
                            'location_id' => 0,
                            'used' => CommonEnum::STATE_IS_OK,
                            'status' => CommonEnum::STATE_IS_OK,
                            'staff_id' => $v2['staff_id'],
                            'type' => 'clear',
                            'order_id' => 0,
                            'money' => 0 - $v2['money'],
                            'outsider' => 2,
                            'type_name' => "到期清零"
                        ]);
                    }

                }
                if (count($clearData)) {
                    (new AccountRecordsT())->saveAll($clearData);
                }
                //更新清零时间
                $nextTime = $this->getNextClearTime($v['clear_type'],
                    $v['first'], $v['end'],
                    $v['day_count'], $v['time_begin']);
                echo $nextTime;
                CompanyAccountT::update(['next_time' => $nextTime], ['id' => $accountId]);
            }

            // Db::commit();
        } catch (\Exception $e) {
            echo $e->getMessage();
            Db::rollback();
        }
    }

    private
    function checkClearTime($nextTime)
    {
        return true;
        $now = strtotime(date('Y-m-d H:i'));
        $nextTime = strtotime(date('Y-m-d H:i', strtotime($nextTime)));
        if ($now == $nextTime) {
            echo 1;
            return true;
        }
        return false;

    }

    private
    function getNextClearTime($clearType, $first, $end, $dayCount, $time_begin)
    {
        if ($clearType == "day") {
            return addDay($dayCount, $time_begin) . ' ' . "23:59";
        }
        if ($clearType == "week") {
            if ($first == CommonEnum::STATE_IS_OK) {
                if (date('w') == 1) {

                    return addDay(7, date('Y-m-d')) . ' ' . "00:01";
                } else {
                    return date('Y-m-d', strtotime('+1 week last monday')) . ' ' . "00:01";
                }
            } else if ($end == CommonEnum::STATE_IS_OK) {
                if (date('w') == 0) {
                    return date('Y-m-d') . ' ' . "23:59";
                } else {
                    return date('Y-m-d', strtotime('+1 week last sunday')) . ' ' . "23:59";
                }
            }
        } else if ($clearType == "month") {
            if ($first == CommonEnum::STATE_IS_OK) {
                $nextMonthBegin = date('Y-m-01', strtotime('+1 month'));
                return $nextMonthBegin . ' ' . "00:01";
            } else if ($end == CommonEnum::STATE_IS_OK) {
                $monthBegin = date('Y-m-01');
                return date('Y-m-d', strtotime("$monthBegin +1 month -1 day")) . ' ' . "23:59";
            }

        } else if ($clearType == "quarter") {
            $season = ceil((date('n')) / 3);

            if ($first == CommonEnum::STATE_IS_OK) {
                $nextQuarterBegin = date('Y-m-01', mktime(0, 0, 0, ($season) * 3 + 1, 1, date('Y')));
                return $nextQuarterBegin . ' ' . "00:01";
            } else if ($end == CommonEnum::STATE_IS_OK) {
                return date('Y-m-d', mktime(23, 59, 59, $season * 3,
                    date('t', mktime(0, 0, 0, $season * 3, 1,
                        date("Y"))), date('Y')));
            }

        } else if ($clearType == "year") {
            $nextYearBegin = date('Y-01-01', strtotime('+1 year'));

            if ($first == CommonEnum::STATE_IS_OK) {
                return $nextYearBegin . ' ' . "00:01";
            } else if ($end == CommonEnum::STATE_IS_OK) {
                return reduceDay(1, $nextYearBegin) . ' ' . "23:59";
            }
        }
    }


}