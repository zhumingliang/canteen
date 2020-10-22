<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AccountService;
use app\lib\exception\SuccessMessage;
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

    public function account()
    {

    }
}