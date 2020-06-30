<?php
/**
 * Created by PhpStorm.
 * User: zhumingliang
 * Date: 2018/3/20
 * Time: 下午1:24
 */


namespace app\api\behavior;

use app\api\service\FlowService;
use app\api\service\Token;
use app\lib\enum\BookingReportEnum;
use Rollbar\Rollbar;
use think\facade\Request;


class CORS
{
    public function appInit($params)
    {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

        $allowOrigin = array(
            'https://cloudcanteen3.51canteen.com',
            'http://cloudcanteen3.51canteen.cn',
        );

        if (in_array($origin, $allowOrigin)) {
            header("Access-Control-Allow-Origin:" . $origin);
        }
        //解决跨域
        // header('Access-Control-Allow-Origin: ,');
        header("Access-Control-Allow-Headers: token,Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: POST,GET');
        header('X-Content-Type-Options: nosniff');
        header("Pragma: no-cache");
        header('cache-control: no-cache, no-store, must-revalidate');
        header("X-XSS-Protection: 1");
        if (request()->isOptions()) {
            exit();
        }
    }
}