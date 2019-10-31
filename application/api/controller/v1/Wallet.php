<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\WalletService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Wallet extends BaseController
{
    /**
     * @api {POST} /api/v1/waller/recharge/cash CMS管理端--新增饭堂配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-新增饭堂配置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 2,
     *       "dinners":[{"name":"早餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"07:00","meal_time_end":"08:00","fixed":2},{"name":"中餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_bgin":"12:00","meal_time_end":"13:00","fixed":1}],
     *     }
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiParam (请求参数说明) {string} dinners  订餐信息json字符串
     * @apiParam (请求参数说明) {string} name  餐次名称
     * @apiParam (请求参数说明) {string} type  时间设置类别：day|week；1、前n天是填写数字，说明每天的餐需要提前一个天数来订餐;2、周，是只能填写周一到周日，说明一周的订餐规定需要在每周某天进行下周一整周的订餐
     * @apiParam (请求参数说明) {int} type_number 订餐时间类别对应数量 （week：0-6；周日-周六）
     * @apiParam (请求参数说明) {string} limit_time  订餐限制时间
     * @apiParam (请求参数说明) {string} meal_time_begin  就餐开始时间
     * @apiParam (请求参数说明) {string} meal_time_end  就餐截止时间
     * @apiParam (请求参数说明) {int} fixed  餐次是否采用标准金额：1｜是；2｜否
     * @apiParam (请求参数说明) {obj} account 饭堂账户设置
     * @apiParam (请求参数说明) {int} dining_mode  个人选菜就餐方式：1｜食堂；2｜外卖；3｜全部
     * @apiParam (请求参数说明) {int} type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiParam (请求参数说明) {int} clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiParam (请求参数说明) {int} clean_day  每月清零具体日期
     * @apiParam (请求参数说明) {int} clean_time  每月清零具体时间
     * @apiParam (请求参数说明) {int} limit_money  可预消费金额
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function rechargeCash()
    {
        $params = Request::param();
        (new WalletService())->rechargeCash($params);
        return json(new SuccessMessage());

    }

}