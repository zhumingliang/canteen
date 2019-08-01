<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AdminService;
use app\lib\enum\AdminEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

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

    /**
     *  * @api {GET} /api/v1/roles CMS管理端-设置-角色列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-设置-角色列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/roles?&page=1&size=10&state=3&c_name="A企业"&key=""
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} state 状态：1|正常；2|停用；3|获取全部
     * @apiParam (请求参数说明) {string} c_name 企业名称
     * @apiParam (请求参数说明) {string} key 关键词查询
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":4,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"company":null,"role":"系统超级管理员","account":"zml","remark":null,"state":1,"create_time":"2019-07-26 08:34:16","canteen":[],"shop":[]},{"id":2,"company":null,"role":"企业系统管理员","account":"2-admin","remark":null,"state":1,"create_time":"2019-07-29 01:28:17","canteen":[],"shop":[]},{"id":5,"company":null,"role":"饭堂管理员1","account":"zml111","remark":"测试","state":1,"create_time":"2019-07-31 01:54:24","canteen":[],"shop":[]},{"id":7,"company":null,"role":"饭堂管理员2","account":"admin12345","remark":"新增饭堂管理员","state":0,"create_time":"2019-08-01 00:34:17","canteen":[{"id":1,"admin_id":7,"name":"饭堂1"},{"id":2,"admin_id":7,"name":"饭堂2"},{"id":22,"admin_id":7,"name":"饭堂"},{"id":23,"admin_id":7,"name":"饭堂"}],"shop":[{"id":1,"admin_id":7,"name":"小卖部1"}]}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 用户id
     * @apiSuccess (返回参数说明) {string} company 归属企业
     * @apiSuccess (返回参数说明) {string} role  名称角色
     * @apiSuccess (返回参数说明) {string} account 账号
     * @apiSuccess (返回参数说明) {string} remark 备注
     * @apiSuccess (返回参数说明) {int} state 状态：1|正常；2|停用
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {obj} canteen 用户管理饭堂信息
     * @apiSuccess (返回参数说明) {int} canteen|id 用户饭堂关联id：取消时需要传入该字段
     * @apiSuccess (返回参数说明) {int} canteen|name 饭堂名称
     * @apiSuccess (返回参数说明) {obj} shop 用户管理小卖部信息
     * @apiSuccess (返回参数说明) {int} shop|id 用户小卖部关联id：取消时需要传入该字段
     * @apiSuccess (返回参数说明) {int} canteen|name 小卖部名称
     */
    public function roles($page = 1, $size = 10, $state = 3, $key = '', $c_name = "全部")
    {
        $roles = (new AdminService())->roles($page, $size, $state, $key, $c_name);
        return json(new SuccessMessageWithData(['data' => $roles]));

    }

    /**
     * @api {POST} /api/v1/role/handel CMS管理端-角色状态修改
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-角色状态修改
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "state":1
     *     }
     * @apiParam (请求参数说明) {int} id  角色ID
     * @apiParam (请求参数说明) {int} state  状态：1|启用；2|停用
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function handel()
    {
        $params = Request::only(['id', 'state']);
        (new AdminService())->handel($params);
        return json(new SuccessMessage());

    }
}