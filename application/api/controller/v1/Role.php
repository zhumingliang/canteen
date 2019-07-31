<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AdminService;
use app\lib\enum\AdminEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;

class Role extends BaseController
{
    /**
     * @api {POST} /api/v1/role/save CMS管理端-新增角色信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-新增角色信息
     * @apiExample {post}  请求样例:
     *    {
     *       "account": "admin123",
     *       "passwd": "a111111",
     *       "role": "饭堂管理员",
     *       "canteens":[{"c_id":1,"name":"饭堂1"},{"c_id":2,"name":"饭堂2"}],
     *       "shops":[{"s_id":1,"name":"小卖部1"},{"s_id":2,"name":"小卖部2"}],
     *       "company":"企业A,企业A",
     *       "remark": "新增饭堂管理员",
     *       "rules": "1,2,3,4"
     *     }
     * @apiParam (请求参数说明) {string} account  账号
     * @apiParam (请求参数说明) {string} passwd  密码
     * @apiParam (请求参数说明) {string} role  角色名称
     * @apiParam (请求参数说明) {string} remark  角色说明
     * @apiParam (请求参数说明) {string} canteens 管理饭堂列表
     * @apiParam (请求参数说明) {string} shops  管理小卖部列表
     * @apiParam (请求参数说明) {string} company 角色所属企业名称，多个用逗号分隔
     * @apiParam (请求参数说明) {string} rules  可见模块，用逗号分隔。注意，一级模块也需要上传
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function save()
    {
        $params = $this->request->param();
        $params['grade'] = AdminEnum::COMPANY_OTHER;
        (new AdminService())->saveRole($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/role/update CMS管理端-修改角色信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-修改角色信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id":2,
     *       "account": "admin123",
     *       "passwd": "a111111",
     *       "role": "饭堂管理员",
     *       "canteens":{"add":[{"c_id":1,"name":"饭堂"}],"cancel":"1,2"},
     *       "shops":{"add":[{"s_id":1,"name":"小卖部1"}],cancel:"3"},
     *       "remark": "新增饭堂管理员",
     *       "rules": "1,2,3,4"
     *     }
     * @apiParam (请求参数说明) {int} id  角色id
     * @apiParam (请求参数说明) {string} account  账号
     * @apiParam (请求参数说明) {string} passwd  密码
     * @apiParam (请求参数说明) {string} role  角色名称
     * @apiParam (请求参数说明) {string} remark  角色说明
     * @apiParam (请求参数说明) {string} canteens 饭堂信息
     * @apiParam (请求参数说明) {string} canteens|add 新增用户管理饭堂
     * @apiParam (请求参数说明) {string} canteens|add|c_id 饭堂id
     * @apiParam (请求参数说明) {string} canteens|add|name 饭堂名称
     * @apiParam (请求参数说明) {string} canteens|cancel  取消用户管理饭堂信息，取消多个饭堂，则将用户和饭堂关系id用逗号分隔
     * @apiParam (请求参数说明) {string} shops  管理小卖部列表
     * @apiParam (请求参数说明) {string} shop|add 新增用户管理饭堂
     * @apiParam (请求参数说明) {string} shop|add|s_id 小卖部id
     * @apiParam (请求参数说明) {string} shop|add|name 小卖部名称
     * @apiParam (请求参数说明) {string} shop|cancel  取消用户管理小卖部信息，取消多个小卖部，则将用户和小卖部关系id用逗号分隔
     * @apiParam (请求参数说明) {string} rules  可见模块，用逗号分隔。注意，一级模块也需要上传
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function update()
    {
        $params = $this->request->param();
        (new AdminService())->updateRole($params);
        return json(new SuccessMessage());
    }

    public function roles($page = 1, $size = 10, $state = 3, $key = '', $c_name = "全部")
    {
        $roles = (new AdminService())->roles($page, $size, $state, $key, $c_name);
        return json(new SuccessMessageWithData(['data' =>$roles]));

    }

}