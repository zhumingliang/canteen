<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\ConsumptionService;
use app\api\service\LogService;
use app\api\service\NoticeService;
use app\api\service\OrderService;
use app\api\service\SendSMSService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Service extends BaseController
{
    //处理订餐未就餐改为订餐就餐
    public function orderStateHandel()
    {
        (new OrderService())->orderStateHandel();
    }

    public function sendMsgHandel()
    {
        (new SendSMSService())->sendHandel();
    }

    public function sendNoticeHandel()
    {
        (new NoticeService())->sendNoticeHandel();
    }

    public function printer()
    {
        $params = Request::param();
        LogService::save(json_encode($params));
        (new ConsumptionService())->sortTask($params['canteenID'], 0, $params['orderID'], $params['sortCode'], $params['consumptionType']);
        (new \app\lib\printer\Printer())->printOrderDetail($params['canteenID'], $params['orderID'], $params['sortCode'],$params['consumptionType']);
        return json(new SuccessMessage());

    }

}