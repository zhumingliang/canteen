<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Submitequity;
use app\lib\exception\SuccessMessageWithData;

class Index extends BaseController
{
    public function index()
    {
      var_dump( Submitequity::all());
    }

}