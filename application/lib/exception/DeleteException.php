<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2019-02-25
 * Time: 01:40
 */

namespace app\lib\exception;


class DeleteException extends BaseException
{
    public $msg = '删除操作失败';
    public $errorCode = 50001;

}