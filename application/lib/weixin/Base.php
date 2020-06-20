<?php


namespace app\lib\weixin;


use Naixiaoxin\ThinkWechat\Facade;

class Base
{
    public $app = null;

    public function __construct()
    {
        $this->app = Facade::officialAccount();
    }

}