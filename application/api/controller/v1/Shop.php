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
     *       "supplier_id": 1,
     *       "category_id": 2,
     *       "name": "鸡蛋",
     *       "price": 8,
     *       "unit": "元/500g",
     *       "count": 100,
     *       "image": "/static/image/a.png",
     *     }
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
        //供应商才有权限
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
        //供应商才有权限
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
        //供应商才有权限
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
        //供应商才有权限
        $id = Request::param('id');
        $state = Request::param('state');
        $product = ShopProductT::update(['state' => $state], ['id' => $id]);
        if (!$product) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());
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

    /**
     * @api {GET} /api/v1/shop/official/products 微信端-小卖部-商品列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-个人选菜-菜品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/products/official
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":2,"name":"生鲜","products":[{"id":5,"category_id":2,"name":"鸡蛋1","price":"100.0","unit":"元\/500g","image":"http:\/\/canteen.tonglingok.com\/static\/image"},{"id":6,"category_id":2,"name":"鸡蛋2","price":"100.0","unit":"元\/500g","image":"http:\/\/canteen.tonglingok.com\/static\/image"}]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 商品类别id
     * @apiSuccess (返回参数说明) {string} name 商品类别名称
     * @apiSuccess (返回参数说明) {obj} products  商品列表
     * @apiSuccess (返回参数说明) {int} products|id  商品id
     * @apiSuccess (返回参数说明) {sting} products|unit  单位
     * @apiSuccess (返回参数说明) {sting} products|name  商品名称
     * @apiSuccess (返回参数说明) {float} products|price  商品价格
     * @apiSuccess (返回参数说明) {string} products|image 商品图片地址
     */
    public function officialProducts()
    {
        $products = (new ShopService())->officialProducts();
        return json(new SuccessMessageWithData(['data' => $products]));
    }

    /**
     * @api {GET} /api/v1/shop/supplier/products  CMS管理端-小卖部管理-商品管理-供应商获取商品列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-小卖部管理-商品管理-供应商获取商品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/supplier/products?category_id=1&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} category_id 商品类型id：0表示全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":"10","current_page":1,"last_page":1,"data":[{"product_id":6,"image":"\/static\/image","name":"鸡蛋2","category":"生鲜","unit":"元\/500g","price":"100.0","stock":"100","supplier":"供应商1"},{"product_id":5,"image":"\/static\/image","name":"鸡蛋1","category":"生鲜","unit":"元\/500g","price":"100.0","stock":"100","supplier":"供应商1"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} product_id 商品id
     * @apiSuccess (返回参数说明) {string} name 商品名称
     * @apiSuccess (返回参数说明) {sting} unit  单位
     * @apiSuccess (返回参数说明) {float} price  商品价格
     * @apiSuccess (返回参数说明) {string} image 商品图片地址
     * @apiSuccess (返回参数说明) {int} stock 商品库存
     * @apiSuccess (返回参数说明) {string} category 商品类型
     * @apiSuccess (返回参数说明) {string} supplier 供货商
     */
    public function supplierProducts($category_id = 0, $page = 1, $size = 10)
    {
        $products = (new ShopService())->supplierProducts($category_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $products]));
    }

    /**
     * @api {GET} /api/v1/shop/cms/products  CMS管理端-小卖部管理-商品管理-企业账号获取商品列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-小卖部管理-商品管理-企业账号获取商品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/cms/products?$supplier_id&category_id=1&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} supplier_id 供应商id：0表示全部
     * @apiParam (请求参数说明) {int} category_id 商品类型id：0表示全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":"10","current_page":1,"last_page":1,"data":[{"product_id":6,"image":"\/static\/image","name":"鸡蛋2","category":"生鲜","unit":"元\/500g","price":"100.0","stock":"100","supplier":"供应商1"},{"product_id":5,"image":"\/static\/image","name":"鸡蛋1","category":"生鲜","unit":"元\/500g","price":"100.0","stock":"100","supplier":"供应商1"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} product_id 商品id
     * @apiSuccess (返回参数说明) {string} name 商品名称
     * @apiSuccess (返回参数说明) {sting} unit  单位
     * @apiSuccess (返回参数说明) {float} price  商品价格
     * @apiSuccess (返回参数说明) {string} image 商品图片地址
     * @apiSuccess (返回参数说明) {int} stock 商品库存
     * @apiSuccess (返回参数说明) {string} category 商品类型
     * @apiSuccess (返回参数说明) {string} supplier 供货商
     */
    public function cmsProducts($supplier_id = 0, $category_id = 0, $page = 1, $size = 10)
    {
        $products = (new ShopService())->cmsProducts($supplier_id, $category_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $products]));
    }

    /**
     * @api {POST} /api/v1/shop/order/save 微信端-小卖部-新增订单
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-小卖部-下单
     * @apiExample {post}  请求样例:
     *    {
     *       "count": 2,
     *       "distribution": 1,
     *       "address_id": 1,
     *       "products":[{"product_id":1,"name":"商品1","price":5,"unit":"kg","count":1},{"product_id":2,"name":"商品2","price":5,"unit":"kg","count":1}]
     *     }
     * @apiParam (请求参数说明) {int} count  数量
     * @apiParam (请求参数说明) {int} distribution  取货方式：1|到店取；2|送货上门
     * @apiParam (请求参数说明) {int} address_id  配送地址id
     * @apiParam (请求参数说明) {obj} products 商品信息
     * @apiParam (请求参数说明) {string} products|product_id 商品id
     * @apiParam (请求参数说明) {string} products|price 商品实时单价
     * @apiParam (请求参数说明) {string} products|count 商品数量
     * @apiParam (请求参数说明) {string} products|name 商品名称
     * @apiParam (请求参数说明) {string} products|unit 商品单位
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function saveOrder()
    {
        $params = Request::param();
        (new ShopService())->saveOrder($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/shop/product/saveComment  微信端--小卖部--评价商品
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription     微信端--小卖部--评价商品
     * @apiExample {post}  请求样例:
     *    {
     *       "product_id": 1,
     *       "taste": 5,
     *       "service": 5
     *       "remark": "味道好极了"
     *     }
     * @apiParam (请求参数说明) {int} product_id  商品id
     * @apiParam (请求参数说明) {int} taste  味道评分：1-5分
     * @apiParam (请求参数说明) {int} service  服务评分：1-5分
     * @apiParam (请求参数说明) {string} remark  评价内容
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveComment()
    {
        $params = Request::param();
        (new ShopService())->saveProductComment($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/shop/product/comments   微信端--小卖部-点击评论获取商品评论信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端--小卖部-点击评论获取商品评论信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/product/comments?product_id=1&page=1&size=10
     * @apiParam (请求参数说明) {int} product_id 商品id
     * @apiParam (请求参数说明) {int} page 页数
     * @apiParam (请求参数说明) {int} size 每条条数
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"comments":{"total":0,"per_page":"10","current_page":1,"last_page":0,"data":[]},"productScore":{"taste":0,"service":0}}}
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {obj}  comments 评论信息
     * @apiSuccess (返回参数说明) {int}  id 评论id
     * @apiSuccess (返回参数说明) {int}  product_id 评论商品
     * @apiSuccess (返回参数说明) {int}  u_id 评论用户
     * @apiSuccess (返回参数说明) {int} taste 味道评分
     * @apiSuccess (返回参数说明) {int} service 服务评分
     * @apiSuccess (返回参数说明) {string} remark 评分说明
     * @apiSuccess (返回参数说明) {obj} productScore 商品评价
     * @apiSuccess (返回参数说明) {float} taste 味道评分
     * @apiSuccess (返回参数说明) {float} service 服务评分
     */
    public function productComments($page = 1, $size = 10)
    {
        $product_id = Request::param('product_id');
        $comments = (new ShopService())->productComments($product_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $comments]));
    }

    /**
     * @api {POST} /api/v1/shop/order/cancel 微信端-订单查询-取消小卖部订单
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-订单查询-取消小卖部订单
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {string} id  订餐id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function orderCancel()
    {
        $id = Request::param('id');
        (new ShopService())->orderCancel($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/shop/order/deliveryCode   微信端-订单查询-获取小卖部订单提货码
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  微信端-订单查询-获取小卖部订单提货码
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/deliveryCode?id=8
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":""}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} url  提货二维码地址
     */
    public function deliveryCode()
    {
        $order_id = Request::param('id');
        $url = (new ShopService())->deliveryCode($order_id);
        return json(new SuccessMessageWithData(['data' => ['url' => $url]]));
    }
}