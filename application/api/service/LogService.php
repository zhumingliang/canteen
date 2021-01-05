<?php


namespace app\api\service;


use app\api\model\JobLogT;
use app\api\model\LogT;
use app\api\model\TaskLogT;

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

    public static function saveTask($msg, $data = '')
    {
        TaskLogT::create([
            'content' => $msg,
            'data' => $data
        ]);
    }

}