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
use app\api\model\OrderingV;
use app\api\model\OrderParentT;
use app\api\model\OrderSubT;
use app\api\model\OrderT;
use app\api\model\OrderUnusedV;
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
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\enum\StrategyEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\Num;
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
        /*
        *
        *
       $redis = new \Redis();
       $redis->connect('121.37.255.12', 6379);
       $redis->auth('waHqes-nijpi8-ruwqex');
       $redis->set('a',1);
      echo $redis->get('a');*/
        echo \app\api\service\Token::getCurrentUid();

    }

    public function test($param = "")
    {
        $a = '';
        $arr = explode('|', $a);
        print_r($arr);
        if (!count($arr)) {
            echo 1;
        } else {
            echo 2;
        }

        /* $parent = OrderParentT::where('state', CommonEnum::STATE_IS_FAIL)
             ->where('phone',"13267686837")
             ->select();
         foreach ($parent as $k => $v) {
             OrderSubT::update(['state' => 2], ['order_id' => $v['id']]);
         }*/

    }

    public function token()
    {
        return json(\app\api\service\Token::getCurrentTokenVar());

    }
}