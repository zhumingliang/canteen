<?php


namespace app\api\service;


use app\api\model\CompanyStaffT;
use app\api\model\StaffV;
use app\api\model\UserT;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\TokenException;
use think\Exception;
use think\facade\Cache;
use think\facade\Request;
use zml\tp_tools\Redis;

class OfficialToken extends Token
{

    public function get($code)
    {
        try {
            $this->checkCode($code);
            $app = app('wechat.official_account.default');
            $info = $app->oauth->user();
            $user_info = $info->getOriginal();
            $openid = $user_info['openid'];
            $user = UserT::where('openid', $openid)->find();
            if (!$user) {
                $user_info['outsiders'] = CommonEnum::STATE_IS_FAIL;
                $user = UserT::create($user_info);
                $u_id = $user->id;
            } else {
                $u_id = $user->id;
                UserT::update($user_info, ['id' => $u_id]);
            }
        } catch (Exception $e) {
            throw  new SaveException(['errorCode' => 40163, 'msg' => $e->getMessage()]);
        }


        $cachedValue = $this->prepareCachedValue($u_id);
        $token = $this->saveToCache($cachedValue);
        return [
            'token' => $token,
            'phone' => empty($cachedValue['phone']) ? 2 : 1,
            'canteen_id' => $cachedValue['current_canteen_id'],
            'company_id' => $cachedValue['current_company_id'],
            'outsiders' => $cachedValue['outsiders'],
            'canteen_selected' => empty($cachedValue['current_canteen_id']) ? 2 : 1
        ];

    }


    private function checkCode($code)
    {
        $check = Redis::instance()->get($code);
        if ($check) {
            throw  new ParameterException(['errorCode' => 40163,
                'msg' => 'code been used']);
        }
        Redis::instance()->set($code, 1, 60 * 60);
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
        // $request = Cache::remember($key, $value, $expire_in);
        $request = Redis::instance()->set($key, $value, $expire_in);
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
        $cachedValue['outsiders'] = $user['outsiders'];
        $cachedValue['current_canteen_id'] = $user['current_canteen_id'];
        $cachedValue['current_company_id'] = $user['current_company_id'];
        $cachedValue['type'] = 'official';

        return $cachedValue;
    }

    public function updatePhone($phone)
    {
        self::updateCurrentTokenVar('phone', $phone);
    }

    public function updateOutsiders($outsiders)
    {
        self::updateCurrentTokenVar('outsiders', $outsiders);
    }


}