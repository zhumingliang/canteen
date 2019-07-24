<?php
/**
 * Created by PhpStorm.
 * User: zhumingliang
 * Date: 2018/3/20
 * Time: 下午1:26
 */

namespace app\lib\exception;


class GetInfoException extends BaseException
{
    public $msg = '获取数据不存在';
    public $errorCode = 60001;

}