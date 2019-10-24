<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\CanteenT;
use app\api\model\Submitequity;
use app\api\service\AddressService;
use app\api\service\CompanyService;
use app\api\service\OrderService;
use app\api\service\QrcodeService;
use app\lib\exception\SuccessMessageWithData;
use think\Db;

class Index extends BaseController
{
    public function index()
    {
        $user = CanteenT::whereIn('id', "19")
            ->field('name')->select()->toArray();
        $user_ids = array();
        foreach ($user as $k => $v) {
            array_push($user_ids, $v['name']);
        }
        echo implode('|', $user_ids);
    }

}