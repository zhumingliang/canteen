<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;
use app\api\service\OrderStatisticService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Takeout extends BaseController
{
    /**
     * @api {GET} /api/v1/order/takeoutStatistic CMS管理端-外卖管理-订单列表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-外卖管理-订单列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderStatistic?company_ids=2&canteen_id=0&ordering_date=2019-09-07&page=1&size=20&dinner_id=0
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息，否则传0
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"20","current_page":1,"last_page":1,"data":[{"order_id":8,"money":"10.0","used":2,"ordering_date":"2019-09-07","dinner":"中餐","canteen":"饭堂1","username":"张三","phone":"18956225230","province":"广东省","area":"蓬江区","city":"江门市","address":"江门市白石大道东4号路3栋"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} order_id 订单id
     * @apiSuccess (返回参数说明) {string} ordering_date 订餐日期
     * @apiSuccess (返回参数说明) {string} canteen 消费地点
     * @apiSuccess (返回参数说明) {string} username 用户姓名
     * @apiSuccess (返回参数说明) {string} phone 用户手机号
     * @apiSuccess (返回参数说明) {float} money 金额
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {int} used 是否派单：1｜已处理；2｜未处理（打印小票）
     */
    public function statistic($page = 1, $size = 20)
    {
        $ordering_date = Request::param('ordering_date');
        $company_ids = Request::param('company_ids');
        $canteen_id = Request::param('canteen_id');
        $dinner_id = Request::param('company_id');
        $statistic = (new OrderStatisticService())->takeoutStatistic($page, $size,
            $ordering_date, $company_ids, $canteen_id, $dinner_id);
        return json(new SuccessMessageWithData(['data' => $statistic]));


    }

    /**
     * @api {POST} /api/v1/order/used CMS管理端-外卖管理-打印小票触发外卖完成状态
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-外卖管理-打印小票触发外卖完成状态
     * @apiExample {post}  请求样例:
     *    {
     *       "ids": "1,2,3"
     *     }
     * @apiParam (请求参数说明) {string} ids  订单id列表，用逗号分隔
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function used()
    {
        $ids = Request::param('ids');
        (new OrderService())->used($ids);
        return json(new SuccessMessage());
    }

}