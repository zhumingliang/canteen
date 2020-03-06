<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;
use app\api\service\OrderStatisticService;
use app\api\service\TakeoutService;
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
     * http://canteen.tonglingok.com/api/v1/order/takeoutStatistic?company_ids=2&canteen_id=0&ordering_date=2019-09-07&page=1&size=20&dinner_id=0&status=1&user_type=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息，否则传0
     * @apiParam (请求参数说明) {string} department_id  部门id，全部传0
     * @apiParam (请求参数说明) {string} user_type  人员类型：1 ：外来人员；2：企业内部人员 ；全部传0
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} status  状态：1:已经支付；2：已取消；3：已接单；4:已完成 ;5:已退回 ；6 全部
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"20","current_page":1,"last_page":1,"data":[{"order_id":233,"money":"6.0","used":2,"ordering_date":"2020-02-15","dinner":"午餐","canteen":"11楼饭堂","username":"宁晓晓","phone":"18219112778","province":"广东省","area":"蓬江区","city":"江门市","address":"。。。","department_id":81,"outsider":2,"receive":1,"pay":"paid","state":1,"status":2}]}}
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
     * @apiSuccess (返回参数说明) {string} outsider 是否外来人员订单 1：是；2｜否
     * @apiSuccess (返回参数说明) {int} status 订单状态：1:已经支付；2：已取消；3：已接单；4:已完成 ;5:已退回
     */
    public function statistic($page = 1, $size = 20)
    {
        $ordering_date = Request::param('ordering_date');
        $company_ids = Request::param('company_ids');
        $canteen_id = Request::param('canteen_id');
        $department_id = Request::param('department_id');
        $dinner_id = Request::param('company_id');
        $status = Request::param('status');
        $user_type = Request::param('user_type');
        $statistic = (new OrderStatisticService())->takeoutStatistic($page, $size,
            $ordering_date, $company_ids, $canteen_id, $dinner_id, $status, $department_id, $user_type);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }


    /**
     * @api {GET} /api/v1/order/takeoutStatistic/official  微信端-外卖管理-订单列表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-外卖管理-订单列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/takeoutStatistic/official?ordering_date=2019-09-07&page=1&size=20&dinner_id=0&status=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：全部传0
     * @apiParam (请求参数说明) {string} department_id  部门id，全部传0
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} status  状态：3：已接单；4:已完成 ;6 全部
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"20","current_page":1,"last_page":1,"data":[{"order_id":233,"province":"广东省","city":"江门市","area":"蓬江区","address":"。。。","username":"小新","phone":"18219112778","used":2,"count":2,"money":"12.00","delivery_fee":"0.00","foods":[{"o_id":233,"name":"肉2","price":"1.0","count":1},{"o_id":233,"name":"肉","price":"1.0","count":1}]}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} order_id 订单id
     * @apiSuccess (返回参数说明) {string} username 用户姓名
     * @apiSuccess (返回参数说明) {string} phone 用户手机号
     * @apiSuccess (返回参数说明) {int} id  地址id
     * @apiSuccess (返回参数说明) {string} city  城市
     * @apiSuccess (返回参数说明) {string} area  区
     * @apiSuccess (返回参数说明) {string} address  详细地址
     * @apiSuccess (返回参数说明) {int} count  份数
     * @apiSuccess (返回参数说明) {float} money  总金额
     * @apiSuccess (返回参数说明) {float} delivery_fee  配送费
     * @apiSuccess (返回参数说明) {int} used 订单状态：2｜已接单；1：已完成
     * @apiSuccess (返回参数说明) {obj} foods 菜品信息
     * @apiSuccess (返回参数说明) {string} name 菜品名称
     * @apiSuccess (返回参数说明) {float} price 菜品价格
     * @apiSuccess (返回参数说明) {int} count 菜品数量
     */
    public function officialStatistic($page = 1, $size = 20)
    {
        $ordering_date = Request::param('ordering_date');
        $department_id = Request::param('department_id');
        $dinner_id = Request::param('dinner_id');
        $status = Request::param('status');
        $statistic = (new OrderStatisticService())->takeoutStatisticForOfficial($page, $size,
            $ordering_date, $dinner_id, $status, $department_id);
        return json(new SuccessMessageWithData(['data' => $statistic]));

    }

    /**
     * @api {GET} /api/v1/order/takeoutStatistic/export CMS管理端-外卖管理-订单列表-导出报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-外卖管理-订单列表-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderStatistic/export?company_ids=2&canteen_id=0&ordering_date=2019-09-07&dinner_id=0&status=1&user_type=1
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息，否则传0
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {string} user_type  人员类型：1 ：外来人员；2：企业内部人员 ；全部传0
     * @apiParam (请求参数说明) {int} status  状态：1:已经支付；2：已取消；3：已接单；4:已完成 ;5:已退回 ；6 全部
     * @apiParam (请求参数说明) {string} department_id  部门id，全部传0
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportStatistic()
    {
        $ordering_date = Request::param('ordering_date');
        $company_ids = Request::param('company_ids');
        $canteen_id = Request::param('canteen_id');
        $dinner_id = Request::param('company_id');
        $status = Request::param('status');
        $department_id = Request::param('department_id');
        $user_type = Request::param('user_type');
        $statistic = (new OrderStatisticService())->exportTakeoutStatistic($ordering_date, $company_ids, $canteen_id, $dinner_id, $status, $department_id, $user_type);
        return json(new SuccessMessageWithData(['data' => $statistic]));


    }

    /**
     * @api {POST} /api/v1/order/used 微信端-外卖管理-确认送达订单
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-外卖管理-确认送达订单
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {string} ids  订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function used()
    {
        $order_id = Request::param('id');
        (new OrderService())->used($order_id);
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
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":8,"address_id":1,"d_id":6,"type":2,"money":2,"sub_money":2,"delivery_fee":2,"create_time":"2019-09-09 16:34:15","hidden":2,"foods":[{"detail_id":5,"o_id":8,"food_id":1,"count":1,"name":"菜品1","price":"5.0"},{"detail_id":6,"o_id":8,"food_id":3,"count":1,"name":"菜品2","price":"5.0"}],"address":{"id":1,"province":"广东省","city":"江门市","area":"蓬江区","address":"江门市白石大道东4号路3栋","name":"张三","phone":"18956225230","sex":1}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} money 订单基本金额
     * @apiSuccess (返回参数说明) {int} sub_money 附加金额
     * @apiSuccess (返回参数说明) {int} delivery_fee 配送费
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

    /**
     * @api {POST} /api/v1/order/receive CMS管理端-外卖管理-接单操作
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-外卖管理-接单操作
     * @apiExample {post}  请求样例:
     *    {
     *       "ids": "1,2,3"
     *     }
     * @apiParam (请求参数说明) {string} ids  订单id列表，用逗号分隔（前端需要自行检测是否可以接单）
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function receive()
    {
        $order_id = Request::param('ids');
        (new  TakeoutService())->receiveOrder($order_id);
        return json(new SuccessMessage());
    }

}