<?php


namespace app\api\service;


use app\lib\exception\SaveException;

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
        return ['url' => '/static/image/' . $info->getSaveName()];
    }

}