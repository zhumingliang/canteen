<?php


namespace app\api\controller\v2;


use app\api\service\WalletService;
use app\lib\exception\ParameterException;
use app\lib\exception\SuccessMessageWithData;

class Wallet
{
    /**
     * @api {POST}  /api/v2/wallet/recharge/upload CMS管理端--充值管理(分账户)--批量充值
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  用file控件上传excel ，文件名称为：cash
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     */
    public function rechargeCashUpload()
    {
        $cash_excel = request()->file('cash');
        if (is_null($cash_excel)) {
            throw  new ParameterException(['msg' => '缺少excel文件']);
        }
        $res = (new WalletService())->rechargeCashUploadWithAccount($cash_excel);
        return json(new SuccessMessageWithData(['data' => $res]));

    }

}