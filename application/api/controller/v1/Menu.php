<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\MenuService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Menu extends BaseController
{
    /**
     * @api {POST} /api/v1/menu/save CMS管理端-新增编辑饭堂菜单
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-编辑饭堂菜单
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 6
     *       "d_id": 6
     *      "detail": [{"id":4,"category":"荤菜","status":1,"count":3,"state":2},{"category":"汤","status":2,"count":0}]
     *     }
     * @apiParam (请求参数说明) {int} c_id 饭堂id
     * @apiParam (请求参数说明) {int} d_id  饭堂餐次id
     * @apiParam (请求参数说明) {string} detail  饭堂菜单明细json字符串
     * @apiParam (请求参数说明) {string} detail|id 菜品明细id,更新操作需要传入此字段
     * @apiParam (请求参数说明) {string} detail|category 菜品类型名称
     * @apiParam (请求参数说明) {string} detail|status 菜品状态：1|固定；2|动态
     * @apiParam (请求参数说明) {string} detail|count 数量
     * @apiParam (请求参数说明) {string} detail|state 明细状态：2|删除，没有删除操作无需传入该字段
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new MenuService())->save($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/menus/company CMS管理端-菜单设置-菜单设置列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-菜单设置-菜单设置列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/menus/company?page=1&size=10&canteen_id="1,2,3"
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} canteen_id 饭堂id，多个用逗号分隔
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":3,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":5,"name":"饭堂2","c_id":2,"company":{"id":2,"name":"一级企业","grade":1},"dinner":[]},{"id":6,"name":"饭堂1","c_id":3,"company":{"id":3,"name":"企业A","grade":1},"dinner":[{"id":5,"c_id":6,"name":"早餐","menus":[]},{"id":6,"c_id":6,"name":"中餐","menus":[{"id":1,"d_id":6,"category":"荤菜","status":1,"count":3},{"id":2,"d_id":6,"category":"汤","status":2,"count":0}]},{"id":7,"c_id":6,"name":"晚餐","menus":[]}]},{"id":7,"name":"饭堂2","c_id":3,"company":{"id":3,"name":"企业A","grade":1},"dinner":[]}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 饭堂id
     * @apiSuccess (返回参数说明) {string} c_id  饭堂归属企业id
     * @apiSuccess (返回参数说明) {obj} name  饭堂名称
     * @apiSuccess (返回参数说明) {string} company  企业信息
     * @apiSuccess (返回参数说明) {string} company|id  企业id
     * @apiSuccess (返回参数说明) {string} company|name  企业名称
     * @apiSuccess (返回参数说明) {int} company|grade 企业级别：1|一级，2|二级，等等
     * @apiSuccess (返回参数说明) {obj} dinner 餐次信息
     * @apiSuccess (返回参数说明) {int} dinner|id 餐次id
     * @apiSuccess (返回参数说明) {int} dinner|name 餐次名称
     * @apiSuccess (返回参数说明) {obj} dinner|menus 餐次菜单设置信息
     * @apiSuccess (返回参数说明) {int} dinner|menus|id 餐次菜单设置id
     * @apiSuccess (返回参数说明) {int} dinner|menus|d_id 餐次id
     * @apiSuccess (返回参数说明) {string} dinner|menus|category 菜单设置分类
     * @apiSuccess (返回参数说明) {int} dinner|menus|status 菜品状态:1|固定；2|动态
     * @apiSuccess (返回参数说明) {int} dinner|menus|count 数量
     */
    public function companyMenus($page = 1, $size = 10)
    {
        $params = Request::param();
        $canteen_id = $params['canteen_id'];
        $menus = (new MenuService())->companyMenus($page, $size, $canteen_id);
        return json(new SuccessMessageWithData(['data' => $menus]));
    }

    /**
     * @api {GET} /api/v1/menus/canteen CMS管理端-获取指定饭堂菜单信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-菜单设置-获取指定饭堂菜单信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/menus/canteen?canteen_id=6
     * @apiParam (请求参数说明) {String} canteen_id 饭堂id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":5,"name":"早餐","menus":[]},{"id":6,"name":"中餐","menus":[{"id":1,"d_id":6,"category":"荤菜","status":1,"count":3},{"id":2,"d_id":6,"category":"汤","status":2,"count":0}]},{"id":7,"name":"晚餐","menus":[]}]}
     * @apiSuccess (返回参数说明) {int} id 餐次id
     * @apiSuccess (返回参数说明) {string} name 餐次名称
     * @apiSuccess (返回参数说明) {obj} menus 餐次菜单设置信息
     * @apiSuccess (返回参数说明) {int} menus|id 餐次菜单设置id
     * @apiSuccess (返回参数说明) {int} menus|d_id 餐次id
     * @apiSuccess (返回参数说明) {string} menus|category 菜单设置分类
     * @apiSuccess (返回参数说明) {int}  menus|status 菜品状态:1|固定；2|动态
     * @apiSuccess (返回参数说明) {int}  menus|count 数量
     */
    public function canteenMenus()
    {
        $canteen_id = Request::param('canteen_id');
        $menus = (new MenuService())->canteenMenus($canteen_id);
        return json(new SuccessMessageWithData(['data' => $menus]));
    }

}