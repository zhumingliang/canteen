<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\ExcelService;
use app\api\service\PunishmentService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Punishment extends BaseController
{
    /**
     * @api {GET} /api/v1/punishment/strategyDetail CMS管理端-惩罚机制-惩罚策略-获取测法策略列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  惩罚机制PC端-惩罚策略-获取测法策略列表
     * http://canteen.tonglingok.com/api/v1/punishment/strategyDetail?page=1&size=10&&company_id=87&canteen_id=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} company_id 企业id
     * @apiParam (请求参数说明) {String} canteen_id 饭堂id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"company_id":78,"canteen_id":1,"staff_type_id":1,"detail":[{"id":7,"strategy_id":1,"type":"no_meal 订餐未就餐","count":2,"state":1},{"id":8,"strategy_id":1,"type":"no_booking 未订餐就餐","count":2,"state":1}]}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 惩罚策略id
     * @apiSuccess (返回参数说明) {int} company_id 企业id
     * @apiSuccess (返回参数说明) {int} canteen_id 饭堂id
     * @apiSuccess (返回参数说明) {int} staff_type_id 人员类型id
     * @apiSuccess (返回参数说明) {string} detail  惩罚策略明细json字符串
     * @apiSuccess (返回参数说明) {int} id 惩罚策略明细表id
     * @apiSuccess (返回参数说明) {int} strategy_id 测法策略id
     * @apiSuccess (返回参数说明) {string} type 违规类型：no_meal 订餐未就餐；no_booking  未订餐就餐
     * @apiSuccess (返回参数说明) {int}  state 状态：1 正常；2 删除
     * @apiSuccess (返回参数说明) {int}  count 最大违规数量
     */
    public function strategyDetail($page = 1, $size = 10)
    {
        $params = Request::param();
        $company_id = $params['company_id'];
        $canteen_id = $params['canteen_id'];
        $menus = (new PunishmentService())->strategyDetails($page, $size, $company_id, $canteen_id);
        return json(new SuccessMessageWithData(['data' => $menus]));
    }

    /**
     * @api {POST} /api/v1/punishment/strategyDetail CMS管理端-惩罚机制-惩罚策略-修改惩罚策略
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    惩罚机制PC端-惩罚策略-修改惩罚策略
     * @apiExample {post}  请求样例:
     * [{
     * "id":7,
     * "strategy_id":"1",
     * "type":"no_meal",
     * "count":"2",
     * "state":"1"
     * }
     * @apiParam (请求参数说明) {int} id 惩罚策略明细表id
     * @apiParam (请求参数说明) {int} strategy_id  惩罚策略id
     * @apiParam (请求参数说明) {string} type 违规类型：no_meal 订餐未就餐；no_booking  未订餐就餐
     * @apiParam (请求参数说明) {int}  state 状态：1 正常；2 删除
     * @apiParam (请求参数说明) {int}  count 最大违规数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateStrategy()
    {
        $params = Request::param();
        (new PunishmentService())->updateStrategy($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/punishment/getPunishmentStaffInfo CMS管理端-惩罚机制-惩罚管理-员工状态列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-惩罚机制-惩罚管理-员工状态列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/punishment/getPunishmentStaffInfo?page=1&size=10&key=测试&company_id=87&status=0
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} key 手机号或者姓名
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {string} company_name 企业关键字
     * @apiParam (请求参数说明) {int} status 0传全部，状态 :1:正常;3:白名单;4:黑名单
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"10","current_page":1,"last_page":1,"data":[{"company_id":87,"company_name":"小橙子","canteen_ids":"164","canteen_name":"饭堂","t_id":25,"staff_type":"测试","staff_id":6068,"username":"安全测试","phone":"18813960130","status":1,"no_meal":null,"no_booking":null}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} company_id 企业id
     * @apiSuccess (返回参数说明) {string} company_name 企业名称
     * @apiSuccess (返回参数说明) {string} canteen_ids 饭堂id
     * @apiSuccess (返回参数说明) {string} canteen_name 饭堂名称
     * @apiSuccess (返回参数说明) {int} t_id 员工类型id
     * @apiSuccess (返回参数说明) {string} staff_type 员工类型
     * @apiSuccess (返回参数说明) {int} staff_id 员工id
     * @apiSuccess (返回参数说明) {string}  username 员工姓名
     * @apiSuccess (返回参数说明) {string}  phone 手机号码
     * @apiSuccess (返回参数说明) {int}  status 状态 :1|正常（未违规）;2|违规;3|白名单;4|黑名单
     * @apiSuccess (返回参数说明) {int}  no_meal 订餐未就餐违规数量
     * @apiSuccess (返回参数说明) {int}  no_booking 未订餐就餐违规数量
     */
    public function getPunishmentStaffInfo($page = 1, $size = 10, $key = '')
    {
        $company_id = Request::param('company_id');
        $company_name = Request::param('company_name');
        $status = Request::param('status');
        $records = (new PunishmentService())->getPunishmentStaffStatus($page, $size, $key, $company_id,
            $company_name, $status);
        return json(new SuccessMessageWithData(['data' => $records]));
    }

    /**
     * @api {GET} /api/v1/punishment/exportPunishmentStaffInfo CMS管理端-惩罚机制-惩罚管理-员工状态列表-导出报表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-惩罚机制-惩罚管理-员工状态列表-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/punishment/exportPunishmentStaffInfo?page=1&size=10&key=测试&company_id=87&status=0
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} key 手机号或者姓名
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {string} company_name 企业关键字
     * @apiParam (请求参数说明) {int} status 0传全部，状态 :1|正常;3|白名单;4|黑名单
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/惩罚管理_20210426005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportPunishmentStaffInfo($key = '')
    {
        $company_id = Request::param('company_id');
        $company_name = Request::param('company_name');
        $status = Request::param('status');
        (new \app\api\service\v2\DownExcelService())->exportPunishmentStaffInfo($key, $company_id, $company_name, $status);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/punishment/getPunishmentEditDetails CMS管理端-惩罚机制-惩罚编辑详情列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-惩罚机制-惩罚编辑详情列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/punishment/getPunishmentEditDetails?page=1&size=10&key=测试&company_id=87&canteen_id=0&time_begin=2021-04-01&time_end=2021-04-26
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} key 手机号或者姓名
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {string} company_name 企业关键字
     * @apiParam (请求参数说明) {int} canteen_id 0传全部，饭堂id
     * @apiParam (请求参数说明) {string} time_begin 查询开始时间
     * @apiParam (请求参数说明) {string} time_end 查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"10","current_page":1,"last_page":1,"data":[{"date":"2021-04-25","company_id":78,"company_name":"宜通世纪","canteen_ids":"146","canteen_name":"11楼饭堂","t_id":29,"staff_type":"管理员","staff_id":1877,"username":"覃美东","phone":"13686948977","old_state":"{\"status\": 1,\"no_meal\": 1,\"no_booking\": 1}","new_state":"{\"status\": 3,\"no_meal\": 3,\"no_booking\": 4}"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} date 日期
     * @apiSuccess (返回参数说明) {int} company_id 企业id
     * @apiSuccess (返回参数说明) {string} company_name 企业名称
     * @apiSuccess (返回参数说明) {string} canteen_ids 饭堂id
     * @apiSuccess (返回参数说明) {string} canteen_name 饭堂名称
     * @apiSuccess (返回参数说明) {int} t_id 员工类型id
     * @apiSuccess (返回参数说明) {string} staff_type 员工类型
     * @apiSuccess (返回参数说明) {int} staff_id 员工id
     * @apiSuccess (返回参数说明) {string}  username 员工姓名
     * @apiSuccess (返回参数说明) {string}  phone 手机号码
     * @apiSuccess (返回参数说明) {string}  old_state 旧状态（json格式）
     * @apiSuccess (返回参数说明) {string}  new_state 新状态（json格式）
     * @apiSuccess (返回参数说明) {int}  status 状态 :1|正常（未违规）;2|违规;3|白名单;4|黑名单
     * @apiSuccess (返回参数说明) {int}  no_meal 订餐未就餐违规数量
     * @apiSuccess (返回参数说明) {int}  no_booking 未订餐就餐违规数量
     */
    public function getPunishmentEditDetails($page = 1, $size = 10, $key = '')
    {
        $company_id = Request::param('company_id');
        $company_name = Request::param('company_name');
        $canteen_id = Request::param('canteen_id');
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $records = (new PunishmentService())->getPunishmentEditDetails($page, $size, $key, $company_id,
            $company_name, $canteen_id, $time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $records]));
    }

    /**
     * @api {GET} /api/v1/punishment/exportPunishmentEditDetails CMS管理端-惩罚机制-惩罚编辑详情列表-导出报表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-惩罚机制-惩罚编辑详情列表-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/punishment/exportPunishmentEditDetails?page=1&size=10&key=测试&company_id=87&canteen_id=0&time_begin=2021-04-01&time_end=2021-04-26
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} key 手机号或者姓名
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {string} company_name 企业关键字
     * @apiParam (请求参数说明) {int} canteen_id 0传全部，饭堂id
     * @apiParam (请求参数说明) {string} time_begin 查询开始时间
     * @apiParam (请求参数说明) {string} time_end 查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/惩罚编辑详情_20210426005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportPunishmentEditDetails($key = '')
    {
        $company_id = Request::param('company_id');
        $company_name = Request::param('company_name');
        $canteen_id = Request::param('canteen_id');
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        (new \app\api\service\v2\DownExcelService())->exportPunishmentEditDetails($key, $company_id, $company_name,
            $canteen_id, $time_begin, $time_end);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/punishment/updatePunishmentStatus CMS管理端-惩罚机制-惩罚管理-编程状态
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-惩罚机制-惩罚管理-编程状态
     * @apiExample {post}  请求样例:
     * [{
     * "admin_id":7,
     * "staff_id":1787,
     * "old_state":"{"status": 1,"no_meal": 1,"no_booking": 1}",
     * "new_state":"{"status": 2,"no_meal": 2,"no_booking": 3}",
     * }
     * @apiParam (请求参数说明) {int} admin_id 操作员id
     * @apiParam (请求参数说明) {int} staff_id 员工id
     * @apiParam (请求参数说明) {string} old_state 旧状态
     * @apiParam (请求参数说明) {string} new_state 新状态
     * @apiSuccess (返回参数说明) {int}  status 状态 :1|正常（未违规）;2|违规;3|白名单;4|黑名单
     * @apiSuccess (返回参数说明) {int}  no_meal 订餐未就餐违规数量
     * @apiSuccess (返回参数说明) {int}  no_booking 未订餐就餐违规数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updatePunishmentStatus()
    {
        $params = Request::param();
        (new PunishmentService())->updatePunishmentStatus($params);
        return json(new SuccessMessage());
    }

}