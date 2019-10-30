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
     * http://canteen.tonglingok.com/api/v1/order/orderStatistic?company_ids=2&canteen_id=0&ordering_date=2019-09-07&page=1&size=20&dinner_id=0&used=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息，否则传0
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} used  打印状态：1｜已打印；2｜未打印；3｜全部
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
        $used = Request::param('used');
        $statistic = (new OrderStatisticService())->takeoutStatistic($page, $size,
            $ordering_date, $company_ids, $canteen_id, $dinner_id, $used);
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

    /**
     * @api {GET} /api/v1/order/info/print CMS管理端--外卖管理--获取打印订单的信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端--外卖管理--获取打印订单的信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/info/print?order_id=8
     * @apiParam (请求参数说明) {int} order_id 订单id
     * @apiSuccessExample {json} 返回样例:
    {"msg":"ok","errorCode":0,"code":200,"data":{"id":8,"address_id":1,"d_id":6,"type":2,"create_time":"2019-09-09 16:34:15","hidden":2,"foods":[{"detail_id":5,"o_id":8,"food_id":1,"count":1,"name":"菜品1","price":"5.0"},{"detail_id":6,"o_id":8,"food_id":3,"count":1,"name":"菜品2","price":"5.0"}],"address":{"id":1,"province":"广东省","city":"江门市","area":"蓬江区","address":"江门市白石大道东4号路3栋","name":"张三","phone":"18956225230","sex":1}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} hidden 是否隐藏订单明细价格：1｜隐藏；2｜不隐藏
     * @apiSuccess (返回参数说明) {obj} foods 菜品信息
     * @apiSuccess (返回参数说明) {int} count 数量
     * @apiSuccess (返回参数说明) {string} name 名称
     * @apiSuccess (返回参数说明) {int} price 价格
     * @apiSuccess (返回参数说明) {obj} address 地址信息
     * @apiSuccess (返回参数说明) {int} id  地址id
     * @apiSuccess (返回参数说明) {string} province  省
     * @apiSuccess (返回参数说明) {string} city  城市
     * @apiSuccess (返回参数说明) {string} area  区
     * @apiSuccess (返回参数说明) {string} address  详细地址
     * @apiSuccess (返回参数说明) {string} name  姓名
     * @apiSuccess (返回参数说明) {string} phone  手机号
     * @apiSuccess (返回参数说明) {int} sex  性别：1|男；2|女
     */
    public function infoToPrint()
    {
        $id = Request::param('order_id');
        $info = (new OrderStatisticService())->infoToPrint($id);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

}