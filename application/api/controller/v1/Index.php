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
        (new AddressService())->prefixAddressDefault(2);
    }

}