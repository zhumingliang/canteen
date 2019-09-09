<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Submitequity;
use app\api\service\QrcodeService;
use app\lib\exception\SuccessMessageWithData;

class Index extends BaseController
{
    public function index()
    {
       // (new QrcodeService())->qr_code('https://tonglingok.com/driver.apk');
    }

}