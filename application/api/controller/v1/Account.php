<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AccountService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Account extends BaseController
{
    /**
     * @api {POST} /api/v1/account/save  PC端-新增企业账户
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     PC端-新增企业账户
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 1,
     *       "type": 2,
     *       "department_all": 2,
     *       "departments": [1,2],
     *       "name": "补贴账户",
     *       "clear": 2,
     *       "clear_type": "day",
     *       "first": 1,
     *       "end": 2,
     *       "day_count": 10,
     *       "time_begin": "2020-10-22",
     *       "sort": 1,
     *       "account_sort": [{"account_id":1,"sort":1},{"account_id":2,"sort":1}]
     *     }
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} type  账户类别：1:基本账户；2:附加账户
     * @apiParam (请求参数说明) {int} department_all  是否全部部门：1 ： 是；2：否
     * @apiParam (请求参数说明) {string} departments  选择的部门
     * @apiParam (请求参数说明) {string} name  账号名称
     * @apiParam (请求参数说明) {int} clear  清零类型：1 ：不清零；2：自然周期；3：天数
     * @apiParam (请求参数说明) {string} clear_type  清零类别：day/week/month/quarter/year：天数/周/月/季度/年
     * @apiParam (请求参数说明) {int} first  周/月/季度/年类型下：是否第一天 ，1 ： 是；2 ：否
     * @apiParam (请求参数说明) {int} end   周/月/季度/年类型下：是否最后一天 ，1 ： 是；2 ：否
     * @apiParam (请求参数说明) {int} day_count  天数类型下：天数
     * @apiParam (请求参数说明) {string} time_begin 天数类型下： 开始日期
     * @apiParam (请求参数说明) {int} sort  本账号排序
     * @apiParam (请求参数说明) {obj} account_sort  企业下所有账户消费排序
     * @apiParam (请求参数说明) {int} account_id  账号id
     * @apiParam (请求参数说明) {int} sort  账号排序
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new AccountService())->save($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/account  CMS管理端-获取账户信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取账户信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/account?id=1
     * @apiParam (请求参数说明) {int} id  账户id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":17,"company_id":95,"type":1,"department_all":1,"name":"个人账户","clear":2,"state":1,"clear_type":0,"first":2,"end":2,"day_count":0,"time_begin":"0000-00-00","sort":1,"next_time":"0000-00-00 00:00:00","fixed_type":1,"allSort":[{"id":17,"name":"个人账户","sort":1},{"id":18,"name":"农行账户","sort":2}],"company":{"id":95,"name":"汕尾人民医院"},"departments":[{"id":1,"account_id":17,"department_id":59,"department":{"id":59,"name":"部门1"}}]}}     * @apiSuccess (返回参数说明) {int} id 账户id
     * @apiSuccess (返回参数说明) {int} company_id 企业id
     * @apiSuccess (返回参数说明) {string} type 账户类别
     * @apiSuccess (返回参数说明) {int} department_all 是否全部部门：1 ： 是；2：否
     * @apiSuccess (返回参数说明) {obj} departments 部门信息：非全部部门下显示具体的部门
     * @apiSuccess (返回参数说明) {string} id 账户部门关联id
     * @apiSuccess (返回参数说明) {string} department/name 部门名称
     * @apiSuccess (返回参数说明) {int} clear 清零类型：1 ：不清零；2：自然周期；3：天数
     * @apiSuccess (返回参数说明) {int} sort  排序
     * @apiSuccess (返回参数说明) {int} fixed_type 基本户类别：1 ：个人账户 2 ：农行账户
     * @apiSuccess (返回参数说明) {string} next_time 下次清零时间
     * @apiSuccess (返回参数说明) {string} clear_type  清零类别：day/week/month/quarter/year：天数/周/月/季度/年
     * @apiSuccess (返回参数说明) {int} first  周/月/季度/年类型下：是否第一天 ，1 ： 是；2 ：否
     * @apiSuccess (返回参数说明) {int} end   周/月/季度/年类型下：是否最后一天 ，1 ： 是；2 ：否
     * @apiSuccess (返回参数说明) {int} day_count  天数类型下：天数
     * @apiSuccess (返回参数说明) {string} time_begin 天数类型下： 开始日期
     * @apiSuccess (返回参数说明) {int} sort  本账号排序
     * @apiSuccess (返回参数说明) {obj} allSort  企业下所有账户消费排序
     * @apiSuccess (返回参数说明) {int} id  账号id
     * @apiSuccess (返回参数说明) {string} name  账号名称
     * @apiSuccess (返回参数说明) {int} sort  账号排序
     * @apiSuccess (返回参数说明) {obj} company 企业信息
     * @apiSuccess (返回参数说明) {string} name 企业名称
     */
    public function account()
    {
        $id = Request::param('id');
        $account = (new AccountService())->account($id);
        return json(new SuccessMessageWithData(['data' => $account]));
    }

    /**
     * @api {GET} /api/v1/accounts  CMS管理端-获取账户列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取账户列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/accounts?company_id=1
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":17,"admin_id":95,"company_id":95,"type":1,"department_all":1,"name":"个人账户","clear":2,"sort":1,"fixed_type":1,"next_time":"0000-00-00 00:00:00","admin":{"id":95,"role":"系统管理","phone":"15002050878"},"company":{"id":95,"name":"汕尾人民医院"},"departments":[{"id":1,"account_id":17,"department_id":59,"department":{"id":59,"name":"部门1"}}]},{"id":18,"admin_id":0,"company_id":95,"type":1,"department_all":1,"name":"农行账户","clear":2,"sort":2,"fixed_type":2,"next_time":"0000-00-00 00:00:00","admin":null,"company":{"id":95,"name":"汕尾人民医院"},"departments":[]}]}
     * @apiSuccess (返回参数说明) {int} id 账户id
     * @apiSuccess (返回参数说明) {int} company_id 企业id
     * @apiSuccess (返回参数说明) {string} type 账户类别
     * @apiSuccess (返回参数说明) {int} department_all 是否全部部门：1 ： 是；2：否
     * @apiSuccess (返回参数说明) {obj} departments 部门信息：非全部部门下显示具体的部门
     * @apiSuccess (返回参数说明) {string} name 部门名称
     * @apiSuccess (返回参数说明) {int} clear 清零类型：1 ：不清零；2：自然周期；3：天数
     * @apiSuccess (返回参数说明) {int} sort  排序
     * @apiSuccess (返回参数说明) {int} fixed_type 基本户类别：1 ：个人账户 2 ：农行账户
     * @apiSuccess (返回参数说明) {string} next_time 下次清零时间
     * @apiSuccess (返回参数说明) {obj} company 企业信息
     * @apiSuccess (返回参数说明) {string} name 企业名称
     * @apiSuccess (返回参数说明) {obj} admin 创建人信息
     * @apiSuccess (返回参数说明) {string} name 企业名称
     * @apiSuccess (返回参数说明) {string} role 角色名称
     * @apiSuccess (返回参数说明) {string} phone 手机号
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     */
    public function accounts()
    {
        $companyId = Request::param('company_id');
        $accounts = (new AccountService())->accounts($companyId);
        return json(new SuccessMessageWithData(['data' => $accounts]));
    }

    /**
     * @api {POST} /api/v1/account/handle PC端-企业账户状态操作
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    PC端-企业账户状态操作
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "state": 1
     *     }
     * @apiParam (请求参数说明) {int} id  账户id
     * @apiParam (请求参数说明) {int} state 状态：1：启用；2：停用
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function handle()
    {
        $id = Request::param('id');
        $state = Request::param('state');
        (new AccountService())->handle($id, $state);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/account/update  PC端-更新企业账户
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     PC端-更新企业账户
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "type": 2,
     *       "department_all": 2,
     *       "departments": {"add":[1,2],"cancel":[1,2]},
     *       "name": "补贴账户",
     *       "clear": 2,
     *       "clear_type": "day",
     *       "first": 1,
     *       "end": 2,
     *       "day_count": 10,
     *       "time_begin": "2020-10-22",
     *       "sort": 1,
     *       "account_sort": [{"account_id":1,"sort":1},{"account_id":2,"sort":1}]
     *     }
     * @apiParam (请求参数说明) {int} id  账户id
     * @apiParam (请求参数说明) {int} department_all  是否全部部门：1 ： 是；2：否
     * @apiParam (请求参数说明) {obj} departments  部门信息：未修改不需要上传参数
     * @apiParam (请求参数说明) {obj} add  新增部门
     * @apiParam (请求参数说明) {obj} cancel  取消部门
     * @apiParam (请求参数说明) {string} name  账号名称
     * @apiParam (请求参数说明) {int} clear  清零类型：1 ：不清零；2：自然周期；3：天数
     * @apiParam (请求参数说明) {string} clear_type  清零类别：day/week/month/quarter/year：天数/周/月/季度/年
     * @apiParam (请求参数说明) {int} first  周/月/季度/年类型下：是否第一天 ，1 ： 是；2 ：否
     * @apiParam (请求参数说明) {int} end   周/月/季度/年类型下：是否最后一天 ，1 ： 是；2 ：否
     * @apiParam (请求参数说明) {int} day_count  天数类型下：天数
     * @apiParam (请求参数说明) {string} time_begin 天数类型下： 开始日期
     * @apiParam (请求参数说明) {int} sort  本账号排序
     * @apiParam (请求参数说明) {obj} account_sort  企业下所有账户消费排序
     * @apiParam (请求参数说明) {int} account_id  账号id
     * @apiParam (请求参数说明) {int} sort  账号排序
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::param();
        (new AccountService())->update($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/accounts/search  CMS管理端-获取账户信息-筛选条件
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取账户信息-筛选条件
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/accounts/search?company_id=95
     * @apiParam (请求参数说明) {int} company_id  指定企业：系统管理员获取时传入，企业内部无需传入
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":17,"sort":1,"fixed_type":1,"name":"个人账户"}]}
     * @apiSuccess (返回参数说明) {int} id  账号id
     * @apiSuccess (返回参数说明) {string} name  账号名称
     * @apiSuccess (返回参数说明) {string} fixed_type  基本户类别：1 ｜ 个人账户 2 ｜ 农行账户
     * @apiSuccess (返回参数说明) {string} sort  排序
     */
    public function accountsForSearch()
    {
        $company_id = Request::param('company_id');
        $account = (new AccountService())->accountsForSearch($company_id);
        return json(new SuccessMessageWithData(['data' => $account]));
    }

    /**
     * @api {GET} /api/v1/account/balance  微信端-我的账户
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription   微信端-我的账户
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/account/balance
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"balance":-35.6,"useBalance":0,"accounts":[{"id":73,"name":"个人账户","sort":1,"type":1,"fixed_type":1,"balance":-35.6}]}}
     * @apiSuccess (返回参数说明) {float} balance  余额
     * @apiSuccess (返回参数说明) {float} useBalance  可用余额
     * @apiSuccess (返回参数说明) {obj} accounts  账户信息
     * @apiSuccess (返回参数说明) {string} name  账户名称
     * @apiSuccess (返回参数说明) {float} balance  账户余额
     */
    public function accountBalance()
    {
        $balance = (new AccountService())->staffAccountBalance();
        return json(new SuccessMessageWithData(['data' => $balance]));
    }

    /**
     * @api {GET} /api/v1/account/balance/fixed 微信端-冻结金额
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-冻结金额（冻结订单列表）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/account/balance/fixed?$page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"records":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"order_id":34459,"location_id":240,"location":"A策略饭堂","order_type":"canteen","create_time":"2020-11-20 14:51:09","ordering_date":"2020-11-20","dinner":"午餐","money":"-11.00","phone":"15014335935","count":1,"sub_money":"11.00","delivery_fee":"0.00","booking":1,"used":2,"eating_type":1,"consumption_type":"one","company_id":120,"sort_code":null}]},"balance":11}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {obj} balance 冻结余额
     * @apiSuccess (返回参数说明) {obj} records  记录列表
     * @apiSuccess (返回参数说明) {int} order_id  订单id
     * @apiSuccess (返回参数说明) {string} location  消费地点
     * @apiSuccess (返回参数说明) {string} order_type  订单类别
     * @apiSuccess (返回参数说明) {string} used_type  类型
     * @apiSuccess (返回参数说明) {string} create_time 消费日期
     * @apiSuccess (返回参数说明) {string} ordering_date 餐次日期
     * @apiSuccess (返回参数说明) {string} consumption_type 扣费类型：one 一次性扣费；more 多次扣费
     * @apiSuccess (返回参数说明) {int} dinner 名称
     */
    public function fixedBalance($page = 1, $size = 10)
    {
        $info = (new AccountService())->fixedBalance($page, $size);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    public function transactionDetails($page = 1, $size = 10, $account_id = 0, $type = 1)
    {
        $consumptionDate = Request::param('consumption_date');
        $info = (new AccountService())->transactionDetails($page, $size, $account_id, $type,$consumptionDate);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    public function bill($page = 1, $size = 10)
    {
        $consumptionDate = Request::param('consumption_date');
        $info = (new AccountService())->bill($page, $size, $consumptionDate);
        return json(new SuccessMessageWithData(['data' => $info]));
    }
}