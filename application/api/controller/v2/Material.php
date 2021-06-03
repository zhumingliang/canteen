<?php


namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\service\MachineService;
use app\api\service\MaterialService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Material extends BaseController
{

    /**
     * @api {POST} /api/v2/food/material/save CMS管理端-材料管理-菜品材料明细-新增菜品材料
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料管理-菜品材料明细-新增菜品材料
     * @apiExample {post}  请求样例:
     *    {
     *       "f_id": 1,
     *       "name": 1,
     *       "count": 6
     * }
     * @apiParam (请求参数说明) {int} f_id  菜品ID
     * @apiParam (请求参数说明) {string} name 菜品材料名称
     * @apiParam (请求参数说明) {float} count 菜品材料数量:单位kg
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveFoodMaterial()
    {
        $params = Request::param();
        (new MaterialService())->saveFoodMaterial($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v2/food/material/update CMS管理端-材料管理-菜品材料明细-修改菜品材料
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料管理-菜品材料明细-修改菜品材料
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": 1,
     *       "count": 6,
     *       "state": 2
     * }
     * @apiParam (请求参数说明) {int} id  菜品材料ID
     * @apiParam (请求参数说明) {string} name 菜品材料名称
     * @apiParam (请求参数说明) {float} count 菜品材料数量:单位kg
     * @apiParam (请求参数说明) {int} state  状态：删除时：传入 2
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateFoodMaterial()
    {
        $params = Request::param();
        (new MaterialService())->updateFoodMaterial($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/material/order/save CMS管理端-材料下单报表-新增材料信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料下单报表-新增材料信息
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 6,
     *       "canteen_id": 6,
     *       "material": "牛肉",
     *       "price": 16.01
     *       "count": 20.001
     *     }
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {int} canteen_id 饭堂id
     * @apiParam (请求参数说明) {string} material 材料名称
     * @apiParam (请求参数说明) {float} price  单价，单位：元
     * @apiParam (请求参数说明) {string} count  数量，单位：kg
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveOrderMaterial()
    {
        $params = Request::param();
        (new MaterialService())->saveOrderMaterial($params);
        return json(new SuccessMessage());
    }

    public function updateOrderMaterial()
    {
        $params = Request::param();
        (new MaterialService())->updateOrderMaterial($params);
        return json(new SuccessMessage());
    }
}