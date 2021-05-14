<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2019-03-07
 * Time: 23:54
 */

namespace app\api\middleware;


use app\api\service\Token;
use think\Controller;

class Auth extends Controller
{
    public function handle($request, \Closure $next)
    {
        $allowAction = [
            'test',
            'getadmintoken',
            'getofficialtoken',
            'getmachinetoken',
            'loginout',
            'getsuppliertoken',
            'printer',
            'sendtemplate',
            'wxnotifyurl',
        ];

        $action = $request->action();
        if (!in_array($action, $allowAction)) {
            Token::getCurrentTokenVar();
        }
        return $next($request);
    }


}