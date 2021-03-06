<?php


namespace app\api\service;


use app\lib\enum\ScopeEnum;
use app\lib\exception\ForbiddenException;
use app\lib\exception\TokenException;
use think\Cache;
use think\Exception;
use think\facade\Request;
use zml\tp_tools\Redis;

class Token
{
    public static function generateToken()
    {
        //32个字符组成一组随机字符串
        $randChars = getRandChar(32);
        //用三组字符串，进行md5加密
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        //salt 盐
        $salt = config('secure.token_salt');

        return md5($randChars . $timestamp . $salt);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     * @throws TokenException
     */
    public static function getCurrentTokenVar($key = '')
    {

        $token = Request::header('token');
        $vars = Redis::instance()->get($token);
        //$vars = \think\facade\Cache::get($token);
        if (!$vars) {
            throw new TokenException();
        } else {
            if ($key == '') {
                return $vars;
            }
            if (!is_array($vars)) {
                $vars = json_decode($vars, true);
            }
            if (array_key_exists($key, $vars)) {
                return $vars[$key];
            } else {
                throw new Exception('尝试获取的Token变量并不存在');
            }
        }
    }


    public static function getCurrentTokenVar2($token, $key = '')
    {

        $vars = Redis::instance()->get($token);
        if (!$vars) {
            throw new TokenException();
        } else {
            if ($key == '') {
                return $vars;
            }
            if (!is_array($vars)) {
                $vars = json_decode($vars, true);
            }
            if (array_key_exists($key, $vars)) {
                return $vars[$key];
            } else {
                throw new Exception('尝试获取的Token变量并不存在');
            }
        }
    }


    public static function updateCurrentTokenVar($key = '', $value = '')
    {

        $token = Request::header('token');
        //$vars = \think\facade\Cache::get($token);
        $vars = Redis::instance()->get($token);
        if (!$vars) {
            throw new TokenException();
        } else {
            $vars = json_decode($vars, true);
            $vars[$key] = $value;
            $expire_in = config('setting.token_official_expire_in');
            $res = Redis::instance()->set($token, json_encode($vars), $expire_in);
            if (!$res) {
                throw  new TokenException(['msg' => '更新缓存失败']);

            }

        }
    }


    /**
     * @return mixed
     * @throws Exception
     * @throws TokenException
     */
    public static function getCurrentUid()
    {
        //token
        $uid = self::getCurrentTokenVar('u_id');
        return $uid;
    }

    public static function getCurrentPhone()
    {
        //token
        $phone = self::getCurrentTokenVar('phone');
        return $phone;
    }


    /**
     * @return mixed
     * @throws Exception
     * @throws TokenException
     */
    public static function getCurrentOpenid()
    {
        $uid = self::getCurrentTokenVar('openId');
        return $uid;
    }


    public static function verifyToken($token)
    {
        $exist = Cache::get($token);
        if ($exist) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $checkedOpenid
     * @return bool
     * @throws Exception
     * @throws TokenException
     */
    public static function isValidOperate($checkedOpenid)
    {
        if (!$checkedOpenid) {
            throw new Exception('检查openid时必须传入一个被检查的openid');
        }
        $currentOperateUID = self::getCurrentOpenid();
        if ($currentOperateUID == $checkedOpenid) {
            return true;
        }
        return false;
    }

}