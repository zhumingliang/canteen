<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\ImageService;
use app\lib\exception\ParameterException;
use app\lib\exception\SuccessMessageWithData;

class Image extends BaseController
{
    /**
     * @api {POST}  /api/v1/image/upload CMS管理端-上传图片
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  用file控件上传excel ，文件名称为：image
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":""}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     * @apiSuccess (返回参数说明) {String} url 图片地址
     */
    public function upload()
    {
        $image = request()->file('image');
        if (is_null($image)) {
            throw  new ParameterException(['msg' => '上传图片为空']);
        }
        $url = (new ImageService())->upload($image);
        return json(new SuccessMessageWithData(['data' => $url]));
    }

}