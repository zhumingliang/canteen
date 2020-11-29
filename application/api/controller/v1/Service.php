<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CompanyService;
use app\api\service\ConsumptionService;
use app\api\service\LogService;
use app\api\service\NoticeService;
use app\api\service\OffLineService;
use app\api\service\OrderService;
use app\api\service\SendSMSService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
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
        (new \app\lib\printer\Printer())->printOrderDetail($params['canteenID'], $params['orderID'], $params['sortCode'], $params['consumptionType']);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/service/offline/config  消费机-离线消费-获取饭堂餐次和消费策略配置
     * @apiGroup  Machine
     * @apiVersion 3.0.0
     * @apiDescription  消费机-离线消费-获取饭堂餐次和消费策略配置
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/service/offline/config
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"dinners":[{"id":417,"c_id":296,"name":"早餐","type":"day","create_time":"2020-11-16 15:57:01","type_number":1,"meal_time_begin":"06:00","meal_time_end":"08:30","limit_time":"17:00:00","fixed":1},{"id":418,"c_id":296,"name":"晚餐","type":"day","create_time":"2020-11-16 15:57:01","type_number":1,"meal_time_begin":"17:25","meal_time_end":"18:20","limit_time":"17:00:00","fixed":1}],"strategies":[{"id":714,"c_id":296,"t_id":101,"d_id":417,"unordered_meals":1,"detail":[{"number":1,"strategy":[{"number":1,"status":"ordering_meals","money":"0","sub_money":"0"},{"number":1,"status":"no_meals_ordered","sub_money":"0","money":"0"},{"number":1,"status":"unordered_meals","sub_money":"0","money":"4"}]}],"consumption_count":1,"ordered_count":0,"consumption_type":1},{"id":715,"c_id":296,"t_id":101,"d_id":418,"unordered_meals":1,"detail":[{"number":1,"strategy":[{"number":1,"status":"ordering_meals","sub_money":"0","money":"0"},{"number":1,"status":"no_meals_ordered","sub_money":"0","money":"0"},{"number":1,"status":"unordered_meals","sub_money":"0","money":"5"}]}],"consumption_count":1,"ordered_count":0,"consumption_type":1}]}}
     * @apiSuccess (返回参数说明) {string} dinners  订餐信息json字符串
     * @apiSuccess (返回参数说明) {string} id  餐次id
     * @apiSuccess (返回参数说明) {string} name  餐次名称
     * @apiSuccess (返回参数说明) {string} type  时间设置类别：day|week
     * @apiSuccess (返回参数说明) {int} fixed  餐次是否采用标准金额：1｜是；2｜否
     * @apiSuccess (返回参数说明) {string} create_time  创建时间
     * @apiSuccess (返回参数说明) {int} type_number 订餐时间类别对应数量 （week：0-6；周日-周六）
     * @apiSuccess (返回参数说明) {string} limit_time  订餐限制时间
     * @apiSuccess (返回参数说明) {string} meal_time_begin  就餐开始时间
     * @apiSuccess (返回参数说明) {string} meal_time_end  就餐截止时间
     * @apiSuccess (返回参数说明) {obj} strategies  消费策略信息
     * @apiSuccess (返回参数说明) {int} id 消费策略id
     * @apiSuccess (返回参数说明) {int} t_id 人员类型id
     * @apiSuccess (返回参数说明) {int} unordered_meals 是否未订餐允许就餐：1：是；2：否
     * @apiSuccess (返回参数说明) {int} consumption_count 允许消费次数
     * @apiSuccess (返回参数说明) {int} consumption_type  打卡方式：1：一次性打开方式；2：逐次打卡消费
     * @apiSuccess (返回参数说明) {int} ordered_count 订餐数量
     * @apiSuccess (返回参数说明) {string} detail 策略明细
     * @apiSuccess (返回参数说明) {string} fixed  策略类型：1； 固定；2： 动态
     * @apiSuccess (返回参数说明) {int} detail  策略明细
     * @apiSuccess (返回参数说明) {int}  number  次数类型
     * @apiSuccess (返回参数说明) {string}  strategy  餐次策略明细
     * @apiSuccess (返回参数说明) {string}  status  消费状态：ordering_meals|订餐就餐；no_meals_ordered|订餐未就餐；unordered_meals|未订餐就餐
     * @apiSuccess (返回参数说明) {float}  money 标准金额
     * @apiSuccess (返回参数说明) {float}  sub_money  附加金额
     */
    public function configForOffLine()
    {
        $config = (new CompanyService())->configForOffLine();
        return json(new SuccessMessageWithData(['data' => $config]));
    }

    /**
     * @api {GET} /api/v1/service/offline/config  消费机-离线消费-获取饭堂今日订餐信息
     * @apiGroup  Machine
     * @apiVersion 3.0.0
     * @apiDescription  消费机-离线消费-获取饭堂今日订餐信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/service/offline/config
     * @apiSuccessExample {json} 一次扣费返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":34625,"staff_id":7358,"money":"4.20","sub_money":"2.40","meal_money":"4.00","meal_sub_money":"2.20","consumption_type":"no_meals_ordered","fixed":2},{"id":34623,"staff_id":7358,"money":"6.00","sub_money":"6.60","meal_money":"3.00","meal_sub_money":"3.30","consumption_type":"no_meals_ordered","fixed":1}]}
     * @apiSuccess (返回参数说明) {int} id  订单id
     * @apiSuccess (返回参数说明) {int} staff_id  用户uid
     * @apiSuccess (返回参数说明) {float} money  冻结标准金额
     * @apiSuccess (返回参数说明) {float} sub_money  冻结附加金额
     * @apiSuccess (返回参数说明) {float} meal_money  订餐就餐金额
     * @apiSuccess (返回参数说明) {float} meal_sub_money  订餐就餐附加金额
     * @apiSuccess (返回参数说明) {string} consumption_type  当前冻结金额消费状态：ordering_meals：订餐就餐；no_meals_ordered：订餐未就餐
     * @apiSuccess (返回参数说明) {int}  fixed  是否固定消费：1：是；2：否
     * @apiSuccessExample {json} 多次扣费返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":34110,"staff_id":7358,"fixed":1,"sub":[{"id":34812,"order_id":34110,"consumption_sort":1,"money":"3.00","sub_money":"1.00","meal_money":"3.00","meal_sub_money":"0.00","consumption_type":"no_meals_ordered"},{"id":34813,"order_id":34110,"consumption_sort":2,"money":"3.00","sub_money":"1.00","meal_money":"3.00","meal_sub_money":"0.00","consumption_type":"no_meals_ordered"},{"id":34814,"order_id":34110,"consumption_sort":3,"money":"3.00","sub_money":"1.00","meal_money":"3.00","meal_sub_money":"0.00","consumption_type":"no_meals_ordered"}]}]}
     * @apiSuccess (返回参数说明) {int} id  总订单id
     * @apiSuccess (返回参数说明) {int} staff_id  用户uid
     * @apiSuccess (返回参数说明) {int}  fixed  是否固定消费：1：是；2：否
     * @apiSuccess (返回参数说明) {obj}  sub 子订单信息
     * @apiSuccess (返回参数说明) {int} id  子订单id
     * @apiSuccess (返回参数说明) {float} money  冻结标准金额
     * @apiSuccess (返回参数说明) {float} sub_money  冻结附加金额
     * @apiSuccess (返回参数说明) {float} meal_money  订餐就餐金额
     * @apiSuccess (返回参数说明) {float} meal_sub_money  订餐就餐附加金额
     * @apiSuccess (返回参数说明) {string} consumption_type  当前冻结金额消费状态：ordering_meals：订餐就餐；no_meals_ordered：订餐未就餐
     * @apiSuccess (返回参数说明) {int} consumption_sort 消费排序：按照从小到大的顺序消费
     */
    public function orderForOffline()
    {
        $orders = (new OffLineService())->orderForOffline();
        return json(new SuccessMessageWithData(['data' => $orders]));
    }

    /**
     * @api {GET} /api/v1/service/offline/company/staffs  消费机-离线消费-获取饭企业用户信息
     * @apiGroup  Machine
     * @apiVersion 3.0.0
     * @apiDescription  消费机-离线消费-获取饭企业用户信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/service/offline/company/staffs
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":7242,"username":"测试5","staff_type_id":101,"balance":"32.00","card":{"id":355,"staff_id":7242,"card_code":"103","state":1}}]}
     * @apiSuccess (返回参数说明) {int} id  用户id
     * @apiSuccess (返回参数说明) {string} username  用户姓名
     * @apiSuccess (返回参数说明) {int} staff_type_id  用户人员类型id（匹配消费策略）
     * @apiSuccess (返回参数说明) {float} balance  余额
     * @apiSuccess (返回参数说明) {obj} card  IC卡信息
     * @apiSuccess (返回参数说明) {int} id  IC卡ID
     * @apiSuccess (返回参数说明) {int} card_code  卡号
     * @apiSuccess (返回参数说明) {int} state   卡状态：1:正常；2:挂失
     */
    public function staffsForOffline()
    {
        $staffs = (new OffLineService())->staffsForOffline();
        return json(new SuccessMessageWithData(['data' => $staffs]));
    }

}