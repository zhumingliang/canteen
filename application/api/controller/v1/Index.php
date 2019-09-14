<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Submitequity;
use app\api\service\AddressService;
use app\api\service\OrderService;
use app\api\service\QrcodeService;
use app\lib\exception\SuccessMessageWithData;

class Index extends BaseController
{
    public function index()
    {
        $day = '2019-09-13';
        echo date('W',strtotime($day));
        $day = '2019-09-16';
        echo date('W',time());

    }

}