<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2019-02-25
 * Time: 01:39
 */

namespace app\lib\exception;


class UpdateException extends BaseException
{
    public $msg = '更新操作失败';
    public $errorCode = 70001;

}