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

    }

    public function test($param = "")
    {
        $count = OrderingV::userOrderings("13794247582", 3, 191, 1, 10);
        //$count = OrderingV:: getOrderingByWithDinnerID("2020-10-14", 160, "13794247582");
        return json($count);
        /* $orderingV = Db::field("`a`.`id` AS `id`,
     `a`.`u_id` AS `u_id`,
     `a`.`ordering_type` AS `ordering_type`,
     `a`.`canteen_id` AS `c_id`,
     `b`.`name` AS `canteen`,
     `a`.`dinner_id` AS `d_id`,
     `c`.`name` AS `dinner`,
     `a`.`ordering_date` AS `ordering_date`,
     date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
     `a`.`count` AS `count`,
     `a`.`state` AS `state`,
     `a`.`used` AS `used`,
     `a`.`type` AS `type`,
     `a`.`create_time` AS `create_time`,
     (
         ( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee`
     ) AS `money`,
     `a`.`phone` AS `phone`,
     `a`.`company_id` AS `company_id`,
     `a`.`pay` AS `pay`,
     `a`.`sub_money` AS `sub_money`,
     `a`.`delivery_fee` AS `delivery_fee`,
     'more' AS `consumption_type`,
     `a`.`fixed` AS `fixed`,
     `a`.`all_used` AS `all_used`,
     `a`.`receive` AS `receive`,a.booking ")
             ->table('canteen_order_parent_t')
             ->alias('a')
             ->leftJoin('canteen_canteen_t b', 'a.canteen_id = b.id')
             ->leftJoin('canteen_dinner_t c', 'a.dinner_id = c.id')
             ->where('a.phone', "13794247582")
             //->where('ordering_month', "2020-10")
             ->where('a.booking', CommonEnum::STATE_IS_OK)
             ->where('a.state', CommonEnum::STATE_IS_OK)
             ->where('a.pay', PayEnum::PAY_SUCCESS)
             ->unionAll(function ($query) {
                 $query->field("	`a`.`id` AS `id`,
     `a`.`u_id` AS `u_id`,
     `a`.`ordering_type` AS `ordering_type`,
     `a`.`c_id` AS `c_id`,
     `b`.`name` AS `canteen`,
     `a`.`d_id` AS `d_id`,
     `c`.`name` AS `dinner`,
     `a`.`ordering_date` AS `ordering_date`,
     date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
     `a`.`count` AS `count`,
     `a`.`state` AS `state`,
     `a`.`used` AS `used`,
     `a`.`type` AS `type`,
     `a`.`create_time` AS `create_time`,
     (
         ( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee`
     ) AS `money`,
     `a`.`phone` AS `phone`,
     `a`.`company_id` AS `company_id`,
     `a`.`pay` AS `pay`,
     `a`.`sub_money` AS `sub_money`,
     `a`.`delivery_fee` AS `delivery_fee`,
     'one' AS `consumption_type`,
     `a`.`fixed` AS `fixed`,
     `a`.`used` AS `all_used`,
     `a`.`receive` AS `receive`,a.booking ")->table('canteen_order_t')
                     ->alias('a')
                     ->leftJoin('canteen_canteen_t b', 'a.c_id = b.id')
                     ->leftJoin('canteen_dinner_t c', 'a.d_id = c.id')
                     ->where('a.phone', "13794247582")
                     //->where('ordering_month', "2020-10")
                     ->where('a.booking', CommonEnum::STATE_IS_OK)
                     ->where('a.state', CommonEnum::STATE_IS_OK)
                     ->where('a.pay', PayEnum::PAY_SUCCESS);
             })
            ->select();
         print_r($orderingV);*/

    }

    public function token()
    {
        return json(\app\api\service\Token::getCurrentTokenVar());

    }
}