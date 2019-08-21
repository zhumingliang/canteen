<?php


namespace app\api\service;


use app\api\model\UserT;
use app\lib\exception\TokenException;
use think\facade\Cache;

class OfficialToken extends Token
{

    public function get($info)
    {
        $user_info = $info->getOriginal();
        $openid = $user_info['openid'];
        $user = UserT::where('openid', $openid)->find();
        if (!$user) {
            $user = UserT::create($user_info);
            $u_id = $user->id;
        } else {
            $u_id = $user->id;
        }


        $cachedValue = $this->prepareCachedValue($u_id);
        $token = $this->saveToCache($cachedValue);
        return [
            'token' => $token,
            'phone' => empty($cachedValue['phone']) ? 2 : 1
        ];
    }


    /**
     * @param $cachedValue
     * @return string
     * @throws TokenException
     */
    private function saveToCache($cachedValue)
    {
        $key = self::generateToken();
        $value = json_encode($cachedValue);
        $expire_in = config('setting.token_official_expire_in');
        $request = Cache::remember($key, $value, $expire_in);
        //$request = Redis::instance()->set($key, $value, $expire_in);
        if (!$request) {
            throw new TokenException([
                'msg' => '服务器缓存用户数据异常',
                'errorCode' => 20002
            ]);
        }
        return $key;
    }


    private function prepareCachedValue($u_id)
    {
        $cachedValue = [];
        $user = UserT::where('id', $u_id)
            ->find()->toArray();

        $cachedValue['u_id'] = $u_id;
        $cachedValue['phone'] = $user['phone'];
        $cachedValue['openId'] = $user['openid'];
        $cachedValue['province'] = $user['province'];
        $cachedValue['nickName'] = $user['nickname'];
        $cachedValue['type'] = 'official';
        return $cachedValue;
    }


}