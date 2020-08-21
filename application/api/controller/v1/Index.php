<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\job\UploadExcel;
use app\api\model\CanteenT;
use app\api\model\CompanyStaffT;
use app\api\model\ConsumptionRecordsV;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\model\OrderConsumptionV;
use app\api\model\OrderT;
use app\api\model\PayT;
use app\api\model\RechargeCashT;
use app\api\model\RechargeV;
use app\api\model\Submitequity;
use app\api\model\UserBalanceV;
use app\api\service\AddressService;
use app\api\service\CanteenService;
use app\api\service\CompanyService;
use app\api\service\ConsumptionService;
use app\api\service\DepartmentService;
use app\api\service\ExcelService;
use app\api\service\NoticeService;
use app\api\service\OrderService;
use app\api\service\QrcodeService;
use app\api\service\SendSMSService;
use app\api\service\TakeoutService;
use app\api\service\WalletService;
use app\api\service\WeiXinService;
use app\lib\Date;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\printer\Printer;
use think\Db;
use think\db\Where;
use think\Exception;
use think\facade\Env;
use think\Queue;
use think\Request;
use zml\tp_tools\Aes;
use zml\tp_tools\Redis;
use function GuzzleHttp\Psr7\str;

class
Index extends BaseController
{
    public function index($sorts)
    {
        //(new TakeoutService())->refundOrder([11283]);

        if (empty($sorts)) {
            throw new ParameterException(['排队号，不能为空']);
        }
        $orders = OrderT::where('sort_code', 'in', $sorts)
            ->where('ordering_date', \date('Y-m-d'))
            ->select();
        $res = [];
        foreach ($orders as $k => $v) {
            $canteenID = 179;
            $orderID = $v['id'];
            $outsider = 2;
            $sortCode = $v['sort_code'];
            $printRes = (new Printer())->printOrderDetail($canteenID, $orderID, $outsider, $sortCode);
            if ($printRes) {
                array_push($res, $v['sort_code'] . "补打印成功");
            } else {
                array_push($res, $v['sort_code'] . "补打印失败");
            }

        }
        return json(new  SuccessMessageWithData(['data' => $res]));


        /* $file_name = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/upload/test.xlsx';
         $data = (new ExcelService())->importExcel($file_name);
         $fail = (new WalletService())->prefixUploadData(69, 1, $data);
         return json(new SuccessMessageWithData(['data' => $fail]));*/

        //(new Printer())->printOrderDetail(1,1388,2,'0001');
// (new  NoticeService())->noticeTask(26,155,'');
//(new OrderService())->refundWxOrder($id);
// $this->mailTask($name);
// $detail = '[{"d_id":122,"ordering":[{"ordering_date":"2020-01-21","count":1}]}]';

// (new OrderService())->orderingOnlineTest($detail, $name);
        /* $strategy = ConsumptionStrategyT::where('state', CommonEnum::STATE_IS_OK)
          ->select()->toArray();
         foreach ($strategy as $k => $v) {
             if(!empty($v['detail'])){
                 (new CanteenService())->prefixStrategyDetail($v['id'],$v['c_id'],$v['d_id'],$v['t_id'],$v['detail']);
             }
         }*/

        /* $money = UserBalanceV::userBalanceGroupByEffective(3, '15521323081');
         print_r($money);*/

        /* $user = CanteenT::whereIn('id', "19")
             ->field('name')->select()->toArray();
         $user_ids = array();
         foreach ($user as $k => $v) {
             array_push($user_ids, $v['name']);
         }
         echo implode('|', $user_ids);*/
    }

    public function test()
    {
        return json(\app\api\service\Token::getCurrentTokenVar());

        /* $data = (new ExcelService())->saveTestExcel();
         $fail = [];
         foreach ($data as $k => $v) {
             if ($k == 1 || empty($v[0])) {
                 continue;
             }
                if ($k >200 && $k <= 300) {
                //if ( $k <= 100) {
                    $orderNum = $v[1];
                    $phone = $v[20];
                    $staff = CompanyStaffT::where('company_id', $company_id)
                        ->where('state',CommonEnum::STATE_IS_OK)
                        ->where('phone', $phone)
                        ->find();
                    if (!$staff) {
                        array_push($fail, [
                            'ordernumber' => $orderNum,
                            'phone' => $phone
                        ]);
                    }
                    PayT::update([
                        'phone' => $phone,
                        'username' => $staff->username,
                        'staff_id' => $staff->id
                    ], ['order_num' => $orderNum, 'company_id' => $company_id]);
                }

         }

         return json($fail);*/

        /*
                try {
                    Db::startTrans();
                    $data = (new ExcelService())->saveTestExcel();
                    $dataList = [];
                    foreach ($data as $k => $v) {
                        if ($k == 1 || empty($v[0])) {
                            continue;
                        }
                        if ($k < 15) {
                            array_push($dataList, [
                                'id' => $v[0],
                                'money' => $v[11],
                                'sub_money' => $v[12],
                            ]);
                        } else {
                            array_push($dataList, [
                                'id' => $v[0],
                                'no_meal_money' => $v[11],
                                'no_meal_sub_money' => $v[12],
                            ]);
                        }

                    }

                    $res = (new  OrderT())->saveAll($dataList);
                    if (!$res) {
                        throw  new SaveException();
                    }
                     Db::commit();
                    return json(new SuccessMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    throw  $e;
                }*/
    }

}