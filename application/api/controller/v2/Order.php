<?php


namespace app\api\controller\v2;


use app\api\service\OrderStatisticService;
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


}