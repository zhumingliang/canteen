<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\job\UploadExcel;
use app\api\model\CanteenT;
use app\api\model\CompanyAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\CompanyT;
use app\api\model\ConsumptionRecordsV;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\model\OrderConsumptionV;
use app\api\model\OrderingV;
use app\api\model\OrderParentT;
use app\api\model\OrderSubT;
use app\api\model\OrderT;
use app\api\model\OrderUnusedV;
use app\api\model\PayNonghangConfigT;
use app\api\model\PayT;
use app\api\model\RechargeCashT;
use app\api\model\RechargeV;
use app\api\model\Submitequity;
use app\api\model\UserBalanceV;
use app\api\service\AccountService;
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
use app\api\service\ShopService;
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
    public function index()
    {
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

    }

    public function test($param = "")
    {
        (new ShopService())->handleReduceOrder(34787,7250,14);

    }

    public function token()
    {
        return json(\app\api\service\Token::getCurrentTokenVar());

    }
}