<?php


namespace app\api\controller\v2;


use app\api\service\v2\OrderService as OrderServiceV2;
use app\api\service\OrderStatisticService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Order
{
    /**
     * @api {GET} /api/v2/order/orderSettlement CMS管理端-结算管理(分账)-消费明细
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-结算管理-消费明细
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderSettlement?company_ids=&canteen_id=0&time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20&department_id=2&dinner_id=0&name=&phone&consumption_type=4
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} type  消费地点类型：shop 小卖部；canteen:饭堂;all 全部
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  消费地点id：选择某一个饭堂时传入消费地点（饭堂/是小卖部）id，此时企业id为0或者不传，选择全部时，消费地点id传入0
     * @apiParam (请求参数说明) {string} department_id  部门id：选择企业时才可以选择具体的部门信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息(小卖部没有)，否则传0或者不传
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {string} phone  手机号查询
     * @apiParam (请求参数说明) {string} name  姓名查询
     * @apiParam (请求参数说明) {int} consumption_type 消费类型，0:全部 1：订餐就餐；2：订餐未就餐；3：未订餐就餐；4：补充；5：补扣；6：小卖部消费；7：小卖部退款
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
     * @apiSuccess (返回参数说明) {string} account 账户明细
     * @apiSuccess (返回参数说明) {string} consumption_type
     */
    public function orderSettlement($page = 1, $size = 20, $name = '',
                                    $phone = '',
                                    $canteen_id = 0,
                                    $department_id = 0,
                                    $dinner_id = 0, $consumption_type = 0, $type = "canteen")
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $records = (new OrderStatisticService())->orderSettlementWithAccount($page, $size,
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        return json(new SuccessMessageWithData(['data' => $records]));
    }


    /**
     * @api {GET} /api/v2/order/orderSettlement/export CMS管理端-结算管理(分账)-消费明细-导出
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-结算管理-消费明细-导出
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/orderSettlement/export?company_ids=&canteen_id=0&time_begin=2019-09-07&time_end=2019-12-07&department_id=2&dinner_id=0&name=&phone&consumption_type=4
     * @apiParam (请求参数说明) {string} type  消费地点类型：shop 小卖部；canteen:饭堂；all 全部
     * @apiParam (请求参数说明) {string} company_ids  企业id：选择全部时，将企业id用逗号分隔，例如：1,2，此时饭堂id传入0;选择某一个企业时传入企业id
     * @apiParam (请求参数说明) {string} canteen_id  消费地点id：选择某一个饭堂时传入消费地点（饭堂/是小卖部）id，此时企业id为0或者不传，选择全部时，消费地点id传入0
     * @apiParam (请求参数说明) {string} department_id  部门id：选择企业时才可以选择具体的部门信息，否则传0或者不传
     * @apiParam (请求参数说明) {string} dinner_id  餐次id：选择饭堂时才可以选择具体的餐次信息(小卖部没有)，否则传0或者不传
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {string} phone  手机号查询
     * @apiParam (请求参数说明) {string} name  姓名查询
     * @apiParam (请求参数说明) {int} consumption_type 消费类型，0:全部 1：订餐就餐；2：订餐未就餐；3：未订餐就餐；4：补充；5：补扣；6：小卖部消费；7：小卖部退款
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
                                          $dinner_id = 0, $consumption_type = 0, $type = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $records = (new OrderStatisticService())->exportOrderSettlementWithAccount(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        return json(new SuccessMessageWithData(['data' => $records]));
    }


    /**
     * @api {GET} /api/v2/order/consumptionStatistic CMS管理端-结算管理(分账)-结算报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-结算管理-结算报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v2/order/consumptionStatistic?time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20&category_id=0&product_id=0&status=0&status=1&department_id=0&username=&phone=18956225230
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} order_type 消费地点类型：shop 小卖部；canteen ：饭堂;all:全部
     * @apiParam (请求参数说明) {int} department_id  部门id：全部传入0
     * @apiParam (请求参数说明) {string} username  用户名
     * @apiParam (请求参数说明) {int} staff_type_id  人员类型id：全部传入0
     * @apiParam (请求参数说明) {int} canteen_ids  消费地点，饭堂id/小卖部id：全部传入0
     * @apiParam (请求参数说明) {int} company_ids  企业id：全部，将所有ID用逗号分隔
     * @apiParam (请求参数说明) {int} status  消费类型：全部传入0；1：订餐就餐；2：订餐未就餐；3：未订餐就餐；4：补充操作；5：补扣操作；6：小卖部消费；7：小卖部退款
     * @apiParam (请求参数说明) {int} type  汇总类型：1：按部门进行汇总；2：按姓名进行汇总；3：按人员类型进行汇总；4：按消费地点进行汇总；5：按消费类型进行汇总
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {string} phone  手机号
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
                                         $department_id = 0, $username = '', $staff_type_id = 0,
                                         $phone = '', $page = 1, $size = 10, $order_type = "canteen")
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $statistic = (new OrderStatisticService())->consumptionStatistic($canteen_ids, $status, $type,
            $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_ids, $phone, $page, $size, $order_type);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * @api {GET} /api/v2/order/consumptionStatistic/export CMS管理端-结算管理(分账管理)-结算报表-导出报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-结算管理-结算报表-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v2/order/consumptionStatistic/export?time_begin=2019-09-07&time_end=2019-12-07&category_id=0&product_id=0&status=0&status=1&department_id=0&username=&phone=18956225230
     * @apiParam (请求参数说明) {int} order_type 消费地点类型：shop 小卖部；canteen ：饭堂；all:全部
     * @apiParam (请求参数说明) {int} department_id  部门id：全部传入0
     * @apiParam (请求参数说明) {string} username  用户名
     * @apiParam (请求参数说明) {int} staff_type_id  人员类型id：全部传入0
     * @apiParam (请求参数说明) {int} canteen_ids  消费地点，饭堂id/小卖部id：全部传入0
     * @apiParam (请求参数说明) {int} company_ids  企业id：全部，将所有ID用逗号分隔
     * @apiParam (请求参数说明) {int} status  消费类型：全部传入0；1：订餐就餐；2：订餐未就餐；3：未订餐就餐；4：补充操作；5：补扣操作;
     * @apiParam (请求参数说明) {int} type  汇总类型：1：按部门进行汇总；2：按姓名进行汇总；3：按人员类型进行汇总；4：按消费地点进行汇总；5：按消费类型进行汇总
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportConsumptionStatistic($canteen_ids = 0, $status = 0, $type = 1,
                                               $department_id = 0, $username = '', $staff_type_id = 0, $phone = "", $order_type = "canteen")
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_ids = Request::param('company_ids');
        $statistic = (new OrderStatisticService())->exportConsumptionStatisticWithAccount($canteen_ids, $status, $type,
            $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_ids, $phone, $order_type);
        return json(new SuccessMessageWithData(['data' => $statistic]));

    }

    /**
     * @api {POST} /api/v2/order/money 微信端-个人选菜-提交订单时查看金额信息
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-个人选菜-提交订单时查看金额信息
     * @apiExample {post}  请求样例:
     *    {
     *       "type": 1,
     *       "orders": [{"ordering_date":"2021-03-07","order":[{"dinner_id":135,"dinner":"早餐","count":1,"foods":[{"menu_id":101,"food_id":999,"name":"商品1","price":5,"count":1}]},{"dinner_id":136,"dinner":"午餐","count":1,"foods":[{"menu_id":102,"food_id":343,"name":"cs","price":1,"count":1},{"menu_id":102,"food_id":128,"name":"清炒苦瓜","price":3,"count":1}]}]}]
     * }
     * @apiParam (请求参数说明) {int} type 就餐类别：1|食堂；2|外卖
     * @apiParam (请求参数说明) {obj} orders  订单信息
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiParam (请求参数说明) {int} dinner 餐次名称
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {obj} foods 订餐菜品明细
     * @apiParam (请求参数说明) {string} menu_id 菜品类别id
     * @apiParam (请求参数说明) {string} food_id 菜品id
     * @apiParam (请求参数说明) {string} price 菜品实时单价
     * @apiParam (请求参数说明) {string} count 菜品数量
     * @apiParam (请求参数说明) {string} name 菜品名称
     * @apiSuccessExample {json} 余额不足返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"type":"balance","outsider":2,"money":"99962","money_type":"overdraw"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} type :order：订单金额;balance:余额提示
     * @apiSuccess (返回参数说明) {int} money_type :余额类型：overdraw：透支金额；user_balance:余额信息
     * @apiSuccess (返回参数说明) {int} money 余额
     * @apiSuccessExample {json} 检测成功返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"type":"order","prepare_id":"C306993891626218","order":[{"id":89,"prepare_order_id":"C306993891626453","type":1,"ordering_date":"2021-03-07","dinner":"早餐","money":"5.00","sub_money":"2.00","delivery_fee":"0.00","foods":[{"prepare_order_id":"C306993891626453","name":"商品1","price":"5.00","count":1}]},{"id":90,"prepare_order_id":"C306993891627657","type":1,"ordering_date":"2021-03-07","dinner":"午餐","money":"4.00","sub_money":"1.00","delivery_fee":"0.00","foods":[{"prepare_order_id":"C306993891627657","name":"cs","price":"1.00","count":1},{"prepare_order_id":"C306993891627657","name":"清炒苦瓜","price":"3.00","count":1}]}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {string} type 返回类型信息：order：订单金额;balance:余额提示
     * @apiSuccess (返回参数说明) {string} outsider 是否外来人员：1是：2：否
     * @apiSuccess (返回参数说明) {string} prepare_id 预订单ID（提交订单上传）
     * @apiSuccess (返回参数说明) {obj} order 订单金额信息
     * @apiSuccess (返回参数说明) {int} type 就餐类别：1|食堂；2|外卖
     * @apiSuccess (返回参数说明) {string} ordering_date   订餐日期
     * @apiSuccess (返回参数说明) {string} consumption_type  扣费类别：one 一次扣费；more 多次扣费（多次扣费订单信息在子订单列表：sub中）
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {int} money 标准金额
     * @apiSuccess (返回参数说明) {int} sub_money 标准金额
     * @apiSuccess (返回参数说明) {int} delivery_fee 外卖配送费
     * @apiSuccess (返回参数说明) {obj} foods 菜品信息
     * @apiSuccess (返回参数说明) {string} name 菜品名称
     * @apiSuccess (返回参数说明) {int} count 菜品数量
     * @apiSuccess (返回参数说明) {int} price 菜品价格
     * @apiSuccess (返回参数说明) {obj} sub 子订单信息
     * @apiSuccess (返回参数说明) {int} money 标准金额
     * @apiSuccess (返回参数说明) {int} sub_money 标准金额
     * @apiSuccess (返回参数说明) {int} sort_code 第几份
     */
    public function getOrderMoney()
    {
        $params = Request::param();
        $money = (new  OrderServiceV2())->getOrderMoney($params);
        return json(new SuccessMessageWithData(['data' => $money]));
    }


    /**
     * @api {POST} /api/v2/order/pre/count/change  微信端-个人选菜-修改预订单份数
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription   微信端-个人选菜-修改预订单份数
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 222，
     *       "count": 2
     * }
     * @apiParam (请求参数说明) {string} id  订单ID
     * @apiParam (请求参数说明) {int} count 修改数量
     * @apiSuccessExample {json} 余额不足返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"type":"success","money":14}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} type  修改是否成功：success：成功 此时返回money为此订单修改后总冻结金额；no_balance：余额不足
     * @apiSuccess (返回参数说明) {int} money 冻结金额
     * @apiSuccess (返回参数说明) {int} money_type  余额类型 :冻结金额类型：overdraw：透支金额；user_balance:余额信息
     * @apiSuccess (返回参数说明) {int} money 当前余额
     */
    public function updatePrepareOrderCount()
    {
        $id = Request::param('id');
        $count = Request::param('count');
        $data = (new OrderServiceV2())->updatePrepareOrderCount($id, $count);
        return json(new SuccessMessageWithData(['data' => $data]));
    }


    /**
     * @api {POST} /api/v2/order/money/check 微信端-个人选菜-检查订单金额信息
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-个人选菜-检查订单金额信息
     * @apiExample {post}  请求样例:
     *    {
     *       "ordering_date": 2021-03-09,
     *       "dinner_id": 1,
     *       "order_money": 10
     * }
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiParam (请求参数说明) {int} order_money 菜品金额
     * @apiSuccessExample {json} 余额不足返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"check":1,"fixedMoney":"7"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} check 余额是否充足；1：充足；2：不足
     * @apiSuccess (返回参数说明) {int} fixed_type :冻结金额类型：overdraw：透支金额；user_balance:余额信息
     * @apiSuccess (返回参数说明) {int} fixed_money 冻结金额
     */
    public function checkOrderMoney()
    {
        $params = Request::param();
        $data = (new OrderServiceV2())->checkOrderMoney($params);
        return json(new SuccessMessageWithData(['data' => $data]));
    }


    /**
     * @api {POST} /api/v2/order/pre/submit 微信端-个人选菜-提交订单
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-个人选菜-检查订单金额信息
     * @apiExample {post}  请求样例:
     *    {
     *       "prepare_id":"C311714394839167",
     *       "address_id": 1,
     *       "delivery_fee": 5,
     *       "remark": "备注"
     * }
     * @apiParam (请求参数说明) {string} prepare_id  预订单ID
     * @apiParam (请求参数说明) {int} address_id  地址id
     * @apiParam (请求参数说明) {int} delivery_fee 配送费（单次，不是累加）
     * @apiParam (请求参数说明) {int} remark 备注
     * @apiSuccessExample {json} 余额不足返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"type":"success","prepare_id":C311714394839167}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} type  提交是否成功：success：成功 此时返回prepare_id 外来人员支付； "balance":余额不足
     * @apiSuccess (返回参数说明) {int} money 冻结金额
     * @apiSuccess (返回参数说明) {int} money_type  余额类型 :冻结金额类型：overdraw：透支金额；user_balance:余额信息
     */
    public function submitOrder($address_id=0,$delivery_fee=0)
    {
        $prepareId = Request::param('prepare_id');
        $remark= Request::param('remark');
        $data = (new OrderServiceV2())->submitOrder($prepareId, $address_id, $delivery_fee,$remark);
        return json(new SuccessMessageWithData(['data' => $data]));
    }


}