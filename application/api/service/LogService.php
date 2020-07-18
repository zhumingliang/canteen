<?php


namespace app\api\service;


use app\api\model\JobLogT;
use app\api\model\LogT;

class LogService
{
    public static function save($msg)
    {
        LogT::create([
            'content' => $msg
        ]);

    }

    public static function saveJob($msg, $data = '')
    {
        JobLogT::create([
            'content' => $msg,
            'data' => $data
        ]);
    }

}