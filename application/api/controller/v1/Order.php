<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/4
 * Time: 15:52
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\MaterialReportT;
use app\api\model\OnlineOrderingT;
use app\api\model\PersonalChoiceT;
use app\api\model\ShopOrderDetailV;
use app\api\service\OrderService;
use app\api\service\OrderStatisticService;
use app\lib\enum\CommonEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class Order extends BaseController
{
    /**
     * @api {POST} /api/v1/order/personChoice/save 微信端-个人选菜-新增订单
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-个人选菜-新增订单
     * @apiExample {post}  请求样例:
     *    {
     *       "ordering_date": "2019-09-07",
     *       "dinner_id": 1,
     *       "dinner": "早餐",
     *       "type": 1,
     *       "count": 1,
     *       "address_id": 1,
     *       "detail":[{"menu_id":1,"foods":[{"food_id":1,"name":"商品1","price":5,""count":1},{"food_id":2,"name":"商品1","price":5,"count":1}]}]
     *     }
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiParam (请求参数说明) {int} dinner 餐次名称
     * @apiParam (请求参数说明) {int} type 就餐类别：1|食堂；2|外卖
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {int} address_id 配送地址id
     * @apiParam (请求参数说明) {obj} detail 订餐菜品明细
     * @apiParam (请求参数说明) {string} detail|menu_id 菜品类别id
     * @apiParam (请求参数说明) {obj} detail|foods 菜品明细
     * @apiParam (请求参数说明) {string} foods|food_id 菜品id
     * @apiParam (请求参数说明) {string} foods|price 菜品实时单价
     * @apiParam (请求参数说明) {string} foods|count 菜品数量
     * @apiParam (请求参数说明) {string} foods|name 菜品名称
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function personChoice()
    {
        $params = Request::param();
        $order = (new OrderService())->personChoice($params);
        return json(new SuccessMessageWithData(['data' => $order]));

    }

    /**
     * @api {GET} /api/v1/order/userOrdering  微信端-线上订餐-获取用户所有订餐信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-线上订餐-获取用户所有订餐信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/userOrdering?consumption_time=2019-10
     * @apiParam (请求参数说明) {string} consumption_time  消费月份
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"c_id":6,"canteen":"饭堂1","d_id":6,"dinner":"中餐","ordering_date":"2019-09-07","count":1,"type":"person_choice"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  订餐记录id
     * @apiSuccess (返回参数说明) {int} c_id 饭堂id
     * @apiSuccess (返回参数说明) {string} canteen 饭堂名称
     * @apiSuccess (返回参数说明) {int} d_id 餐次id
     * @apiSuccess (返回参数说明) {string} dinner 餐次名称
     * @apiSuccess (返回参数说明) {string} ordering_date 订餐日期
     * @apiSuccess (返回参数说明) {string} ordering_month 订餐月份
     * @apiSuccess (返回参数说明) {int} count 订餐数量
     * @apiSuccess (返回参数说明) {string}ordering_type 订餐方式：online|线上订餐；personal_choice|个人订餐
     */
    public function userOrdering()
    {
        $consumption_time = Request::param('consumption_time');
        $orders = (new OrderService())->userOrdering($consumption_time);
        return json(new SuccessMessageWithData(['data' => $orders]));

    }

    /**
     * @api {GET} /api/v1/order/online/info  微信端-线上订餐-获取饭堂餐次配置信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-线上订餐-获取饭堂餐次配置信息（确定是否可以订餐、可以定几餐）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/online/info
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":7,"c_id":6,"name":"早餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_begin":"07:00:00","meal_time_end":"08:00:00","limit_time":"09:00:00","ordered_count":1},{"id":6,"c_id":6,"name":"中餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_begin":"12:00:00","meal_time_end":"13:00:00","limit_time":"10:00:00"},{"id":7,"c_id":6,"name":"晚餐","type":"day","create_time":"2019-07-30 11:24:36","type_number":10,"meal_time_begin":"18:00:00","meal_time_end":"19:00:00","limit_time":"10:00:00"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  餐次id
     * @apiSuccess (返回参数说明) {string} name  餐次名称
     * @apiSuccess (返回参数说明) {string} fixed  餐次金额是否为采用标准金额
     * @apiSuccess (返回参数说明) {string} type  时间设置类别：day|week 1、前n天是填写数字，说明每天的餐需要提前一个天数来订餐2、周，是只能填写周一到周日，说明一周的订餐规定需要在每周某天进行下周一整周的订餐
     * @apiSuccess (返回参数说明) {int} type_number 订餐时间类别对应数量（week：0-6；周日-周六）
     * @apiSuccess (返回参数说明) {string} limit_time  订餐限制时间
     * @apiSuccess (返回参数说明) {int} ordered_count  可订餐数量
     */
    public function infoForOnline()
    {
        $info = (new OrderService())->infoForOnline();
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/order/online/save 微信端--线上订餐--新增订单
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端--线上订餐--新增订单
     * @apiExample {post}  请求样例:
     *    {
     *       "detail":[{"d_id":5,"ordering":[{"ordering_date":"2019-10-01","count":1},{"ordering_date":"2019-10-02","count":1}]},{"d_id":6,"ordering":[{"ordering_date":"2019-10-01","count":1},{"ordering_date":"2019-10-02","count":1}]}]
     *     }
     * @apiParam (请求参数说明) {obj} detail  订餐明细
     * @apiParam (请求参数说明) {int} d_id  餐次id
     * @apiParam (请求参数说明) {obj} ordering 指定餐次订餐明细
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {string} ordering|count  订餐数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function orderingOnline()
    {
        $detail = Request::param('detail');
        (new OrderService())->orderingOnline($detail);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/order/cancel 微信端-取消订餐
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-取消订餐（线上订餐/个人选菜）
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {string} id  订餐id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function orderCancel()
    {
        $id = Request::param('id');
        (new OrderService())->orderCancel($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/order/changeCount 微信端---线上订餐---修改订单预定数量
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端---线上订餐---修改订单预定数量
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "count": 1,
     *     }
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function changeOrderCount()
    {
        $id = Request::param('id');
        $count = Request::param('count');
        (new OrderService())->changeOrderCount($id, $count);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/order/changeFoods 微信端---个人选菜---修改订单菜品信息
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端---个人选菜---修改订单菜品信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "count": 2,
     *       "detail": [{"menu_id":1,"add_foods":[{"food_id":1,"name":"商品1","price":5,"count":1},{"food_id":1,"name":"商品1","price":5,"count":1},{"food_id":2,"name":"商品1","price":5,"count":1}],"update_foods":[{"detail_id":1,"count":1}],"cancel_foods":"3,4"}]
     *     }
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {obj} detail 订餐菜品明细
     * @apiParam (请求参数说明) {string} detail|menu_id 菜品类别id
     * @apiParam (请求参数说明) {obj} add_foods 新增菜品明细
     * @apiParam (请求参数说明) {string} food_id 菜品id
     * @apiParam (请求参数说明) {string} price 菜品实时单价
     * @apiParam (请求参数说明) {string} count 菜品数量
     * @apiParam (请求参数说明) {string} price 菜品实时单价
     * @apiParam (请求参数说明) {string} name 菜品名称
     * @apiParam (请求参数说明) {obj} update_foods 修改菜品明细
     * @apiParam (请求参数说明) {string} detail_id 订单菜品明细id
     * @apiParam (请求参数说明) {string} count 修改菜品数量
     * @apiParam (请求参数说明) {string} cancel_foods 取消菜品id列表，多个用逗号分隔，此id来自于订单信息中detail_id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function changeOrderFoods()
    {
        $params = Request::param();
        (new OrderService())->changeOrderFoods($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/order/personalChoice/info  微信端-个人选菜-获取订单信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-个人选菜-获取订单信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/personalChoice/info?id=8
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":8,"dinner_id":6,"canteen_id":6,"ordering_date":"2019-09-07","count":1,"type":1,"money":"10.0","foods":[{"detail_id":5,"o_id":8,"food_id":1,"menu_id":0,"count":1},{"detail_id":6,"o_id":8,"food_id":3,"menu_id":0,"count":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiParam (请求参数说明) {string} id  订单id
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiParam (请求参数说明) {int} canteen_id 饭堂id
     * @apiParam (请求参数说明) {int} type 就餐类别：1|食堂；2|外卖
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {obj} foods 订餐菜品明细
     * @apiParam (请求参数说明) {int} foods|detail_id 订单菜品明细id
     * @apiParam (请求参数说明) {int} foods|menu_id 菜品类别id
     * @apiParam (请求参数说明) {int} foods|food_id 菜品id
     * @apiParam (请求参数说明) {string} foods|count 菜品数量
     */
    public function personalChoiceInfo()
    {
        $id = Request::param('id');
        $info = (new OrderService())->personalChoiceInfo($id);
        return json(new SuccessMessageWithData(['data' => $info]));

    }

    /**
     * @api {GET} /api/v1/order/userOrderings 微信端-订单查询-订单列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-订单查询-订单列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/userOrderings?$page=1&size=100&type=3&id=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} type  类型：1|就餐；2|外卖；3|小卖部
     * @apiParam (请求参数说明) {int} id 类型为：就餐和外卖时该字段为饭堂id，类型为小卖部时，该字段为企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":8,"address":"饭堂1","type":"食堂","create_time":"2019-09-09 16:34:15","dinner":"中餐","money":"10.0"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {string} address  地点
     * @apiSuccess (返回参数说明) {float} type  类型
     * @apiSuccess (返回参数说明) {string} create_time 日期
     * @apiSuccess (返回参数说明) {int} dinner 名称
     * @apiSuccess (返回参数说明) {int} money 金额
     */
    public function userOrderings($page = 1, $size = 10)
    {
        $type = Request::param('type');
        $id = Request::param('id');
        $orders = (new OrderService())->userOrders($type, $id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $orders]));
    }

    /**
     * @api {GET} /api/v1/order/detail 微信端-订单查询-获取订单详情
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-订单查询-获取订单详情
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/detail?id=8&type=1
     * @apiParam (请求参数说明) {int} type  类型：1|就餐；2|外卖；3|小卖部
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":8,"u_id":3,"order_type":1,"ordering_type":"personal_choice|","count":1,"address_id":1,"state":1,"foods":[{"detail_id":5,"o_id":8,"food_id":1,"count":1,"name":"菜品1"},{"detail_id":6,"o_id":8,"food_id":3,"count":1,"name":"菜品2"}],"address":{"id":1,"province":"广东省","city":"江门市","area":"蓬江区","address":"江门市白石大道东4号路3栋","name":"张三","phone":"18956225230","sex":1}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {float} order_type  订单类型，饭堂订单：1|食堂，2|外卖；小卖部订单：1|到店取；2|送货上门
     * @apiSuccess (返回参数说明) {int} count  订餐数量
     * @apiSuccess (返回参数说明) {int} used  是否使用：1｜是；2｜否
     * @apiSuccess (返回参数说明) {string} ordering_type  订单类别：shop|小卖部；personal_choice|个人选菜；online|在线订餐
     * @apiSuccess (返回参数说明) {string} ordering_date  饭堂订单中订餐日期
     * @apiSuccess (返回参数说明) {obj} address 地址信息：order_type=2时此数据不为空
     * @apiSuccess (返回参数说明) {string} province  省
     * @apiSuccess (返回参数说明) {string} city  城市
     * @apiSuccess (返回参数说明) {string} area  区
     * @apiSuccess (返回参数说明) {string} address  详细地址
     * @apiSuccess (返回参数说明) {string} name  姓名
     * @apiSuccess (返回参数说明) {string} phone  手机号
     * @apiSuccess (返回参数说明) {int} sex  性别：1|男；2|女
     * @apiSuccess (返回参数说明) {obj} foods ：order_type=2时此数据不为空
     * @apiSuccess (返回参数说明) {int} food_id 菜品id
     * @apiSuccess (返回参数说明) {string} price 菜品实时单价
     * @apiSuccess (返回参数说明) {string} count 菜品数量
     * @apiSuccess (返回参数说明) {string} name 菜品名称
     * @apiSuccess (返回参数说明) {string} unit 小卖部商品单位
     * @apiSuccess (返回参数说明) {int} menu_id 菜单id
     */
    public function orderDetail()
    {
        $type = Request::param('type');
        $id = Request::param('id');
        $order = (new OrderService())->orderDetail($type, $id);
        return json(new SuccessMessageWithData(['data' => $order]));
    }

    /**
     * @api {GET} /api/v1/order/consumptionRecords 微信端-消费查询-订单列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-订单查询-订单列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/consumptionRecords?$page=1&size=100&consumption_time=2019-10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} consumption_time  消费日期
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"balance":{"hidden":2,"money":0},"consumptionMoney":20,"records":{"total":2,"per_page":20,"current_page":1,"last_page":1,"data":[{"order_id":6,"location":"企业A","order_type":"shop","used_type":"小卖部","create_time":"2019-09-28 08:14:10","ordering_date":"\/","dinner":"商品","money":-10},{"order_id":8,"location":"饭堂1","order_type":"canteen","used_type":"就餐","create_time":"2019-09-09 16:34:15","ordering_date":"2019-09-07","dinner":"中餐","money":-10}]}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {obj} balance 余额信息
     * @apiSuccess (返回参数说明) {int} hidden 是否隐藏：1｜是；2｜否
     * @apiSuccess (返回参数说明) {int} money 余额金额
     * @apiSuccess (返回参数说明) {int} consumptionMoney 月消费金额
     * @apiSuccess (返回参数说明) {obj} records  记录列表
     * @apiSuccess (返回参数说明) {int} order_id  订单id
     * @apiSuccess (返回参数说明) {string} location  消费地点
     * @apiSuccess (返回参数说明) {string} order_type  订单类别
     * @apiSuccess (返回参数说明) {string} used_type  类型
     * @apiSuccess (返回参数说明) {string} create_time 消费日期
     * @apiSuccess (返回参数说明) {string} ordering_date 餐次日期
     * @apiSuccess (返回参数说明) {int} dinner 名称
     * @apiSuccess (返回参数说明) {int} all_money 可用金额
     * @apiSuccess (返回参数说明) {int} effective_money 实际金额
     */
    public function consumptionRecords($page = 1, $size = 20)
    {
        $consumption_time = Request::param('consumption_time');
        $records = (new OrderService())->consumptionRecords($consumption_time, $page, $size);
        return json(new SuccessMessageWithData(['data' => $records]));
    }

    /**
     * @api {GET} /api/v1/order/consumptionRecords/detail 微信端-消费查询-获取订单详情
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-消费查询-获取订单详情
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/consumptionRecords/detail?order_id=8&order_type=canteen
     * @apiParam (请求参数说明) {int} order_type 饭堂订单：canteen；小卖部订单：shop
     * @apiParam (请求参数说明) {int} order_id  订单id
     * @apiSuccessExample {json} 饭堂订单返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":8,"u_id":3,"order_type":1,"ordering_type":"personal_choice|","count":1,"address_id":1,"state":1,"foods":[{"detail_id":5,"o_id":8,"food_id":1,"count":1,"name":"菜品1"},{"detail_id":6,"o_id":8,"food_id":3,"count":1,"name":"菜品2"}],"address":{"id":1,"province":"广东省","city":"江门市","area":"蓬江区","address":"江门市白石大道东4号路3栋","name":"张三","phone":"18956225230","sex":1}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} count  订餐数量
     * @apiSuccess (返回参数说明) {string} ordering_date  饭堂订单中订餐日期
     * @apiSuccess (返回参数说明) {obj} address 地址信息：order_type=2时此数据不为空
     * @apiSuccess (返回参数说明) {string} address|province  省
     * @apiSuccess (返回参数说明) {string} address|city  城市
     * @apiSuccess (返回参数说明) {string} address|area  区
     * @apiSuccess (返回参数说明) {string} address|address  详细地址
     * @apiSuccess (返回参数说明) {string} address|name  姓名
     * @apiSuccess (返回参数说明) {string} address|phone  手机号
     * @apiSuccess (返回参数说明) {int} address|sex  性别：1|男；2|女
     * @apiSuccess (返回参数说明) {obj} foods ：order_type=2时此数据不为空
     * @apiSuccess (返回参数说明) {int} foods|food_id 菜品id
     * @apiSuccess (返回参数说明) {string} foods|price 菜品实时单价
     * @apiSuccess (返回参数说明) {string} foods|count 菜品数量
     * @apiSuccess (返回参数说明) {string} foods|name 菜品名称
     * @apiSuccess (返回参数说明) {string} foods|unit 小卖部商品单位
     */
    public function recordsDetail()
    {
        $order_type = Request::param('order_type');
        $order_id = Request::param('order_id');
        $info = (new OrderService())->recordsDetail($order_type, $order_id);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * * @api {GET} /api/v1/order/managerOrders 微信端-总订餐查询-餐次订餐信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-总订餐查询-餐次订餐信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/managerOrders?canteen_id=6&consumption_time=2019-09-07
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id
     * @apiParam (请求参数说明) {string} consumption_time  消费日期
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":5,"name":"早餐","all":0,"used":0,"noOrdering":0,"orderingNoMeal":0},{"id":6,"name":"中餐","all":1,"used":0,"noOrdering":0,"orderingNoMeal":1},{"id":7,"name":"晚餐","all":0,"used":0,"noOrdering":0,"orderingNoMeal":0}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 餐次id
     * @apiSuccess (返回参数说明) {string} name 餐次名称
     * @apiSuccess (返回参数说明) {int} all  订餐数量
     * @apiSuccess (返回参数说明) {int} used  已就餐数量
     * @apiSuccess (返回参数说明) {int} noOrdering  未订餐就餐数量
     * @apiSuccess (返回参数说明) {int} orderingNoMeal  订餐未就餐数量
     */
    public function managerOrders()
    {
        $canteen_id = Request::param('canteen_id');
        $consumption_time = Request::param('consumption_time');
        $orders = (new OrderService())->managerOrders($canteen_id, $consumption_time);
        return json(new SuccessMessageWithData(['data' => $orders]));

    }

    /**
     * * @api {GET} /api/v1/order/managerDinnerStatistic 微信端-总订餐查询-点击订餐数量获取菜品统计（有选菜/无选菜）
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-总订餐查询-点击订餐数量获取菜品统计（有选菜/无选菜）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/managerDinnerStatistic?dinner_id=6&consumption_time=2019-09-07&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} dinner_id  残次id
     * @apiParam (请求参数说明) {string} consumption_time  消费日期
     * @apiSuccessExample {json} 无选菜返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"haveFoods":2,"statistic":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"username":"张三","phone":"18956225230"}]}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} haveFoods 是否有选菜：1｜是；2｜否
     * @apiSuccessExample {json}有选菜返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"haveFoods":1,"statistic":{"total":2,"per_page":20,"current_page":1,"last_page":1,"data":[{"order_id":8,"food_id":1,"name":"菜品1","count":1},{"order_id":8,"food_id":3,"name":"菜品2","count":1}]}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} haveFoods 是否有选菜：1｜是；2｜否
     * @apiSuccess (返回参数说明) {obj} statistic 订餐数量统计
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} food_id 菜品id
     * @apiSuccess (返回参数说明) {string} name 菜品名称
     * @apiSuccess (返回参数说明) {string} count 订餐份数
     */
    public function managerDinnerStatistic($page = 1, $size = 20)
    {
        $dinner_id = Request::param('dinner_id');
        $consumption_time = Request::param('consumption_time');
        $info = (new OrderService())->managerDinnerStatistic($dinner_id, $consumption_time, $page, $size);
        return json(new SuccessMessageWithData(['data' => $info]));

    }

    /**
     * * @api {GET} /api/v1/order/usersStatistic 微信端-总订餐查询-点击订餐数量获取订餐人员统计
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-总订餐查询-点击订餐数量获取订餐人员统计（已就餐/未订餐就餐/订餐未就餐）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/usersStatistic?dinner_id=6&consumption_time=2019-09-07&page=1&size=20&consumption_type=used
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} dinner_id  餐次id
     * @apiParam (请求参数说明) {string} consumption_time  消费日期
     * @apiParam (请求参数说明) {string} consumption_type  订餐统计类别：used｜已就餐；noOrdering｜未订餐就餐；orderingNoMeal｜订餐未就餐
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"username":"张三","phone":"18956225230","count":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} username 姓名
     * @apiSuccess (返回参数说明) {string} phone 手机号
     * @apiSuccess (返回参数说明) {string} count 订餐份数
     */
    public function orderUsersStatistic($page = 1, $size = 20)
    {
        $dinner_id = Request::param('dinner_id');
        $consumption_time = Request::param('consumption_time');
        $consumption_type = Request::param('consumption_type');
        $info = (new OrderService())->orderUsersStatistic($dinner_id, $consumption_time, $consumption_type, $page, $size);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {GET} /api/v1/order/foodUsersStatistic 微信端-总订餐查询-点击有选菜订餐数量中订餐人数获取人员统计
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-总订餐查询-点击有选菜订餐数量中订餐人数获取人员统计
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/foodUsersStatistic?dinner_id=6&consumption_time=2019-09-07&page=1&size=20&food_id=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} dinner_id  餐次id
     * @apiParam (请求参数说明) {string} consumption_time  消费日期
     * @apiParam (请求参数说明) {int} food_id  菜品id
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"username":"张三","phone":"18956225230"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} username 姓名
     * @apiSuccess (返回参数说明) {string} phone 手机号
     */
    public function foodUsersStatistic($page = 1, $size = 20)
    {
        $dinner_id = Request::param('dinner_id');
        $food_id = Request::param('food_id');
        $consumption_time = Request::param('consumption_time');
        $info = (new OrderService())->foodUsersStatistic($dinner_id, $food_id, $consumption_time, $page, $size);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/order/handelOrderedNoMeal 微信端-总订餐查询-订餐未就餐-一键扣费
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-个人选菜-新增订单
     * @apiExample {post}  请求样例:
     *    {
     *       "consumption_time": "2019-09-07",
     *       "dinner_id": 1
     *     }
     * @apiParam (请求参数说明) {string} consumption_time  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function handelOrderedNoMeal()
    {
        $dinner_id = Request::param('dinner_id');
        $consumption_time = Request::param('consumption_time');
        (new OrderService())->handelOrderedNoMeal($dinner_id, $consumption_time);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/order/orderStatistic CMS管理端-订餐管理-订餐统计
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-订餐管理-订餐统计
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderStatistic?company_ids=6&canteen_id=1&time_begin=2019-09-07&time_end=2019-09-07&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"ordering_date":"2019-09-07","company":"一级企业","canteen":"饭堂1","dinner":"中餐","count":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} ordering_date 订餐日期
     * @apiSuccess (返回参数说明) {string} company 企业
     * @apiSuccess (返回参数说明) {string} canteen 饭堂
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {string} count 订餐人数
     */
    public function orderStatistic($page = 1, $size = 20, $canteen_id = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $list = (new OrderStatisticService())->statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $list]));
    }

    /**
     * @api {GET} /api/v1/order/orderStatistic/export CMS管理端-订餐管理-订餐统计-导出报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-订餐管理-订餐统计-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderStatistic/export?company_ids=6&canteen_id=1&time_begin=2019-09-07&time_end=2019-09-07
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportOrderStatistic($canteen_id = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $list = (new OrderStatisticService())->exportStatistic($time_begin, $time_end, $company_ids, $canteen_id);
        return json(new SuccessMessageWithData(['data' => $list]));
    }


    /**
     * @api {GET} /api/v1/order/orderStatistic/detail CMS管理端-订餐管理-订餐明细
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-订餐管理-订餐明细
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderStatistic/detail?company_ids=&canteen_id=0&time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20&department_id=2&dinner_id=0&name=&phone&type=3
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0或者不传，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} department_id  部门id：选择企业时才可以选择具体的部门信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {string} phone  手机号查询
     * @apiParam (请求参数说明) {string} name  姓名查询
     * @apiParam (请求参数说明) {int} type  订餐类型：1｜饭堂就餐；2｜外卖；3｜全部
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"ordering_date":"2019-09-07","company":"一级企业","canteen":"饭堂1","dinner":"中餐","count":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} order_id 订单id
     * @apiSuccess (返回参数说明) {string} ordering_date 订餐日期
     * @apiSuccess (返回参数说明) {string} canteen 消费地点
     * @apiSuccess (返回参数说明) {string} department 部门
     * @apiSuccess (返回参数说明) {string} username 用户姓名
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     */
    public function orderStatisticDetail($page = 1, $size = 20, $name = '', $phone = '', $canteen_id = 0, $department_id = 0, $dinner_id = 0, $type = 3)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $list = (new OrderStatisticService())->orderStatisticDetail($company_ids, $time_begin,
            $time_end, $page, $size, $name,
            $phone, $canteen_id, $department_id,
            $dinner_id, $type);
        return json(new SuccessMessageWithData(['data' => $list]));

    }

    /**
     * @api {GET} /api/v1/order/orderStatistic/detail/export CMS管理端-订餐管理-订餐明细-导出报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-订餐管理-订餐明细-订餐明细-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderStatistic/detail/export?company_ids=&canteen_id=0&time_begin=2019-09-07&time_end=2019-12-07&department_id=2&dinner_id=0&name=&phone&type=3
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0或者不传，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} department_id  部门id：选择企业时才可以选择具体的部门信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {string} phone  手机号查询
     * @apiParam (请求参数说明) {string} name  姓名查询
     * @apiParam (请求参数说明) {int} type  订餐类型：1｜饭堂就餐；2｜外卖；3｜全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportOrderStatisticDetail($name = '', $phone = '',
                                               $canteen_id = 0, $department_id = 0,
                                               $dinner_id = 0, $type = 3)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $list = (new OrderStatisticService())->exportOrderStatisticDetail($company_ids, $time_begin,
            $time_end, $name,
            $phone, $canteen_id, $department_id,
            $dinner_id, $type);
        return json(new SuccessMessageWithData(['data' => $list]));

    }

    /**
     * @api {GET} /api/v1/order/orderSettlement CMS管理端-结算管理-消费明细
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-结算管理-消费明细
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderSettlement?company_ids=&canteen_id=0&time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20&department_id=2&dinner_id=0&name=&phone&consumption_type=4
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0或者不传，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} department_id  部门id：选择企业时才可以选择具体的部门信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {string} phone  手机号查询
     * @apiParam (请求参数说明) {string} name  姓名查询
     * @apiParam (请求参数说明) {int} consumption_type  消费类型，1：订餐就餐；2：订餐未就餐；3：未订餐就餐；4：补录；5：全部
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"20","current_page":1,"last_page":1,"data":[{"order_id":8,"used_time":"0000-00-00 00:00:00","username":"张三","phone":"18956225230","canteen":"饭堂1","department":"董事会-修改","dinner":"中餐","booking":1,"used":2,"consumption_type":2}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} order_id 订单id
     * @apiSuccess (返回参数说明) {string} used_time 消费时间
     * @apiSuccess (返回参数说明) {string} canteen 消费地点
     * @apiSuccess (返回参数说明) {string} department 部门
     * @apiSuccess (返回参数说明) {string} name 用户姓名
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {string} consumption_type  消费类型，1：订餐就餐；2：订餐未就餐；3：未订餐就餐；4：补录
     */
    public function orderSettlement($page = 1, $size = 20, $name = '',
                                    $phone = '',
                                    $canteen_id = 0,
                                    $department_id = 0,
                                    $dinner_id = 0, $consumption_type = 5)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $records = (new OrderStatisticService())->orderSettlement($page, $size,
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids);
        return json(new SuccessMessageWithData(['data' => $records]));
    }

    /**
     * @api {GET} /api/v1/order/orderSettlement/export CMS管理端-结算管理-消费明细-导出
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-结算管理-消费明细-导出
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderSettlement/export?company_ids=&canteen_id=0&time_begin=2019-09-07&time_end=2019-12-07&department_id=2&dinner_id=0&name=&phone&consumption_type=4
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id：选择某一个饭堂时传入饭堂id，此时企业id为0或者不传，选择全部时，饭堂id传入0
     * @apiParam (请求参数说明) {string} department_id  部门id：选择企业时才可以选择具体的部门信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {string} phone  手机号查询
     * @apiParam (请求参数说明) {string} name  姓名查询
     * @apiParam (请求参数说明) {int} consumption_type  消费类型，1：订餐就餐；2：订餐未就餐；3：未订餐就餐；4：补录；5：全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportOrderSettlement($name = '',
                                          $phone = '',
                                          $canteen_id = 0,
                                          $department_id = 0,
                                          $dinner_id = 0, $consumption_type = 5)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $records = (new OrderStatisticService())->exportOrderSettlement(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids);
        return json(new SuccessMessageWithData(['data' => $records]));
    }

    /**
     * @api {GET} /api/v1/order/personChoice/info  微信端-个人选菜-获取饭堂餐次配置信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-个人选菜-获取饭堂餐次配置信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/personChoice/info?day=2019-11-07
     * @apiParam (请求参数说明) {string} day  查询日期
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":7,"name":"早餐","fixed":2,"type":"day","type_number":10,"limit_time":"09:00:00","ordered_count":1,"menus":[{"id":3,"d_id":5,"category":"汤","status":2,"count":0},{"id":4,"d_id":5,"category":"荤菜","status":1,"count":0},{"id":5,"d_id":5,"category":"荤菜","status":1,"count":0},{"id":6,"d_id":5,"category":"汤","status":1,"count":0},{"id":7,"d_id":5,"category":"素菜","status":1,"count":0}]},{"id":6,"name":"中餐","fixed":2,"type":"day","type_number":10,"limit_time":"10:00:00","menus":[{"id":1,"d_id":6,"category":"荤菜","status":1,"count":3},{"id":2,"d_id":6,"category":"汤","status":2,"count":0}]},{"id":7,"name":"晚餐","fixed":2,"type":"day","type_number":10,"limit_time":"10:00:00","menus":[]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  餐次id
     * @apiSuccess (返回参数说明) {string} name  餐次名称
     * @apiSuccess (返回参数说明) {string} fixed  餐次金额是否为采用标准金额
     * @apiSuccess (返回参数说明) {string} type  时间设置类别：day|week 1、前n天是填写数字，说明每天的餐需要提前一个天数来订餐2、周，是只能填写周一到周日，说明一周的订餐规定需要在每周某天进行下周一整周的订餐
     * @apiSuccess (返回参数说明) {int} type_number 订餐时间类别对应数量（week：0-6；周日-周六）
     * @apiSuccess (返回参数说明) {string} limit_time  订餐限制时间
     * @apiSuccess (返回参数说明) {int} ordered_count  可订餐数量
     * @apiSuccess (返回参数说明) {int} ordering_count  已订餐数量
     * @apiSuccess (返回参数说明) {obj} menus  菜品类别信息
     * @apiSuccess (返回参数说明) {int} id  菜品类别id
     * @apiSuccess (返回参数说明) {int} count  可选数量
     */
    public function infoForPersonChoiceOnline($day)
    {
        $info = (new OrderService())->infoForPersonChoiceOnline($day);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {GET} /api/v1/order/materialsStatistic CMS管理端-材料管理-材料下单表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-材料管理-材料下单表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/materialsStatistic?canteen_id=0&time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"list":{"total":8,"per_page":"20","current_page":1,"last_page":1,"data":[{"order_id":8,"ordering_date":"2019-09-07","material":null,"dinner_id":6,"dinner":"中餐","order_count":null,"material_price":0,"material_count":null},{"order_id":8,"ordering_date":"2019-09-07","material":"土豆","dinner_id":6,"dinner":"中餐","order_count":10,"material_price":5,"material_count":10},{"order_id":8,"ordering_date":"2019-09-07","material":"牛肉","dinner_id":6,"dinner":"中餐","order_count":15,"material_price":60,"material_count":15},{"order_id":8,"ordering_date":"2019-09-07","material":"西红柿","dinner_id":6,"dinner":"中餐","order_count":10,"material_price":5,"material_count":10},{"order_id":16,"ordering_date":"2019-11-07","material":null,"dinner_id":6,"dinner":"中餐","order_count":null,"material_price":0,"material_count":null},{"order_id":18,"ordering_date":"2019-11-18","material":"土豆","dinner_id":6,"dinner":"中餐","order_count":10,"material_price":5,"material_count":10},{"order_id":18,"ordering_date":"2019-11-18","material":"牛肉","dinner_id":6,"dinner":"中餐","order_count":15,"material_price":60,"material_count":15},{"order_id":18,"ordering_date":"2019-11-18","material":"西红柿","dinner_id":6,"dinner":"中餐","order_count":10,"material_price":5,"material_count":10}]},"money":2000}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} list 报表列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} order_id 订单id
     * @apiSuccess (返回参数说明) {int} detail_id 订单明细id
     * @apiSuccess (返回参数说明) {string} ordering_date 日期
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {string} material 材料名称
     * @apiSuccess (返回参数说明) {float} order_count 材料数量
     * @apiSuccess (返回参数说明) {float} material_count 订货数量
     * @apiSuccess (返回参数说明) {float} material_price 订货单价
     * @apiSuccess (返回参数说明) {float} money 报表总价
     */
    public function orderMaterialsStatistic($page = 1, $size = 20)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $canteen_id = Request::param('canteen_id');
        $statistic = (new OrderStatisticService())
            ->orderMaterialsStatistic($page, $size, $time_begin, $time_end, $canteen_id);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * @api {POST} /api/v1/order/material/update CMS管理端-材料管理-材料下单表-提交生成报表
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料管理-材料下单表-提交材料修改
     * @apiExample {post}  请求样例:
     *    {
     *       "title": "报表名称",
     *       "canteen_id":3,
     *       "time_begin": 2019-11-01,
     *       "time_end": 2019-11-20,
     *       "materials": [{"dinner_id": 8,"material":"西红柿","price": 1,"count": 1,"ordering_date":"2019-11-07"}]
     *     }
     * @apiParam (请求参数说明) {string} title  报表名称
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id
     * @apiParam (请求参数说明) {string} time_begin  开始时间
     * @apiParam (请求参数说明) {string} time_end  结束时间
     * @apiParam (请求参数说明) {string} materials 修改材料价格明细：json字符串
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiParam (请求参数说明) {string} material 材料名称
     * @apiParam (请求参数说明) {int} price 单价-元
     * @apiParam (请求参数说明) {int} count 订货数量-kg
     * @apiParam (请求参数说明) {int} ordering_date 订餐日期
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function updateOrderMaterial()
    {
        $params = Request::param();
        (new OrderStatisticService())->updateOrderMaterial($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/order/material/reports CMS管理端-材料管理-入库材料报表-列表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-材料管理-入库材料报表-列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/material/reports?canteen_id=6&time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} canteen_id  饭堂id
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"id":4,"canteen_id":6,"title":"2019-11-01~2019-11-07材料报表","create_time":"2019-11-08 00:10:34","canteen":{"id":6,"name":"饭堂1"}}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 报表id
     * @apiSuccess (返回参数说明) {int} title 报表名称
     * @apiSuccess (返回参数说明) {string} create_time 日期
     * @apiSuccess (返回参数说明) {obj} canteen 饭堂信息
     * @apiSuccess (返回参数说明) {string} name 饭堂名称
     */
    public function materialReports($page = 1, $size = 20)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $canteen_id = Request::param('canteen_id');
        $report = (new OrderStatisticService())
            ->materialReports($page, $size, $time_begin, $time_end, $canteen_id);
        return json(new SuccessMessageWithData(['data' => $report]));
    }

    /**
     * @api {GET} /api/v1/order/material/report CMS管理端-材料管理-入库材料报表-报表详情
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-材料管理-入库材料报表-报表详情
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/material/report?id=7&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} id 报表id
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"list":{"total":5,"per_page":20,"current_page":1,"last_page":1,"data":[{"order_id":8,"ordering_date":"2019-09-07","material":null,"dinner_id":6,"dinner":"中餐","order_count":"1","material_count":"1","material_price":0},{"order_id":8,"ordering_date":"2019-09-07","material":"土豆","dinner_id":6,"dinner":"中餐","order_count":"1","material_count":"1","material_price":5},{"order_id":8,"ordering_date":"2019-09-07","material":"牛肉","dinner_id":6,"dinner":"中餐","order_count":"1","material_count":"1","material_price":60},{"order_id":8,"ordering_date":"2019-09-07","material":"西红柿","dinner_id":6,"dinner":"中餐","order_count":"1","material_count":"1","material_price":5},{"order_id":16,"ordering_date":"2019-11-07","material":null,"dinner_id":6,"dinner":"中餐","order_count":"1","material_count":"1","material_price":0}]},"money":1000}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} list 报表列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} ordering_date
     * @apiSuccess (返回参数说明) {int} order_id 订单id
     * @apiSuccess (返回参数说明) {int} dinner_id 餐次id
     * @apiSuccess (返回参数说明) {string} ordering_date 日期
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {string} material 材料名称
     * @apiSuccess (返回参数说明) {float} order_count 材料数量
     * @apiSuccess (返回参数说明) {float} material_count 订货数量
     * @apiSuccess (返回参数说明) {float} material_price 订货单价
     * @apiSuccess (返回参数说明) {float} money 报表总价
     */
    public function materialReport($page = 1, $size = 20)
    {
        $report_id = Request::param('id');
        $report = (new OrderStatisticService())->materialReport($report_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $report]));
    }

    /**
     * @api {POST} /api/v1/order/material/report/delete  CMS管理端-材料管理-入库材料报表-废除
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料管理-入库材料报表-废除
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 4
     *     }
     * @apiParam (请求参数说明) {int} id 报表id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function materialReportHandel()
    {
        $id = Request::param('id');
        $report = MaterialReportT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$report) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/order/consumptionStatistic CMS管理端-结算管理-结算报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-结算管理-结算报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/consumptionStatistic?time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20&category_id=0&product_id=0&status=0&status=1&department_id=0&username=
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} department_id  部门id：全部传入0
     * @apiParam (请求参数说明) {string} username  用户名
     * @apiParam (请求参数说明) {int} staff_type_id  人员类型id：全部传入0
     * @apiParam (请求参数说明) {int} canteen_ids  消费地点，饭堂id：全部传入0
     * @apiParam (请求参数说明) {int} company_ids  企业id：全部，将所有ID用逗号分隔
     * @apiParam (请求参数说明) {int} status  消费类型：全部传入0；1：订餐就餐；2：未订餐就餐；3：未订餐就餐；4：补录操作
     * @apiParam (请求参数说明) {int} type  汇总类型：1：按部门进行汇总；2：按姓名进行汇总；3：按人员类型进行汇总；4：按消费地点进行汇总；5：按消费类型进行汇总
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}汇总类型为：1/3/4/5返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"statistic":[{"statistic":"股东","time_begin":"2019-10-11","time_end":"2019-11-30","department":"股东","dinnerStatistic":[{"dinner_id":6,"dinner":"中餐","order_count":"8","order_money":28},{"dinner_id":5,"dinner":"早餐","order_count":"2","order_money":4},{"dinner_id":7,"dinner":"晚餐","order_count":"5","order_money":3}]}],"allMoney":35,"allCount":15}}
     * @apiSuccessExample {json}汇总类型为：2返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"statistic":{"total":10,"per_page":10,"current_page":1,"last_page":1,"data":[{"staff_id":365,"statistic":"LANGBIN","username":"LANGBIN","department":"B1部门","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":368,"statistic":"rush","username":"rush","department":"股东","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":369,"statistic":"rush23","username":"rush23","department":"股东","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":370,"statistic":"rush233","username":"rush233","department":"股东","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":371,"statistic":"langbin","username":"langbin","department":"股东","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":372,"statistic":"llb","username":"llb","department":"股东","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":373,"statistic":"13510","username":"13510","department":"股东","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":374,"statistic":"langbin","username":"langbin","department":"股东","dinner_statistic":[{"staff_id":374,"dinner_id":6,"dinner":"中餐","order_count":"8","order_money":28},{"staff_id":374,"dinner_id":5,"dinner":"早餐","order_count":"2","order_money":4},{"staff_id":374,"dinner_id":7,"dinner":"晚餐","order_count":"5","order_money":3}],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":375,"statistic":"rush2333","username":"rush2333","department":"股东","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"},{"staff_id":376,"statistic":"1103","username":"1103","department":"股东","dinner_statistic":[],"time_begin":"2019-10-11","time_end":"2019-11-30"}]},"allMoney":35,"allCount":"15"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} statistic 统计变量
     * @apiSuccess (返回参数说明) {string} time_begin 开始时间
     * @apiSuccess (返回参数说明) {string} time_end 结束时间
     * @apiSuccess (返回参数说明) {string} username 姓名
     * @apiSuccess (返回参数说明) {string} department 部门
     * @apiSuccess (返回参数说明) {string} status 消费类型
     * @apiSuccess (返回参数说明) {string} canteen 消费地点
     * @apiSuccess (返回参数说明) {string} staff_type 人员类型
     * @apiSuccess (返回参数说明) {int} allMoney 合计-总数量
     * @apiSuccess (返回参数说明) {int} allCount 合计-总金额
     */
    public function consumptionStatistic($canteen_ids = 0, $status = 0, $type = 1,
                                         $department_id = 0, $username = '', $staff_type_id = 0, $page = 1, $size = 10)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $statistic = (new OrderStatisticService())->consumptionStatistic($canteen_ids, $status, $type,
            $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_ids, $page, $size);
        return json(new SuccessMessageWithData(['data' => $statistic]));

    }

    /**
     * @api {GET} /api/v1/order/consumptionStatistic/export CMS管理端-结算管理-结算报表-导出报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-结算管理-结算报表-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/consumptionStatistic/export?time_begin=2019-09-07&time_end=2019-12-07&category_id=0&product_id=0&status=0&status=1&department_id=0&username=
     * @apiParam (请求参数说明) {int} department_id  部门id：全部传入0
     * @apiParam (请求参数说明) {string} username  用户名
     * @apiParam (请求参数说明) {int} staff_type_id  人员类型id：全部传入0
     * @apiParam (请求参数说明) {int} canteen_ids  消费地点，饭堂id：全部传入0
     * @apiParam (请求参数说明) {int} company_ids  企业id：全部，将所有ID用逗号分隔
     * @apiParam (请求参数说明) {int} status  消费类型：全部传入0；1：订餐就餐；2：未订餐就餐；3：未订餐就餐；4：补录操作
     * @apiParam (请求参数说明) {int} type  汇总类型：1：按部门进行汇总；2：按姓名进行汇总；3：按人员类型进行汇总；4：按消费地点进行汇总；5：按消费类型进行汇总
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportConsumptionStatistic($canteen_ids = 0, $status = 0, $type = 1,
                                               $department_id = 0, $username = '', $staff_type_id = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $statistic = (new OrderStatisticService())->exportConsumptionStatistic($canteen_ids, $status, $type,
            $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_ids);
        return json(new SuccessMessageWithData(['data' => $statistic]));

    }


    /**
     * @api {POST} /api/v1/order/changeAddress CMS管理端-修改订单地址
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料管理-材料下单表-提交材料修改
     * @apiExample {post}  请求样例:
     *    {
     *       "order_id": 1,
     *       "address_id":3,
     * }
     * @apiParam (请求参数说明) {string} order_id  订单id
     * @apiParam (请求参数说明) {string} address_id  地址id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function changeOrderAddress()
    {
        $order_id = Request::param('order_id');
        $address_id = Request::param('address_id');
        (new OrderService())->changeOrderAddress($order_id, $address_id);
        return json(new SuccessMessage());
    }


}
