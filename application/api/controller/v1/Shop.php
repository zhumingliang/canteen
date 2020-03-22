<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\ShopOrderDetailV;
use app\api\model\ShopOrderT;
use app\api\model\ShopProductT;
use app\api\model\ShopT;
use app\api\service\ShopService;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class Shop extends BaseController
{
    /**
     * @api {POST} /api/v1/shop/product/save  CMS管理端-小卖部管理-商品管理-新增商品(只有供应商才有权限)
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-小卖部管理-商品管理-新增商品
     * @apiExample {post}  请求样例:
     *    {
     *       "name": "鸡蛋",
     *       "price": 8,
     *       "category_id": 8,
     *       "unit": "元/500g",
     *       "count": 100,
     *       "image": "/static/image/a.png",
     *     }
     * @apiParam (请求参数说明) {string} name  商品名称
     * @apiParam (请求参数说明) {float} price  价格
     * @apiParam (请求参数说明) {int} category_id  商品类别id
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
     * @api {POST} /api/v1/shop/product/update  CMS管理端-小卖部管理-商品管理-修改商品(只有供应商才有权限)
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-小卖部管理-商品管理-修改商品
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 5,
     *       "name": "鸡蛋",
     *       "price": 8,
     *       "unit": "元/500g",
     *       "image": "/static/image/a.png",
     *     }
     * @apiParam (请求参数说明) {int} id  商品id
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
     * @api {POST} /api/v1/shop/product/handel   CMS管理端-小卖部管理-商品管理-商品状态操作(只有供应商才有权限)
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-小卖部管理-商品管理-商品状态操作
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "state": 2,
     *     }
     * @apiParam (请求参数说明) {int} id  商品id
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
     * @api {POST} /api/v1/shop/stock/save  CMS管理端-小卖部管理-商品管理-商品入库(供应商才有权限)
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
     * http://canteen.tonglingok.com/api/v1/shop/official/products
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
     * @apiSuccess (返回参数说明) {int} state 商品状态：1 | 上架；2| 下架
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
     * @apiParam (请求参数说明) {string} product_id 商品id
     * @apiParam (请求参数说明) {string} price 商品实时单价
     * @apiParam (请求参数说明) {string} count 商品数量
     * @apiParam (请求参数说明) {string} name 商品名称
     * @apiParam (请求参数说明) {string} unit 商品单位
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
     * {"msg":"ok","errorCode":0,"code":200,"data":{"time_begin":"","time_end":"","url":""}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {string} time_begin  申请时间
     * @apiSuccess (返回参数说明) {string} time_end  有效时间
     * @apiSuccess (返回参数说明) {string} url  提货二维码地址
     */
    public function deliveryCode()
    {
        $order_id = Request::param('id');
        $url = (new ShopService())->deliveryCode($order_id);
        return json(new SuccessMessageWithData(['data' => ['url' => $url]]));
    }

    /**
     * @api {POST} /api/v1/shop/save  CMS管理端-企业管理-新增小卖部
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-企业管理-新增小卖部
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 1,
     *       "name": "小卖部",
     *       "taking_mode": 3
     *     }
     * @apiParam (请求参数说明) {int} c_id  企业id
     * @apiParam (请求参数说明) {string} name  小卖部名称
     * @apiParam (请求参数说明) {string} taking_mode  取货方式：1｜到店取；2｜送货上门；3｜全部都显示
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"shop_id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveShop()
    {
        $params = Request::param();
        $shop = (new ShopService())->save($params);
        return json(new SuccessMessageWithData(['data' => $shop]));

    }

    /**
     * @api {POST} /api/v1/shop/update  CMS管理端-企业管理-更新小卖部
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-企业管理-更新小卖部
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": "小卖部",
     *       "taking_mode": 3
     *     }
     * @apiParam (请求参数说明) {int} id  小卖部id
     * @apiParam (请求参数说明) {string} taking_mode  取货方式：1｜到店取；2｜送货上门；3｜全部都显示
     * @apiParam (请求参数说明) {string} name  小卖部名称
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateShop()
    {
        $params = Request::param();
        $shop = ShopT::update($params);
        if (!$shop) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/shop/delete   CMS管理端-企业管理-小卖部删除
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-企业管理-小卖部删除
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {int} id  小卖部id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function deleteShop()
    {
        $id = Request::param('id');
        $shop = ShopProductT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$shop) {
            throw new DeleteException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/shop/takingMode  微信端--小卖部--获取当前小卖部取货方式
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端--小卖部--获取当前小卖部取货方式
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/takingMode
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"taking_mode":3}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {string} taking_mode  取货方式：1｜到店取；2｜送货上门；3｜全部都显示
     */
    public function takingMode()
    {
        $mode = (new ShopService())->takingMode();
        return json(new SuccessMessageWithData(['data' => $mode]));
    }

    /**
     * @api {GET} /api/v1/shop/order/statistic/supplier CMS管理端-小卖部管理-订单明细查询-供应商
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-订单明细查询-供应商
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/statistic/supplier?category_id=0&product_id=0&time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} category_id  商品类型id：0表示全部
     * @apiParam (请求参数说明) {int} product_id  商品id：0表示全部
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":10,"per_page":1,"current_page":1,"last_page":10,"data":[{"create_time":"2019-10-28 23:49:27","product":"langbing","price":"17.0","count":1,"category":"商品12"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} create_time 下单时间
     * @apiSuccess (返回参数说明) {string} product 商品名称
     * @apiSuccess (返回参数说明) {string} count 数量
     * @apiSuccess (返回参数说明) {obj} price 单价
     * @apiSuccess (返回参数说明) {string} category 类别
     */
    public function orderDetailStatisticToSupplier($page = 1, $size = 20, $category_id = 0, $product_id = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $statistic = (new ShopService())->orderDetailStatisticToSupplier($page, $size,
            $category_id, $product_id, $time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }


    /**
     * @api {GET} /api/v1/shop/order/exportOrderStatistic/supplier CMS管理端-小卖部管理-订单明细查询-供应商-导出
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-小卖部管理-订单明细查询-供应商-导出
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/exportOrderStatistic/supplier?ppage=1&size=10&date[]=2019-10-01&date[]=2019-11-30&department_id=0&company_id=3&time_begin=2019-10-01&time_end=2019-11-30&status=0
     * @apiParam (请求参数说明) {int} category_id  商品类型id：0表示全部
     * @apiParam (请求参数说明) {int} product_id  商品id：0表示全部
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportOrderStatisticToSupplier($category_id = 0, $product_id = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $url = (new ShopService())->exportOrderDetailStatisticToSupplier($category_id, $product_id, $time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $url]));
    }


    /**
     * @api {GET} /api/v1/shop/order/statistic/manager CMS管理端-小卖部管理-订单明细查询-管理员
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-订单明细查询-管理员
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/statistic/manager?department_id=0&status=0&name=''&phone=''&time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20&company_id=3
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} department_id  部门id：0表示全部
     * @apiParam (请求参数说明) {int} status 状态：0表示全部；1：已完成；2：已取消；3：待取货；4：待送货
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} name  姓名
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":11,"per_page":1,"current_page":1,"last_page":11,"data":[{"order_id":6,"create_time":"2019-09-28 08:14:10","used_time":null,"username":"LANGBIN","phone":"15521323081","order_count":2,"money":10,"address_id":1,"address":{"id":1,"address":"江门市白石大道东4号路3栋"}}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} order_id 订单id
     * @apiSuccess (返回参数说明) {string} create_time 下单时间
     * @apiSuccess (返回参数说明) {string} used_time 结束时间
     * @apiSuccess (返回参数说明) {string} username 用户姓名
     * @apiSuccess (返回参数说明) {string} phone 手机号
     * @apiSuccess (返回参数说明) {float} money 订单金额
     * @apiSuccess (返回参数说明) {int} address_id 地址id
     * @apiSuccess (返回参数说明) {obj} address 地址信息
     * @apiSuccess (返回参数说明) {string} address 地址详情
     */
    public function orderStatisticToManager($page = 1, $size = 20, $department_id = 0, $name = '', $phone = '', $status = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_id = Request::param('company_id');
        $statistic = (new ShopService())->orderStatisticToManager($page, $size,
            $department_id, $name, $phone, $status, $time_begin, $time_end, $company_id);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * @api {GET} /api/v1/shop/order/exportOrderStatistic/manager CMS管理端-小卖部管理-订单明细查询-管理员-导出
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-小卖部管理-订单明细查询-管理员-导出
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/exportOrderStatistic/manager?ppage=1&size=10&date[]=2019-10-01&date[]=2019-11-30&department_id=0&company_id=3&time_begin=2019-10-01&time_end=2019-11-30&status=0
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} department_id  部门id：0表示全部
     * @apiParam (请求参数说明) {int} status 状态：0表示全部；1：已完成；2：已取消；3：待取货；4：待送货
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} name  姓名
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportOrderStatisticToManager($department_id = 0, $name = '', $phone = '', $status = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_id = Request::param('company_id');
        $url = (new ShopService())->exportOrderStatisticToManager($department_id, $name, $phone, $status, $time_begin, $time_end, $company_id);
        return json(new SuccessMessageWithData(['data' => $url]));
    }


    /**
     * @api {GET} /api/v1/shop/salesReport/supplier CMS管理端-小卖部管理-进销报表-供应商
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-进销报表-供应商
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/salesReport/supplier?time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":4,"per_page":"20","current_page":1,"last_page":1,"data":[{"id":29,"name":"超烧饭","price":"18.0","unit":"盒","purchase_sum":"20","sale_sum":"3"},{"id":30,"name":"炒牛肉","price":"18.0","unit":"份","purchase_sum":"20","sale_sum":"5"},{"id":31,"name":"河粉","price":"20.0","unit":"份","purchase_sum":"20","sale_sum":"1"},{"id":32,"name":"捞面","price":"8.0","unit":"份","purchase_sum":"100","sale_sum":"1"}],"money":172}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 商品id
     * @apiSuccess (返回参数说明) {string} name 名称
     * @apiSuccess (返回参数说明) {string} price 价格
     * @apiSuccess (返回参数说明) {string} unit 单位
     * @apiSuccess (返回参数说明) {int} purchase_sum 总进货量
     * @apiSuccess (返回参数说明) {int} sale_sum 总销售量
     * @apiSuccess (返回参数说明) {float} money 报表总销售额
     */
    public function salesReportToSupplier($page = 1, $size = 20)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $statistic = (new ShopService())->salesReportToSupplier($page, $size, $time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * @api {GET} /api/v1/shop/order/exportSalesReport/supplier CMS管理端-小卖部管理-进销报表-供应商-导出报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-进销报表-供应商-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/exportSalesReport/supplier?time_begin=2019-10-01&time_end=2019-12-31
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportSalesReportToSupplier()
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $statistic = (new ShopService())->exportSalesReportToSupplier($time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }


    /**
     * @api {GET} /api/v1/shop/salesReport/manager CMS管理端-小卖部管理-进销报表-管理员
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-进销报表-管理员
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/salesReport/manager?supplier_id=1&time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} supplier_id  供应商id
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":4,"per_page":"20","current_page":1,"last_page":1,"data":[{"id":29,"name":"超烧饭","price":"18.0","unit":"盒","purchase_sum":"20","sale_sum":"3"},{"id":30,"name":"炒牛肉","price":"18.0","unit":"份","purchase_sum":"20","sale_sum":"5"},{"id":31,"name":"河粉","price":"20.0","unit":"份","purchase_sum":"20","sale_sum":"1"},{"id":32,"name":"捞面","price":"8.0","unit":"份","purchase_sum":"100","sale_sum":"1"}],"money":172}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 商品id
     * @apiSuccess (返回参数说明) {string} name 名称
     * @apiSuccess (返回参数说明) {string} price 价格
     * @apiSuccess (返回参数说明) {string} unit 单位
     * @apiSuccess (返回参数说明) {int} purchase_sum 总进货量
     * @apiSuccess (返回参数说明) {int} sale_sum 总销售量
     * @apiSuccess (返回参数说明) {float} money 报表总销售额
     */
    public function salesReportToManager($page = 1, $size = 20)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $supplier_id = Request::param('supplier_id');
        $statistic = (new ShopService())->salesReportToManager($page, $size, $time_begin, $time_end, $supplier_id);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * @api {GET} /api/v1/shop/order/exportSalesReport/manager CMS管理端-小卖部管理-进销报表-管理员-导出报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-进销报表-管理员-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/exportSalesReport/manager?supplier_id=10&time_begin=2019-10-01&time_end=2019-12-31
     * @apiParam (请求参数说明) {int} supplier_id  供应商id
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportSalesReportToManager()
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $supplier_id = Request::param('supplier_id');
        $statistic = (new ShopService())->exportSalesReportToManager($time_begin, $time_end, $supplier_id);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * @api {GET} /api/v1/shop/orderConsumption CMS管理端-小卖部管理-消费订单汇总查询-供应商/管理员
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-消费订单汇总查询-供应商/管理员
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/orderConsumption?time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20&category_id=0&product_id=0&status=0&status=1&department_id=0&username=&company_id=3
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} department_id  部门id：全部传入0
     * @apiParam (请求参数说明) {int} username  用户名
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} category_id  类型id：全部传入0
     * @apiParam (请求参数说明) {int} category_id  类型id：全部传入0
     * @apiParam (请求参数说明) {int} product_id  商品id：全部传入0
     * @apiParam (请求参数说明) {int} status  状态：全部传入0；1：已完成；2：已取消；3：待取货；4：待送货
     * @apiParam (请求参数说明) {int} type  汇总类型：1：按类型进行汇总；2：按商品进行汇总；3：按状态进行汇总；4：按姓名进行汇总；5：按部门进行汇总
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":20,"current_page":1,"last_page":1,"data":[{"statistic":"商品12","create_time":"2019-10-28 23:49:27","used_time":null,"username":"\/","department":"\/","category":"商品12","unit":"kg","product":"\/","order_count":"7","order_money":"119.00"},{"statistic":"公司餐","create_time":"2019-10-29 10:20:17","used_time":null,"username":"\/","department":"\/","category":"公司餐","unit":"元\/500g","product":"\/","order_count":"6","order_money":"48.00"}],"statistic":{"statisticCount":2,"money":167}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} statistic 统计变量
     * @apiSuccess (返回参数说明) {string} create_time 开始时间
     * @apiSuccess (返回参数说明) {string} used_time 结束时间
     * @apiSuccess (返回参数说明) {string} username 姓名
     * @apiSuccess (返回参数说明) {string} department 部门
     * @apiSuccess (返回参数说明) {string} category 类型
     * @apiSuccess (返回参数说明) {string} product 商品名称
     * @apiSuccess (返回参数说明) {string} unit 单位
     * @apiSuccess (返回参数说明) {int} order_count 数量
     * @apiSuccess (返回参数说明) {float} order_money 报表总销售额
     * @apiSuccess (返回参数说明) {float} statistic 报表合计统计
     * @apiSuccess (返回参数说明) {int} statisticCount 报表合计-统计变量
     * @apiSuccess (返回参数说明) {float} statisticMoney 报表合计-金额
     */
    public function consumptionStatistic($page = 1, $size = 20, $category_id = 0,
                                         $product_id = 0, $status = 0, $type = 1,
                                         $department_id = 0, $username = '')
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_id = Request::param('company_id');
        $statistic = (new ShopService())->consumptionStatistic($page, $size, $category_id, $product_id,
            $status, $time_begin, $time_end, $type, $department_id, $username, $company_id);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * @api {GET} /api/v1/shop/order/exportConsumptionStatistic CMS管理端-小卖部管理-消费订单汇总查询-供应商/管理员-导出
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-小卖部管理-消费订单汇总查询-供应商/管理员-导出
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/exportConsumptionStatistic?time_begin=2019-09-07&time_end=2019-12-07&page=1&size=20&category_id=0&product_id=0&status=0&status=1&department_id=0&username=&company_id=3
     * @apiParam (请求参数说明) {int} department_id  部门id：全部传入0
     * @apiParam (请求参数说明) {int} username  用户名
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} category_id  类型id：全部传入0
     * @apiParam (请求参数说明) {int} category_id  类型id：全部传入0
     * @apiParam (请求参数说明) {int} product_id  商品id：全部传入0
     * @apiParam (请求参数说明) {int} status  状态：全部传入0；1：已完成；2：已取消；3：待取货；4：待送货
     * @apiParam (请求参数说明) {int} type  汇总类型：1：按类型进行汇总；2：按商品进行汇总；3：按状态进行汇总；4：按姓名进行汇总；5：按部门进行汇总
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportConsumptionStatistic($category_id = 0,
                                               $product_id = 0, $status = 0, $type = 1,
                                               $department_id = 0, $username = '')
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $company_id = Request::param('company_id');
        $statistic = (new ShopService())->exportConsumptionStatistic($category_id, $product_id,
            $status, $time_begin, $time_end, $type, $department_id, $username, $company_id);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * @api {GET} /api/v1/shop/companyProducts/search CMS管理端-获取企业商品列表-查询接口
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取企业商品列表-查询接口
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/companyProducts/search?company_id=1&product=''
     * @apiParam (请求参数说明) {int} company_id  企业id，没有时，不需要传入此参数
     * @apiParam (请求参数说明) {int} product 商品关键词
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":2,"name":"鸡蛋"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 商品id
     * @apiSuccess (返回参数说明) {string} name 商品名称
     */
    public function companyProductsToSearch($company_id = 0, $product = '')
    {
        $products = (new ShopService())->companyProductsToSearch($company_id, $product);
        return json(new SuccessMessageWithData(['data' => $products]));
    }

    /**
     * @api {GET} /api/v1/shop/supplierProducts/search CMS管理端-供应商获取商品列表-查询接口
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-供应商获取商品列表-查询接口
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/companyProducts/search?product=''
     * @apiParam (请求参数说明) {int} product 商品关键词
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":2,"name":"鸡蛋"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 商品id
     * @apiSuccess (返回参数说明) {string} name 商品名称
     */
    public function supplierProductsToSearch($product = '')
    {
        $products = (new ShopService())->supplierProductsToSearch($product);
        return json(new SuccessMessageWithData(['data' => $products]));

    }

    /**
     * @api {GET} /api/v1/shop/order/products CMS管理端-小卖部管理-订单明细查询-订单明细
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-CMS管理端-小卖部管理-订单明细查询-订单明细
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/shop/order/products?order_id=11
     * @apiParam (请求参数说明) {int} order_id 订单id
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":11,"order_type":1,"u_id":5,"count":4,"ordering_type":"shop","address_id":3,"state":1,"used":2,"products":[{"detail_id":6,"order_id":11,"name":"langbing","count":1,"price":"17.00","unit":"kg","category":"商品12"},{"detail_id":7,"order_id":11,"name":"炒牛肉","count":1,"price":"18.00","unit":"份","category":"商品12"},{"detail_id":8,"order_id":11,"name":"超烧饭","count":1,"price":"18.00","unit":"盒","category":"商品12"},{"detail_id":9,"order_id":11,"name":"鸡蛋xxx","count":1,"price":"8.00","unit":"元\/500g","category":"公司餐"}],"address":{"id":3,"province":"广东省","city":"江门市","area":"蓬江区","address":"江门市白石大道东4号路3栋 ","name":"陈梓","phone":"18956225230","sex":2}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} count  订餐数量
     * @apiSuccess (返回参数说明) {string} ordering_date  饭堂订单中订餐日期
     * @apiSuccess (返回参数说明) {obj} address 地址信息
     * @apiSuccess (返回参数说明) {string} province  省
     * @apiSuccess (返回参数说明) {string} city  城市
     * @apiSuccess (返回参数说明) {string} area  区
     * @apiSuccess (返回参数说明) {string} address  详细地址
     * @apiSuccess (返回参数说明) {string} name  姓名
     * @apiSuccess (返回参数说明) {string} phone  手机号
     * @apiSuccess (返回参数说明) {int} sex  性别：1|男；2|女
     * @apiSuccess (返回参数说明) {obj} products
     * @apiSuccess (返回参数说明) {int} food_id 菜品id
     * @apiSuccess (返回参数说明) {string} price 菜品实时单价
     * @apiSuccess (返回参数说明) {string} count 菜品数量
     * @apiSuccess (返回参数说明) {string} name 菜品名称
     * @apiSuccess (返回参数说明) {string} unit 小卖部商品单位
     * @apiSuccess (返回参数说明) {string} category 类别
     */
    public function shopOrderProducts($order_id)
    {
        $products = ShopOrderT::orderInfoForStatistic($order_id);
        return json(new SuccessMessageWithData(['data' => $products]));

    }

    /**
     * @api {POST} /api/v1/shop/order/send CMS管理端-小卖部管理-订单明细查询-打印触发发货
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-小卖部管理-订单明细查询-打印触发发货
     * @apiExample {post}  请求样例:
     *    {
     *       "id": "1"
     *     }
     * @apiParam (请求参数说明) {string} ids  订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function send()
    {
        $order_id = Request::param('id');
        (new  ShopService())->printOrder($order_id);
        return json(new SuccessMessage());
    }

}