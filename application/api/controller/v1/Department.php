<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\CompanyDepartmentT;
use app\api\service\DepartmentService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class Department extends BaseController
{
    /**
     * @api {POST} /api/v1/department/save CMS管理端-设置-新增企业部门
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-设置-新增部门
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 2,
     *       "name": "董事会",
     *       "parent_id": 0
     *     }
     * @apiParam (请求参数说明) {string} c_id  企业id
     * @apiParam (请求参数说明) {string} name  部门名称
     * @apiParam (请求参数说明) {int} parent_id  上级部门id;0表示无上级部门，本次新增为顶级部门
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 新增部门id
     */
    public function save()
    {
        $params = Request::param();
        $id = (new DepartmentService())->save($params);
        return json(new SuccessMessageWithData(['data' => ['id' => $id]]));
    }

    /**
     * @api {POST} /api/v1/department/update CMS管理端-设置-更新企业部门
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-设置-新增部门
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": "董事会-修改"
     *     }
     * @apiParam (请求参数说明) {string} id  部门id
     * @apiParam (请求参数说明) {string} name  部门名称
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::only('id,name');
        $id = CompanyDepartmentT::update($params);
        if (!$id) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/department/delete CMS管理端-设置-删除企业部门
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-设置-删除企业部门
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {string} id  部门id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function delete()
    {
        $id = Request::param('id');
        (new DepartmentService())->deleteDepartment($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/departments CMS管理端-获取企业部门列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-获取企业部门列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/departments?c_id=2
     * @apiParam (请求参数说明) {int} c_id 企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":3,"parent_id":0,"name":"董事会-修改","count":"0","items":[{"id":5,"parent_id":3,"name":"B部门","count":"0","items":[{"id":8,"parent_id":5,"name":"B1部门","count":"0"},{"id":9,"parent_id":5,"name":"B2部门","count":"0"}]},{"id":4,"parent_id":3,"name":"A部门","count":"0","items":[{"id":7,"parent_id":4,"name":"A2部门","count":"0"},{"id":6,"parent_id":4,"name":"A1部门","count":"0"}]}]}]}
     * @apiSuccess (返回参数说明) {int} id 部门id
     * @apiSuccess (返回参数说明) {String} parent_id 部门上级id
     * @apiSuccess (返回参数说明) {String} name  部门名称
     * @apiSuccess (返回参数说明) {int} count 部门员工数量
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     */
    public function departments()
    {
        $c_id = Request::param('c_id');
        $departments = (new DepartmentService())->departments($c_id);
        return json(new SuccessMessageWithData(['data' => $departments]));
    }

    public function addStaff()
    {
        $params = Request::param();
        (new DepartmentService())->addStaff($params);
        return json(new SuccessMessage());
    }

}