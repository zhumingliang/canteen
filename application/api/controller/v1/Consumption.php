<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\ConsumptionService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Consumption extends BaseController
{
    public function staff()
    {
        $code = Request::param('code');
        $type = Request::param('type');
        $staff_id = Request::param('staff_id');
        (new ConsumptionService())->staff($type, $code, $staff_id);
        return json(new SuccessMessage());

    }

}