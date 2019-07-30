<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CanteenService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;

class Canteen extends BaseController
{
    /**
     * @api {POST} /api/v1/canteen/save CMS管理端-新增饭堂
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-新增饭堂
     * @apiExample {post}  请求样例:
     *    {
     *       "canteens": ["饭堂1号","饭堂2号"],
     *       "c_id": 2
     *     }
     * @apiParam (请求参数说明) {string} canteens  饭堂名称json字符串
     * @apiParam (请求参数说明) {int} c_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = $this->request->param();
        (new CanteenService())->save($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/canteen/configuration/save CMS管理端-新增饭堂配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-新增饭堂配置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 2,
     *       "dinners":[{"name":"早餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"07:00","meal_time_end":"08:00"},{"name":"中餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_bgin":"12:00","meal_time_end":"13:00"}],
     *       "account":{"type":2,"clean_type":3,"clean_day":0}
     *     }
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiParam (请求参数说明) {string} dinners  订餐信息json字符串
     * @apiParam (请求参数说明) {string} dinners|name  餐次名称
     * @apiParam (请求参数说明) {string} dinners|type  时间设置类别：day|week|month
     * @apiParam (请求参数说明) {int} dinners|type_number 订餐时间类别对应数量
     * @apiParam (请求参数说明) {string} dinners|limit_time  订餐限制时间
     * @apiParam (请求参数说明) {string} dinners|meal_time_begin  就餐开始时间
     * @apiParam (请求参数说明) {string} dinners|meal_time_end  就餐截止时间
     * @apiParam (请求参数说明) {string} account 饭堂账户设置
     * @apiParam (请求参数说明) {int} account|type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiParam (请求参数说明) {int} account|clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiParam (请求参数说明) {int} account|clean_day  每月清零具体日期
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveConfiguration()
    {
        $params = $this->request->param();
        (new CanteenService())->saveConfiguration($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/canteen/configuration  CMS管理端-获取饭堂设置信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取饭堂设置信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/canteen/configuration?c_id=2
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiSuccessExample {json} 系统功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"dinners":[{"id":5,"c_id":1,"name":"早餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_bgin":"07:00:00","meal_time_end":"08:00:00","limit_time":"10:00:00"},{"id":6,"c_id":1,"name":"中餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_bgin":"12:00:00","meal_time_end":"13:00:00","limit_time":"10:00:00"}],"account":{"id":3,"c_id":1,"type":2,"clean_type":3,"clean_day":0,"create_time":"2019-07-30 02:07:17"}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {string} dinners  订餐信息json字符串
     * @apiSuccess (返回参数说明) {string} dinners|id  餐次id
     * @apiSuccess (返回参数说明) {string} dinners|name  餐次名称
     * @apiSuccess (返回参数说明) {string} dinners|type  时间设置类别：day|week|month
     * @apiSuccess (返回参数说明) {string} dinners|create_time  创建时间
     * @apiSuccess (返回参数说明) {int} dinners|type_number 订餐时间类别对应数量
     * @apiSuccess (返回参数说明) {string} dinners|limit_time  订餐限制时间
     * @apiSuccess (返回参数说明) {string} dinners|meal_time_begin  就餐开始时间
     * @apiSuccess (返回参数说明) {string} dinners|meal_time_end  就餐截止时间
     * @apiSuccess (返回参数说明) {string} account 账户设置
     * @apiSuccess (返回参数说明) {int} account|id  设置id
     * @apiSuccess (返回参数说明) {int} account|type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiSuccess (返回参数说明) {int} account|clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiSuccess (返回参数说明) {int} account|clean_day  每月清零具体日期
     * @apiSuccess (返回参数说明) {int} account|create_time  创建时间
     */
    public function configuration()
    {
        $c_id = $this->request->param('c_id');
        $info = (new CanteenService())->configuration($c_id);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/canteen/configuration/update CMS管理端-更新饭堂配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-更新饭堂配置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 2,
     *       "dinners":[{"id":1,"name":"早餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"07:00","meal_time_end":"08:00"},{"name":"晚餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"18:00","meal_time_end":"19:00"}],
     *       "account":{"id":1,"type":2,"clean_type":3,"clean_day":0}
     *     }
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiParam (请求参数说明) {string} dinners  订餐信息json字符串
     * @apiParam (请求参数说明) {string} dinners|id  餐次设置id，更新操作需要传如此字段
     * @apiParam (请求参数说明) {string} dinners|name  餐次名称
     * @apiParam (请求参数说明) {string} dinners|type  时间设置类别：day|week|month
     * @apiParam (请求参数说明) {int} dinners|type_number 订餐时间类别对应数量
     * @apiParam (请求参数说明) {string} dinners|limit_time  订餐限制时间
     * @apiParam (请求参数说明) {string} dinners|meal_time_begin  就餐开始时间
     * @apiParam (请求参数说明) {string} dinners|meal_time_end  就餐截止时间
     * @apiParam (请求参数说明) {string} account 饭堂账户设置
     * @apiParam (请求参数说明) {int} account|id  饭堂账户设置ID
     * @apiParam (请求参数说明) {int} account|type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiParam (请求参数说明) {int} account|clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiParam (请求参数说明) {int} account|clean_day  每月清零具体日期
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateConfiguration()
    {
        $params = $this->request->param();
        (new CanteenService())->updateConfiguration($params);
        return json(new SuccessMessage());
    }


}