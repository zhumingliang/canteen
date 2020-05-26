<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\CanteenT;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\model\OrderT;
use app\api\model\Submitequity;
use app\api\model\UserBalanceV;
use app\api\service\AddressService;
use app\api\service\CanteenService;
use app\api\service\CompanyService;
use app\api\service\DepartmentService;
use app\api\service\NoticeService;
use app\api\service\OrderService;
use app\api\service\QrcodeService;
use app\api\service\WeiXinService;
use app\lib\enum\CommonEnum;
use app\lib\exception\SuccessMessageWithData;
use app\lib\printer\Printer;
use think\Db;
use think\db\Where;
use think\facade\Env;
use think\Queue;
use think\Request;
use zml\tp_tools\Aes;

class
Index extends BaseController
{
    public function index(Request $request)
    {

//(new Printer())->printOrderDetail(1,1388,2,'0001');
// (new  NoticeService())->noticeTask(26,155,'');
//(new OrderService())->refundWxOrder($id);
// $this->mailTask($name);
// $detail = '[{"d_id":122,"ordering":[{"ordering_date":"2020-01-21","count":1}]}]';

// (new OrderService())->orderingOnlineTest($detail, $name);
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

//邮件队列
private
function mailTask($email = '')
{
    //php think queue:work --queue sendMsgQueue
    $jobHandlerClassName = 'app\api\job\SendMsg';//负责处理队列任务的类
    $jobQueueName = "sendMsgQueue";//队列名称
    $jobData = ['email' => $email];//当前任务的业务数据
    $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);//将该任务推送到消息队列
    if ($isPushed !== false) {
        echo date('Y-m-d H:i:s') . '邮件队列任务发送成功';
    } else {
        echo date('Y-m-d H:i:s') . '邮件队列发送失败';
    }

}


}