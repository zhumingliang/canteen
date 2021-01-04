<?php


namespace app\api\service;


use app\lib\exception\SaveException;
use app\lib\Image;

class ImageService
{
    public function upload($file)
    {
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/image';
        if (!is_dir($path)) {
            mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
        }
        $info = $file->move($path);
        if (!$info) {
            throw new SaveException();
        }
        $path = '/static/image/' . $info->getSaveName();
        $srcPath = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/image' . $info->getSaveName();
        $savePath = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/image/wechat/' . $info->getSaveName();
        Image::mkThumbnail($srcPath, 165, 200, $savePath);
        return ['url' => $path];
    }

    public function saveCompanyQRCode($url)
    {
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/qrcode';
        if (!is_dir($path)) {
            mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
        }
        $content = file_get_contents($url); // 得到二进制图片内容
        $name = guid();
        $info = file_put_contents($path . "/$name.jpg", $content); // 写入文件
        if (!$info) {
            throw new SaveException();
        }
        return '/static/qrcode' . "/$name.jpg";
    }

}