<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/9
 * Time: 16:45
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AddressService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Address extends BaseController
{
    /**
     * @api {POST} /api/v1/address/save  微信端-新增用户地址
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription     微信端-新增用户地址
     * @apiExample {post}  请求样例:
     *    {
     *       "province": "广东省",
     *       "city": "江门市",
     *       "area": "蓬江区",
     *       "address": "江门市白石大道东4号路3栋 ",
     *       "name": "张三",
     *       "phone": "18956225230",
     *       "sex": 1
     *     }
     * @apiParam (请求参数说明) {string} province  省
     * @apiParam (请求参数说明) {string} city  城市
     * @apiParam (请求参数说明) {string} area  区
     * @apiParam (请求参数说明) {string} address  详细地址
     * @apiParam (请求参数说明) {string} name  姓名
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiParam (请求参数说明) {int} sex  性别：1|男；2|女
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new AddressService())->save($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/address/update  微信端-更新用户地址
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription     微信端-更新用户地址
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "province": "广东省",
     *       "city": "江门市",
     *       "area": "蓬江区",
     *       "address": "江门市白石大道东4号路3栋 ",
     *       "name": "张三",
     *       "phone": "18956225230",
     *       "sex": 1
     *     }
     * @apiParam (请求参数说明) {int} id  地址id
     * @apiParam (请求参数说明) {string} province  省
     * @apiParam (请求参数说明) {string} city  城市
     * @apiParam (请求参数说明) {string} area  区
     * @apiParam (请求参数说明) {string} address  详细地址
     * @apiParam (请求参数说明) {string} name  姓名
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiParam (请求参数说明) {int} sex  性别：1|男；2|女
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::param();
        (new AddressService())->update($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/addresses 微信端--获取用户地址列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端--获取用户地址列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/addresses
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"limit":1,"delivery_fee":5,"limit_address":[{"id":1,"province":"北京市","area":"东城区","city":"北京市","address":"五环外去","state":1}],"user_address":[{"id":5,"u_id":40,"province":"北京市","area":"东城区","city":"北京市","address":"1203","name":"AAA","phone":"15521323081","default":2,"sex":1,"use":1},{"id":6,"u_id":40,"province":"北京市","area":"东城区","city":"北京市","address":"1111","name":"bbb","phone":"15521323081","default":2,"sex":2,"use":1},{"id":7,"u_id":40,"province":"北京市","area":"丰台区","city":"北京市","address":"23123","name":"ccc","phone":"15521323081","default":2,"sex":2,"use":2},{"id":8,"u_id":40,"province":"北京市","area":"通州区","city":"北京市","address":"121212","name":"cccc","phone":"15521323081","default":1,"sex":2,"use":2}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {string} limit  是否限制 1：限制；2：未限制
     * @apiSuccess (返回参数说明) {string} delivery_fee  外送配送费
     * @apiSuccess (返回参数说明) {obj} limit_address  限制地址列表
     * @apiSuccess (返回参数说明) {obj} user_address  用户地址列表
     * @apiSuccess (返回参数说明) {string} province  省
     * @apiSuccess (返回参数说明) {int} id  地址id
     * @apiSuccess (返回参数说明) {string} city  城市
     * @apiSuccess (返回参数说明) {string} area  区
     * @apiSuccess (返回参数说明) {string} address  详细地址
     * @apiSuccess (返回参数说明) {string} name  姓名
     * @apiSuccess (返回参数说明) {string} phone  手机号
     * @apiSuccess (返回参数说明) {int} sex  性别：1|男；2|女
     * @apiSuccess (返回参数说明) {int} default  是否默认：1|是；2|否
     */
    public function addresses()
    {
        $addresses = (new AddressService())->userAddresses();
        return json(new SuccessMessageWithData(['data' => $addresses]));
    }

}