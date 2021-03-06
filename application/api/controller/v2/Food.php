<?php


namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\service\v2\FoodService;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Food extends BaseController
{
    /**
     * @api {GET} /api/v2/foods/personChoice 微信端-个人选菜-菜品列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-个人选菜-菜品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v2/foods/personChoice?day=2021-02-28
     * @apiParam (请求参数说明) {string} day 选菜日期
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":458,"name":"早餐","menu":[{"id":547,"d_id":458,"category":"包点","status":1,"count":20,"foods":[{"id":8787,"day":"2021-02-21","f_id":1093,"status":1,"default":2,"m_id":547,"d_id":458,"name":"灌汤包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/9f1e3a3456002ff7496b3962e84c2430.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8788,"day":"2021-02-21","f_id":1095,"status":1,"default":2,"m_id":547,"d_id":458,"name":"小笼包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/640ce1da4fdda02a8bdbd5a7dea12d17.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"}]},{"id":548,"d_id":458,"category":"饺子","status":1,"count":20,"foods":[{"id":8787,"day":"2021-02-21","f_id":1093,"status":1,"default":2,"m_id":547,"d_id":458,"name":"灌汤包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/9f1e3a3456002ff7496b3962e84c2430.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8788,"day":"2021-02-21","f_id":1095,"status":1,"default":2,"m_id":547,"d_id":458,"name":"小笼包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/640ce1da4fdda02a8bdbd5a7dea12d17.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8789,"day":"2021-02-21","f_id":1096,"status":1,"default":2,"m_id":548,"d_id":458,"name":"玉米瘦肉饺","price":"4.50","img_url":"\/static\/image\/wechat\/20210126\/748bfaa29b734e5eaa383d7ee7d302eb.png","f_type":2,"chef":" ","des":" ","external_price":"5.50"}]},{"id":549,"d_id":458,"category":"粥类","status":1,"count":20,"foods":[{"id":8787,"day":"2021-02-21","f_id":1093,"status":1,"default":2,"m_id":547,"d_id":458,"name":"灌汤包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/9f1e3a3456002ff7496b3962e84c2430.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8788,"day":"2021-02-21","f_id":1095,"status":1,"default":2,"m_id":547,"d_id":458,"name":"小笼包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/640ce1da4fdda02a8bdbd5a7dea12d17.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8789,"day":"2021-02-21","f_id":1096,"status":1,"default":2,"m_id":548,"d_id":458,"name":"玉米瘦肉饺","price":"4.50","img_url":"\/static\/image\/wechat\/20210126\/748bfaa29b734e5eaa383d7ee7d302eb.png","f_type":2,"chef":" ","des":" ","external_price":"5.50"}]}],"menus":[{"id":547,"d_id":458,"category":"包点","status":1,"count":20,"foods":[{"id":8787,"day":"2021-02-21","f_id":1093,"status":1,"default":2,"m_id":547,"d_id":458,"name":"灌汤包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/9f1e3a3456002ff7496b3962e84c2430.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8788,"day":"2021-02-21","f_id":1095,"status":1,"default":2,"m_id":547,"d_id":458,"name":"小笼包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/640ce1da4fdda02a8bdbd5a7dea12d17.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"}]},{"id":548,"d_id":458,"category":"饺子","status":1,"count":20,"foods":[{"id":8787,"day":"2021-02-21","f_id":1093,"status":1,"default":2,"m_id":547,"d_id":458,"name":"灌汤包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/9f1e3a3456002ff7496b3962e84c2430.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8788,"day":"2021-02-21","f_id":1095,"status":1,"default":2,"m_id":547,"d_id":458,"name":"小笼包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/640ce1da4fdda02a8bdbd5a7dea12d17.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8789,"day":"2021-02-21","f_id":1096,"status":1,"default":2,"m_id":548,"d_id":458,"name":"玉米瘦肉饺","price":"4.50","img_url":"\/static\/image\/wechat\/20210126\/748bfaa29b734e5eaa383d7ee7d302eb.png","f_type":2,"chef":" ","des":" ","external_price":"5.50"}]},{"id":549,"d_id":458,"category":"粥类","status":1,"count":20,"foods":[{"id":8787,"day":"2021-02-21","f_id":1093,"status":1,"default":2,"m_id":547,"d_id":458,"name":"灌汤包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/9f1e3a3456002ff7496b3962e84c2430.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8788,"day":"2021-02-21","f_id":1095,"status":1,"default":2,"m_id":547,"d_id":458,"name":"小笼包","price":"5.00","img_url":"\/static\/image\/wechat\/20210126\/640ce1da4fdda02a8bdbd5a7dea12d17.png","f_type":2,"chef":" ","des":" ","external_price":"6.00"},{"id":8789,"day":"2021-02-21","f_id":1096,"status":1,"default":2,"m_id":548,"d_id":458,"name":"玉米瘦肉饺","price":"4.50","img_url":"\/static\/image\/wechat\/20210126\/748bfaa29b734e5eaa383d7ee7d302eb.png","f_type":2,"chef":" ","des":" ","external_price":"5.50"}]}]},{"id":459,"name":"午餐","menu":[{"id":550,"d_id":459,"category":"大碗饭","status":1,"count":20,"foods":[{"id":8790,"day":"2021-02-21","f_id":1097,"status":1,"default":2,"m_id":550,"d_id":459,"name":"四宝大碗饭","price":"15.00","img_url":"\/static\/image\/wechat\/20210126\/cdabe309e33aa0ac6514b8e0a2897c0b.png","f_type":2,"chef":" ","des":" ","external_price":"18.00"}]},{"id":551,"d_id":459,"category":"汤粉面","status":1,"count":20,"foods":[{"id":8790,"day":"2021-02-21","f_id":1097,"status":1,"default":2,"m_id":550,"d_id":459,"name":"四宝大碗饭","price":"15.00","img_url":"\/static\/image\/wechat\/20210126\/cdabe309e33aa0ac6514b8e0a2897c0b.png","f_type":2,"chef":" ","des":" ","external_price":"18.00"},{"id":8791,"day":"2021-02-21","f_id":1099,"status":1,"default":2,"m_id":551,"d_id":459,"name":"牛腩云吞双拼面","price":"13.00","img_url":"\/static\/image\/wechat\/20210126\/a43f4bd11dae2bc6d229967c0959deeb.png","f_type":2,"chef":" ","des":" ","external_price":"15.00"}]}],"menus":[{"id":550,"d_id":459,"category":"大碗饭","status":1,"count":20,"foods":[{"id":8790,"day":"2021-02-21","f_id":1097,"status":1,"default":2,"m_id":550,"d_id":459,"name":"四宝大碗饭","price":"15.00","img_url":"\/static\/image\/wechat\/20210126\/cdabe309e33aa0ac6514b8e0a2897c0b.png","f_type":2,"chef":" ","des":" ","external_price":"18.00"}]},{"id":551,"d_id":459,"category":"汤粉面","status":1,"count":20,"foods":[{"id":8790,"day":"2021-02-21","f_id":1097,"status":1,"default":2,"m_id":550,"d_id":459,"name":"四宝大碗饭","price":"15.00","img_url":"\/static\/image\/wechat\/20210126\/cdabe309e33aa0ac6514b8e0a2897c0b.png","f_type":2,"chef":" ","des":" ","external_price":"18.00"},{"id":8791,"day":"2021-02-21","f_id":1099,"status":1,"default":2,"m_id":551,"d_id":459,"name":"牛腩云吞双拼面","price":"13.00","img_url":"\/static\/image\/wechat\/20210126\/a43f4bd11dae2bc6d229967c0959deeb.png","f_type":2,"chef":" ","des":" ","external_price":"15.00"}]}]},{"id":460,"name":"晚餐","menu":[{"id":552,"d_id":460,"category":"大碗饭","status":1,"count":20,"foods":[{"id":8792,"day":"2021-02-21","f_id":1101,"status":1,"default":2,"m_id":552,"d_id":460,"name":"四宝大碗饭","price":"15.00","img_url":"\/static\/image\/wechat\/20210126\/213bded6fa8e75d54d27dae47cde036f.png","f_type":2,"chef":" ","des":" ","external_price":"18.00"}]},{"id":553,"d_id":460,"category":"汤粉面","status":1,"count":20,"foods":[{"id":8792,"day":"2021-02-21","f_id":1101,"status":1,"default":2,"m_id":552,"d_id":460,"name":"四宝大碗饭","price":"15.00","img_url":"\/static\/image\/wechat\/20210126\/213bded6fa8e75d54d27dae47cde036f.png","f_type":2,"chef":" ","des":" ","external_price":"18.00"},{"id":8793,"day":"2021-02-21","f_id":1103,"status":1,"default":2,"m_id":553,"d_id":460,"name":"牛腩云吞双拼面","price":"13.00","img_url":"\/static\/image\/wechat\/20210126\/44dcaba8c3d2d5c8257dae712d793d5a.png","f_type":2,"chef":" ","des":" ","external_price":"15.00"}]}],"menus":[{"id":552,"d_id":460,"category":"大碗饭","status":1,"count":20,"foods":[{"id":8792,"day":"2021-02-21","f_id":1101,"status":1,"default":2,"m_id":552,"d_id":460,"name":"四宝大碗饭","price":"15.00","img_url":"\/static\/image\/wechat\/20210126\/213bded6fa8e75d54d27dae47cde036f.png","f_type":2,"chef":" ","des":" ","external_price":"18.00"}]},{"id":553,"d_id":460,"category":"汤粉面","status":1,"count":20,"foods":[{"id":8792,"day":"2021-02-21","f_id":1101,"status":1,"default":2,"m_id":552,"d_id":460,"name":"四宝大碗饭","price":"15.00","img_url":"\/static\/image\/wechat\/20210126\/213bded6fa8e75d54d27dae47cde036f.png","f_type":2,"chef":" ","des":" ","external_price":"18.00"},{"id":8793,"day":"2021-02-21","f_id":1103,"status":1,"default":2,"m_id":553,"d_id":460,"name":"牛腩云吞双拼面","price":"13.00","img_url":"\/static\/image\/wechat\/20210126\/44dcaba8c3d2d5c8257dae712d793d5a.png","f_type":2,"chef":" ","des":" ","external_price":"15.00"}]}]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 餐次id
     * @apiSuccess (返回参数说明) {string} name 餐次名称
     * @apiSuccess (返回参数说明) {obj} menu 菜单信息
     * @apiSuccess (返回参数说明) {int} id 菜品类别id
     * @apiSuccess (返回参数说明) {string} category  菜品类别名称
     * @apiSuccess (返回参数说明) {int}  status 菜品状态:1|固定；2|动态
     * @apiSuccess (返回参数说明) {int}  count 固定状态下可选数量
     * @apiSuccess (返回参数说明) {obj} foods  菜品
     * @apiSuccess (返回参数说明) {int} f_id  菜品id
     * @apiSuccess (返回参数说明) {sting} day  日期
     * @apiSuccess (返回参数说明) {sting} name  菜品名称
     * @apiSuccess (返回参数说明) {float} price  菜品价格
     * @apiSuccess (返回参数说明) {float} external_price  对外价格
     * @apiSuccess (返回参数说明) {string} img_url 菜品图片地址
     */
    public function foodsForOfficialPersonChoice()
    {
        $day = Request::param('day');
        $foods = (new FoodService())->foodsForOfficialPersonChoice($day);
        return json(new SuccessMessageWithData(['data' => $foods]));
    }


    /**
     * @api {GET} /api/v2/food/day 微信端-个人选菜-查看餐次有选菜日期
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-个人选菜-菜品列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v2/food/day
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"dinner_id":477,"day":"2021-02-26"},{"dinner_id":478,"day":"2021-02-26"},{"dinner_id":479,"day":"2021-02-26"},{"dinner_id":479,"day":"2021-03-05"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} dinner_id 餐次id
     * @apiSuccess (返回参数说明) {string} day 已配日期
     */
    public function haveFoodDay()
    {
        $day = (new FoodService())->haveFoodDay();
        return json(new SuccessMessageWithData(['data' => $day]));
    }


}