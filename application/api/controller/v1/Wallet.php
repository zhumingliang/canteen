<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AdminService;
use app\api\service\WalletService;
use app\lib\exception\ParameterException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Wallet extends BaseController
{
    /**
     * @api {POST} /api/v1/wallet/recharge/cash CMS管理端--充值管理--现金充值
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端--充值管理--现金充值
     * @apiExample {post}  请求样例:
     *    {
     *       "money": 200,
     *       "remark": '备注',
     *       "detail":[{"phone":"18956225230","card_num":"123"},{"phone":"18956225230","card_num":"123"}]
     *     }
     * @apiParam (请求参数说明) {int} money 充值金额
     * @apiParam (请求参数说明) {int} remark 备注
     * @apiParam (请求参数说明) {obj} detail  充值人员信息json字符串
     * @apiParam (请求参数说明) {string} phone  充值用户手机号
     * @apiParam (请求参数说明) {string} card_num  充值用户卡号
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function rechargeCash()
    {
        $params = Request::param();
        (new WalletService())->rechargeCash($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST}  /api/v1/wallet/recharge/upload CMS管理端--充值管理--批量充值
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  用file控件上传excel ，文件名称为：cash
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     */
    public function rechargeCashUpload()
    {
        $cash_excel = request()->file('cash');
        if (is_null($cash_excel)) {
            throw  new ParameterException(['msg' => '缺少excel文件']);
        }
        (new WalletService())->rechargeCashUpload($cash_excel);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/wallet/recharge/admins CMS管理端-充值管理-充值记录明细-充值人员列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-充值记录明细-充值人员列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/wallet/recharge/admins?&module_id=1
     * @apiParam (请求参数说明) {int} module_id 模块id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":7,"role":"饭堂管理员2"}]}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 角色id
     * @apiSuccess (返回参数说明) {string} role  角色名称
     */
    public function rechargeAdmins()
    {
        $module_id = Request::param('module_id');
        $admins = (new AdminService())->rechargeAdmins($module_id);
        return json(new SuccessMessageWithData(['data' => $admins]));


    }

    /**
     * @api {GET} /api/v1/recharges CMS管理端-充值管理-充值记录列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-企业明细-企业列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/recharges?time_begin=2019-09-01&time_end=2019-11-01&admin_id=0&username&type=all&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} time_begin 查询开始时间
     * @apiParam (请求参数说明) {string} time_end 查询截止时间
     * @apiParam (请求参数说明) {String} username 被充值用户
     * @apiParam (请求参数说明) {int} admin_id 充值人员id，全部传入0
     * @apiParam (请求参数说明) {String} type 充值途径:目前有：cash：现金；weixin:微信；nonghang:农行；all：全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":20,"current_page":1,"last_page":1,"data":[{"create_time":"2019-10-31 18:32:47","username":null,"money":"200.00","type":"cash","admin":"系统超级管理员","remark":""},{"create_time":"2019-10-31 18:32:48","username":null,"money":"200.00","type":"cash","admin":"系统超级管理员","remark":""}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {String} username  姓名
     * @apiSuccess (返回参数说明) {int} money 充值金额
     * @apiSuccess (返回参数说明) {string} admin 充值人员
     * @apiSuccess (返回参数说明) {string} remark 备注
     */
    public function rechargeRecords($page = 1, $size = 20, $type = 'all', $admin_id = 0, $username = '')
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $records = (new WalletService())->rechargeRecords($time_begin, $time_end,
            $page, $size, $type, $admin_id, $username);
        return json(new SuccessMessageWithData(['data' => $records]));

    }

    /**
     * @api {GET} /api/v1/wallet/users/balance CMS管理端-充值管理-饭卡余额查询
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-饭卡余额查询
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/wallet/users/balance?&department_id=0&user&phone=&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} user 人员信息
     * @apiParam (请求参数说明) {String} phone 手机号
     * @apiParam (请求参数说明) {int} department_id 部门id，全部传0
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":20,"current_page":1,"last_page":1,"data":[{"username":"LANGBIN","code":"1996010101","card_num":"1101147822","phone":"15521323081","department":"B1部门","balance":-227},{"username":null,"code":null,"card_num":"123","phone":"18956225230","department":null,"balance":400}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {String} username  姓名
     * @apiSuccess (返回参数说明) {int} code 编码
     * @apiSuccess (返回参数说明) {string} card_num 卡号
     * @apiSuccess (返回参数说明) {string} phone 手机号
     * @apiSuccess (返回参数说明) {string} department 部门
     * @apiSuccess (返回参数说明) {int} balance 余额
     */
    public function usersBalance($page = 1, $size = 20, $department_id = 0, $user = '', $phone = '')
    {
        $users = (new WalletService())->usersBalance($page, $size, $department_id, $user, $phone);
        return json(new SuccessMessageWithData(['data' => $users]));

    }

    /**
     * @api {POST} /api/v1/wallet/clearBalance CMS管理端--充值管理--饭卡余额查询-一键清零
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端--充值管理--饭卡余额查询-一键清零（企业管理员才有权限）
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function clearBalance()
    {
        (new WalletService())->clearBalance();
        return json(new SuccessMessage());

    }

    public function rechargeSupplement()
    {
        $params = Request::param();
        (new WalletService())->rechargeSupplement($params);
        return json(new SuccessMessage());
    }
}