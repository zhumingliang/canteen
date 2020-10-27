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
     * http://canteen.tonglingok.com/account
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
     * http://canteen.tonglingok.com/accounts
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
        $accounts = (new AccountService())->accounts();
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
     * http://canteen.tonglingok.com/api/v1/account/search?company_id=95
     * @apiParam (请求参数说明) {int} company_id  指定企业：系统管理员获取时传入，企业内部无需传入
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":17,"fixed_type":1,"name":"个人账户"}]}     * @apiSuccess (返回参数说明) {int} id  账号id
     * @apiSuccess (返回参数说明) {string} name  账号名称
     */
    public function accountsForSearch()
    {
        $company_id = Request::param('company_id');
        $account = (new AccountService())->accountsForSearch($company_id);
        return json(new SuccessMessageWithData(['data' => $account]));
    }

}