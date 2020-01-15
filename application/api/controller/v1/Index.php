<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\CanteenT;
use app\api\model\ConsumptionStrategyT;
use app\api\model\Submitequity;
use app\api\model\UserBalanceV;
use app\api\service\AddressService;
use app\api\service\CanteenService;
use app\api\service\CompanyService;
use app\api\service\DepartmentService;
use app\api\service\OrderService;
use app\api\service\QrcodeService;
use app\lib\enum\CommonEnum;
use app\lib\exception\SuccessMessageWithData;
use think\Db;
use think\db\Where;

class Index extends BaseController
{
    public function index()
    {


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

}