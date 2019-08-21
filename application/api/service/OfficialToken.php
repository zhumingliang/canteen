<?php


namespace app\api\service;


use app\lib\exception\TokenException;

class OfficialToken extends Token
{

    public function get($info)
    {

        $user = UserPublicT::where('openid', $this->openid)->find();

        if (!$user) {
            $userPublic = UserPublicT::create(['openid' => $this->openid]);
            $u_id = $userPublic->id;
        } else {
            $u_id = $user->id;
        }


        $cachedValue = $this->prepareCachedValue($u_id);
        $token = $this->saveToCache($cachedValue);

        if (!strlen($cachedValue['nickName']) && !strlen($cachedValue['province'])) {
            return [
                'token' => $token,
                'type' => $this->USER_MSG_IS_NULL,
            ];

        }


        return [
            'token' => $token,
            'type' => $this->USER_MSG_IS_OK,
            'phone' => $cachedValue['phone']
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
        $expire_in = config('setting.token_expire_in');
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
        $user = UserPublicT::where('id', $u_id)
            ->find()->toArray();

        $cachedValue['phone'] = $user['phone'];
        $cachedValue['openId'] = $user['openid'];
        $cachedValue['province'] = $user['province'];
        $cachedValue['nickName'] = $user['nickname'];
        $cachedValue['type'] = 'public';
        $cachedValue['scene'] = 2;
        $cachedValue['u_id'] = $u_id;
        return $cachedValue;
    }


}