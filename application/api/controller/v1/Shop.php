<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\ShopProductT;
use app\api\service\ShopService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class Shop extends BaseController
{
    /**
     * @api {POST} /api/v1/shop/product/save  CMS管理端-小卖部管理-商品管理-新增商品
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-小卖部管理-商品管理-新增商品
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 3,
     *       "supplier_id": 1,
     *       "category_id": 2,
     *       "name": "鸡蛋",
     *       "price": 8,
     *       "unit": "元/500g",
     *       "count": 100,
     *       "image": "/static/image/a.png",
     *     }
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} supplier_id  供应商id
     * @apiParam (请求参数说明) {int} category_id  类型id
     * @apiParam (请求参数说明) {string} name  商品名称
     * @apiParam (请求参数说明) {float} price  价格
     * @apiParam (请求参数说明) {string} unit  单位
     * @apiParam (请求参数说明) {int} count  商品库存数量
     * @apiParam (请求参数说明) {string} image  商品图片：由上传图片接口返回
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveProduct()
    {
        $params = Request::param();
        (new ShopService())->saveProduct($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/shop/product/update  CMS管理端-小卖部管理-商品管理-修改商品
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-小卖部管理-商品管理-修改商品
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 5,
     *       "company_id": 3,
     *       "supplier_id": 1,
     *       "category_id": 2,
     *       "name": "鸡蛋",
     *       "price": 8,
     *       "unit": "元/500g",
     *       "image": "/static/image/a.png",
     *     }
     * @apiParam (请求参数说明) {int} id  商品id
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} supplier_id  供应商id
     * @apiParam (请求参数说明) {int} category_id  类型id
     * @apiParam (请求参数说明) {string} name  商品名称
     * @apiParam (请求参数说明) {float} price  价格
     * @apiParam (请求参数说明) {string} unit  单位
     * @apiParam (请求参数说明) {string} image  商品图片：由上传图片接口返回
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateProduct()
    {
        $params = Request::param();
        (new ShopService())->updateProduct($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/shop/product  CMS管理端-小卖部管理-商品管理-获取商品信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-小卖部管理-商品管理-获取商品信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/product?id=8
     * @apiParam (请求参数说明) {int} id  商品id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":5,"name":"鸡蛋","price":"100.0","supplier_id":1,"company_id":3,"category_id":2,"stock":100,"unit":"元\/500g","image":"http:\/\/canteen.tonglingok.com\/static\/image"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  商品id
     * @apiSuccess (返回参数说明) {int} company_id  企业id
     * @apiSuccess (返回参数说明) {int} supplier_id  供应商id
     * @apiSuccess (返回参数说明) {int} category_id  类型id
     * @apiSuccess (返回参数说明) {string} name  商品名称
     * @apiSuccess (返回参数说明) {float} price  价格
     * @apiSuccess (返回参数说明) {string} unit  单位
     * @apiSuccess (返回参数说明) {string} image  商品图片
     * @apiSuccess (返回参数说明) {int} count  商品库存
     */
    public function product()
    {
        $id = Request::param('id');
        $product = (new ShopService())->product($id);
        return json(new SuccessMessageWithData(['data' => $product]));
    }

    /**
     * @api {POST} /api/v1/shop/product/handel   CMS管理端-小卖部管理-商品管理-商品状态操作
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-小卖部管理-商品管理-商品状态操作
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "state": 2,
     *     }
     * @apiParam (请求参数说明) {int} id  供应商id
     * @apiParam (请求参数说明) {int} state  状态：1|上架；2|下架；3|删除
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function handel()
    {
        $id = Request::param('id');
        $state = Request::param('state');
        $product = ShopProductT::update(['state' => $state], ['id' => $id]);
        if (!$product) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/shop/products  CMS管理端-小卖部管理-商品管理-获取商品列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-商品管理-获取商品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/products?c_id=1&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} c_id 企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"name":"供应商1","account":"123456","c_id":2,"state":1,"create_time":"2019-09-19 10:02:39","company":"一级企业"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 供应商id
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {String} name  供应商名称
     * @apiSuccess (返回参数说明) {String} account  账号
     * @apiSuccess (返回参数说明) {String} company  企业名称
     */
    public function products($company_id, $supplier_id, $category_id, $page = 1, $size = 10)
    {
        $products = (new ShopService())->products($company_id, $supplier_id, $category_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $products]));
    }

    /**
     * @api {POST} /api/v1/shop/stock/save  CMS管理端-小卖部管理-商品管理-商品入库
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-小卖部管理-商品管理-商品入库
     * @apiExample {post}  请求样例:
     *    {
     *       "product_id": 5,
     *       "count": 100
     *     }
     * @apiParam (请求参数说明) {int} product_id  商品id
     * @apiParam (请求参数说明) {int} count  库存数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveProductStock()
    {
        $params = Request::param();
        (new ShopService())->saveProductStock($params);
        return json(new SuccessMessage());
    }



}