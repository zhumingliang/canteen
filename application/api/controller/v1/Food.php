<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\FoodService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Food extends BaseController
{
    /**
     * @api {POST} /api/v1/food/save CMS管理端-菜品管理-新增菜品
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-菜品管理-新增菜品
     * @apiExample {post}  请求样例:
     *    {
     *       "f_type": 1,
     *       "m_id": 1,
     *       "c_id": 6,
     *       "name": "红烧土豆牛肉",
     *       "price": 5,
     *       "chef":"李大厨",
     *       "des": "适合**人群，有利于***不适合***人群",
     *       "material": [{"name":"牛肉","count":"10"},{"name":"土豆","count":"10"}],
     *       "img_url": "/static/image/20190810/ab9ce8ff0e2c5adb40263641b24f36d4.png",
     *     }
     * @apiParam (请求参数说明) {int} f_type  菜品是否为无选菜：1|是；2|否
     * @apiParam (请求参数说明) {string} m_id 菜单菜品id
     * @apiParam (请求参数说明) {string} c_id 饭堂id
     * @apiParam (请求参数说明) {string} name 菜品名称
     * @apiParam (请求参数说明) {string} price  菜品价格
     * @apiParam (请求参数说明) {string} chef  主厨名称
     * @apiParam (请求参数说明) {string} des  描述
     * @apiParam (请求参数说明) {string} img_url 菜品图片地址：由新增图片接口  /api/v1/image/upload 返回
     * @apiParam (请求参数说明) {string} material 菜品材料明细
     * @apiParam (请求参数说明) {string} material|name 菜品材料名称
     * @apiParam (请求参数说明) {string} material|count 菜品材料数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new FoodService())->save($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/food/update CMS管理端-菜品管理-修改菜品
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-菜品管理-新增菜品
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 2,
     *       "m_id": 1,
     *       "name": "西红柿牛肉",
     *       "price": 5,
     *       "chef":"李大厨",
     *       "des": "适合**人群，有利于***不适合***人群",
     *       "material": [{"id":1,"count":"15"},{"id":2,"state":2},{"name":"西红柿","count":"10"}],
     *       "img_url": "/static/image/20190810/ab9ce8ff0e2c5adb40263641b24f36d4.png",
     *     }
     * @apiParam (请求参数说明) {int} id  菜品ID
     * @apiParam (请求参数说明) {string} m_id 菜单菜品id
     * @apiParam (请求参数说明) {string} name 菜品名称
     * @apiParam (请求参数说明) {string} price  菜品价格
     * @apiParam (请求参数说明) {string} chef  主厨名称
     * @apiParam (请求参数说明) {string} des  描述
     * @apiParam (请求参数说明) {string} img_url 菜品图片地址：由新增图片接口  /api/v1/image/upload 返回
     * @apiParam (请求参数说明) {string} material 菜品材料明细
     * @apiParam (请求参数说明) {string} material|id 菜品材料id：修改操作时传入
     * @apiParam (请求参数说明) {string} material|state 菜品材料状态：1|正常；2|删除：修改操作时传入
     * @apiParam (请求参数说明) {string} material|name 菜品材料名称
     * @apiParam (请求参数说明) {string} material|count 菜品材料数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::param();
        (new FoodService())->update($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/food/handel CMS管理端-菜品状态设置
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription   CMS管理端-菜品状态设置
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {int} id  菜品ID
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function handel()
    {
        $params = Request::param();
        (new FoodService())->handel($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/foods CMS管理端-菜品列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-菜品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/foods?f_type=1&page=1&size=10&menu_ids='1'&dinner_ids='1'&canteen_ids='1'&company_ids='1'
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} f_type  菜品是否为无选菜：1|是；2|否
     * @apiParam (请求参数说明) {String} menu_ids 类型ids，选择全部时传入所有id并逗号分隔,选择此选择项其他筛选字段（dinner_ids/canteen_ids/company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {String} dinner_ids 餐次ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（menu_ids/canteen_ids/company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {String} canteen_ids 饭堂ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（menu_ids，dinner_ids/company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {String} company_ids 公司ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（menu_ids/dinner_ids/canteen_ids）无需上传无需上传
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"name":"红烧牛肉","price":5,"f_type":1,"chef":"李大厨","des":"适合**人群，有利于***不适合***人群","img_url":"http:\/\/canteen.tonglingok.com\/static\/image\/20190810\/ab9ce8ff0e2c5adb40263641b24f36d4.png","state":1,"menu":"荤菜","dinner":"中餐","create_time":"2019-08-10 01:07:24"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 菜品id
     * @apiSuccess (返回参数说明) {string} name  菜品名称
     * @apiSuccess (返回参数说明) {float} price  菜品价格
     * @apiSuccess (返回参数说明) {string} chef  主厨名称
     * @apiSuccess (返回参数说明) {string} menu  菜品类别
     * @apiSuccess (返回参数说明) {string} dinner  餐次
     * @apiSuccess (返回参数说明) {string} des  描述
     * @apiSuccess (返回参数说明) {string} img_url 菜品图片地址
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     */
    public function foods($page = 1, $size = 10)
    {
        $params = Request::param();
        $foods = (new FoodService())->foods($page, $size, $params);
        return json(new SuccessMessageWithData(['data' => $foods]));
    }

    /**
     * @api {GET} /api/v1/food CMS管理端-获取菜品信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-获取菜品信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/food?id=3
     * @apiParam (请求参数说明) {int} id 菜品id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":3,"name":"西红柿牛肉","price":5,"chef":"李大厨","des":"适合**人群，有利于***不适合***人群","img_url":"http:\/\/canteen.tonglingok.com\/static\/image\/20190810\/ab9ce8ff0e2c5adb40263641b24f36d4.png","state":2,"menu":"荤菜","dinner":"中餐","material":[{"id":1,"f_id":3,"name":"牛肉","count":15},{"id":3,"f_id":3,"name":"西红柿","count":10}]}}
     * @apiSuccess (返回参数说明) {string} id 菜品id
     * @apiSuccess (返回参数说明) {string} name 菜品名称
     * @apiSuccess (返回参数说明) {string} price  菜品价格
     * @apiSuccess (返回参数说明) {string} chef  主厨名称
     * @apiSuccess (返回参数说明) {string} des  描述
     * @apiSuccess (返回参数说明) {string} menu  菜品类别
     * @apiSuccess (返回参数说明) {string} dinner  餐次
     * @apiSuccess (返回参数说明) {string} img_url 菜品图片地址：由新增图片接口  /api/v1/image/upload 返回
     * @apiSuccess (返回参数说明) {string} material 菜品材料明细
     * @apiSuccess (返回参数说明) {string} material|id 菜品材料id
     * @apiSuccess (返回参数说明) {string} material|name 菜品材料名称
     * @apiSuccess (返回参数说明) {string} material|count 菜品材料数量
     */
    public function food()
    {
        $id = Request::param('id');
        $food = (new FoodService())->food($id);
        return json(new SuccessMessageWithData(['data' => $food]));
    }

    /**
     * @api {POST} /api/v1/food/material/update CMS管理端-菜品材料明细-编辑菜品材料明细
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-菜品材料明细-编辑菜品材料明细
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 2,
     *       "material": [{"id":1,"count":"15"},{"id":2,"state":2},{"name":"西红柿","count":"10"}],
     *     }
     * @apiParam (请求参数说明) {int} id  菜品ID
     * @apiParam (请求参数说明) {string} material 菜品材料明细
     * @apiParam (请求参数说明) {string} material|id 菜品材料id：修改操作时传入
     * @apiParam (请求参数说明) {string} material|state 菜品材料状态：1|正常；2|删除：修改操作时传入
     * @apiParam (请求参数说明) {string} material|name 菜品材料名称
     * @apiParam (请求参数说明) {string} material|count 菜品材料数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateMaterial()
    {
        $params = Request::param();
        (new FoodService())->updateMaterial($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/foods/officialManager 微信端-菜品管理-菜品信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-菜品管理-菜品信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/foods/officialManager?$page=1&size=100&menu_id=1&food_type=2&day=2019-09-02&canteen_id=3
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} food_type  菜品是否为无选菜：1|是；2|否
     * @apiParam (请求参数说明) {int} menu_id 类型id
     * @apiParam (请求参数说明) {String} day 日期
     * @apiParam (请求参数说明) {String} canteen_id 饭堂ID
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":100,"current_page":1,"last_page":1,"data":[{"id":1,"name":"红烧牛肉","img_url":"http:\/\/canteen.tonglingok.com\/static\/image\/20190810\/ab9ce8ff0e2c5adb40263641b24f36d4.png","price":5,"status":2,"default":2}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 菜品id
     * @apiSuccess (返回参数说明) {string} name  菜品名称
     * @apiSuccess (返回参数说明) {float} price  菜品价格
     * @apiSuccess (返回参数说明) {string} img_url 菜品图片地址
     * @apiSuccess (返回参数说明) {int} status 菜品上架状态：1|上架；2|下架
     * @apiSuccess (返回参数说明) {int} default 菜品默认状态：1|默认；2|非默认
     */
    public function foodsForOfficialManager($page = 1, $size = 100)
    {
        $menu_id = Request::param('menu_id');
        $food_type = Request::param('food_type');
        $day = Request::param('day');
        $canteen_id = Request::param('canteen_id');
        $foods = (new FoodService())->foodsForOfficialManager($menu_id, $food_type, $day, $canteen_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $foods]));
    }

    /**
     * @api {POST} /api/v1/food/day/handel 微信端-菜品管理-菜品状态操作
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription   微信端-菜品管理-菜品状态操作
     * @apiExample {post}  请求样例:
     *    {
     *       "food_id": 1，
     *       "canteen_id": 1，
     *       "day": 2019-09-01，
     *       "status": 1，
     *       "default": 1，
     *     }
     * @apiParam (请求参数说明) {int} food_id  菜品ID
     * @apiParam (请求参数说明) {int} canteen_id  饭堂ID
     * @apiParam (请求参数说明) {String} day 日期
     * @apiParam (请求参数说明) {int} status 菜品状态：1|上架；2|下架
     * @apiParam (请求参数说明) {int} default 菜品默认状态：1|默认；2|非默认
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function handelFoodsDayStatus()
    {
        $params = Request::param();
        (new FoodService())->handelFoodsDayStatus($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/foods/personChoice 微信端-个人选菜-菜品列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-个人选菜-菜品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/foods/personChoice?dinner_id=6
     * @apiParam (请求参数说明) {int} dinner_id 餐次ID
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"category":"荤菜","status":1,"count":3,"foods":[{"id":2,"day":"2019-09-03","f_id":1,"status":2,"m_id":1,"d_id":6,"name":"红烧牛肉","price":5,"img_url":"\/static\/image\/20190810\/ab9ce8ff0e2c5adb40263641b24f36d4.png","f_type":2},{"id":3,"day":"2019-09-04","f_id":1,"status":1,"m_id":1,"d_id":6,"name":"红烧牛肉","price":5,"img_url":"\/static\/image\/20190810\/ab9ce8ff0e2c5adb40263641b24f36d4.png","f_type":2}]},{"id":2,"category":"汤","status":2,"count":0,"foods":[]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 菜品类别id
     * @apiSuccess (返回参数说明) {string} category  菜品类别名称
     * @apiSuccess (返回参数说明) {int}  status 菜品状态:1|固定；2|动态
     * @apiSuccess (返回参数说明) {int}  count 固定状态下可选数量
     * @apiSuccess (返回参数说明) {obj} foods  菜品
     * @apiSuccess (返回参数说明) {int} f_id  菜品id
     * @apiSuccess (返回参数说明) {sting} day  日期
     * @apiSuccess (返回参数说明) {sting} name  菜品名称
     * @apiSuccess (返回参数说明) {float} price  菜品价格
     * @apiSuccess (返回参数说明) {string} img_url 菜品图片地址
     */
    public function foodsForOfficialPersonChoice()
    {
        $d_id = Request::param('dinner_id');
        $foods = (new FoodService())->foodsForOfficialPersonChoice($d_id);
        return json(new SuccessMessageWithData(['data' => $foods]));
    }

    /**
     * @api {POST} /api/v1/food/saveComment  微信端--个人选菜--评价菜品
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端--个人选菜--评价菜品
     * @apiExample {post}  请求样例:
     *    {
     *       "food_id": 1,
     *       "taste": 5,
     *       "service": 5
     *       "remark": "味道好极了"
     *     }
     * @apiParam (请求参数说明) {int} food_id  菜品id
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
        (new FoodService())->saveComment($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/food/info/comment   微信端--个人选菜/菜谱查询-点击评论获取菜品评论信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端--个人选菜/菜谱查询-点击评论获取菜品评论信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/food/info/comment?food_id=1
     * @apiParam (请求参数说明) {int} id 菜品id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"food":{"id":1,"name":"红烧牛肉","price":5,"img_url":"http:\/\/canteen.tonglingok.com\/static\/image\/20190810\/ab9ce8ff0e2c5adb40263641b24f36d4.png","chef":"李大厨","comments":[{"id":4,"u_id":3,"f_id":1,"taste":3,"service":3,"remark":"4"},{"id":3,"u_id":3,"f_id":1,"taste":4,"service":4,"remark":"3"},{"id":2,"u_id":3,"f_id":1,"taste":5,"service":5,"remark":"2"}]},"canteenScore":{"taste":4.3,"service":4.3}}}
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {obj} food 菜品信息
     * @apiSuccess (返回参数说明) {string} food|id 菜品id
     * @apiSuccess (返回参数说明) {string} food|name 菜品名称
     * @apiSuccess (返回参数说明) {string} food|price  菜品价格
     * @apiSuccess (返回参数说明) {string} food|chef  主厨名称
     * @apiSuccess (返回参数说明) {obj} comments  评论信息
     * @apiSuccess (返回参数说明) {int} comments|id 评论id
     * @apiSuccess (返回参数说明) {string} comments|taste 菜品味道评分
     * @apiSuccess (返回参数说明) {string} comments|service 菜品服务评分
     * @apiSuccess (返回参数说明) {string} comments|remark 评分说明
     * @apiSuccess (返回参数说明) {obj} canteenScore 饭堂评分
     * @apiSuccess (返回参数说明) {string} canteenScore|taste 饭堂味道评分
     * @apiSuccess (返回参数说明) {string} canteenScore|service 饭堂服务评分
     */
    public function infoToComment()
    {
        $food_id = Request::param('food_id');
        $info = (new FoodService())->infoToComment($food_id);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {GET} /api/v1/foods/menu 微信端-菜谱查询-菜品列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-菜谱查询-菜品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/foods/foods/menu?dinner_id=6
     * @apiParam (请求参数说明) {int} dinner_id 餐次ID
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"category":"荤菜","status":1,"count":3,"foods":[{"id":3,"day":"2019-09-07","f_id":1,"status":1,"default":2,"m_id":1,"d_id":6,"name":"红烧牛肉","price":5,"img_url":"\/static\/image\/20190810\/ab9ce8ff0e2c5adb40263641b24f36d4.png","f_type":2,"chef":"李大厨","des":"适合**人群，有利于***不适合***人群","materials":[{"id":1,"f_id":3,"name":"牛肉","count":15,"unit":"kg"},{"id":2,"f_id":3,"name":"土豆","count":10,"unit":"kg"},{"id":3,"f_id":3,"name":"西红柿","count":10,"unit":"kg"}]}]},{"id":2,"category":"汤","status":2,"count":0,"foods":[]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 菜品类别id
     * @apiSuccess (返回参数说明) {string} category  菜品类别名称
     * @apiSuccess (返回参数说明) {int}  status 菜品状态:1|固定；2|动态
     * @apiSuccess (返回参数说明) {int}  count 固定状态下可选数量
     * @apiSuccess (返回参数说明) {obj} foods  菜品
     * @apiSuccess (返回参数说明) {int} foods|f_id  菜品id
     * @apiSuccess (返回参数说明) {sting} foods|day  日期
     * @apiSuccess (返回参数说明) {sting} foods|name  菜品名称
     * @apiSuccess (返回参数说明) {float} foods|price  菜品价格
     * @apiSuccess (返回参数说明) {string} foods|img_url 菜品图片地址
     * @apiSuccess (返回参数说明) {string} foods|chef 菜品主厨
     * @apiSuccess (返回参数说明) {obj} materials 菜品材料明细
     * @apiSuccess (返回参数说明) {string} materials|name 菜品材料名称
     * @apiSuccess (返回参数说明) {string} materials|count 菜品材料数量
     * @apiSuccess (返回参数说明) {string} materials|unit 菜品材料数量单位
     */
    public function foodsForOfficialMenu()
    {
        $d_id = Request::param('dinner_id');
        $foods = (new FoodService())->foodsForOfficialMenu($d_id);
        return json(new SuccessMessageWithData(['data' => $foods]));
    }


}