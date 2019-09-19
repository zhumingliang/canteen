<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CategoryService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Category extends BaseController
{
    /**
     * @api {POST} /api/v1/category/save  CMS管理端-小卖部设置-新增商品类型
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-小卖部设置-新增商品类型
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": "1",
     *       "name": "生鲜"
     *     }
     * @apiParam (请求参数说明) {int} c_id  企业id
     * @apiParam (请求参数说明) {string} name  商品类型名称
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new CategoryService())->save($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/category/update  CMS管理端-小卖部设置-更新商品类型
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-小卖部设置-更新商品类型
     * @apiExample {post}  请求样例:
     *    {
     *       "id": "1",
     *       "name": "生鲜"
     *     }
     * @apiParam (请求参数说明) {int} id  供应商id
     * @apiParam (请求参数说明) {string} name  商品类型名称
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::param();
        (new CategoryService())->update($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/category/delete  CMS管理端-小卖部设置-删除商品类型
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-小卖部设置-删除商品类型
     * @apiExample {post}  请求样例:
     *    {
     *       "id": "1"
     *     }
     * @apiParam (请求参数说明) {int} id  商品类型id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function delete()
    {
        $id = Request::param('id');
        (new CategoryService())->delete($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/categories CMS管理端-小卖部设置-商品类型列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部设置-商品类型列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/categories?c_id=1&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} c_id 企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"name":"生鲜","c_id":2,"create_time":"2019-09-19 10:02:39","company":"一级企业"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 商品类型id
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {String} name  商品类型名称
     * @apiSuccess (返回参数说明) {String} company  企业名称
     */
    public function categories($page = 1, $size = 10)
    {
        $c_id = Request::param('c_id');
        $suppliers = (new CategoryService())->categories($page, $size, $c_id);
        return json(new SuccessMessageWithData(['data' => $suppliers]));
    }


}