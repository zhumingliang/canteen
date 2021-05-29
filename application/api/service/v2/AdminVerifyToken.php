<?php


namespace app\api\service\v2;


use app\api\model\AdminT;
use app\api\service\Token;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\TokenException;
use think\captcha\Captcha;
use think\Exception;
use zml\tp_tools\Redis;

class AdminVerifyToken extends Token
{
    protected $account;
    protected $passwd;
    protected $code;


    function __construct($account, $passwd, $code)
    {
        $this->account = $account;
        $this->passwd = $passwd;
        $this->code = $code;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get()
    {
        try {
            $this->check_verify();
            $admin = AdminT::where('account', '=', $this->account)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->find();

            if (is_null($admin) || (sha1($this->passwd) != $admin->passwd)) {
                throw new TokenException([
                    'msg' => '账号或密码不正确',
                    'errorCode' => 30000
                ]);
            }
            /**
             * 获取缓存参数
             */
            $cachedValue = $this->prepareCachedValue($admin);
            /**
             * 缓存数据
             */
            $token = $this->saveToCache('', $cachedValue);
            //进行绑定
            return $token;

        } catch (Exception $e) {
            throw $e;
        }

    }

    private function check_verify()
    {

        $captcha = new Captcha();
        if (!$captcha->checkByCache($this->code)) {
            // 验证失败
            throw  new  ParameterException(['msg' => "验证码不正确"]);
        }
    }


    /**
     * @param $key
     * @param $cachedValue
     * @return mixed
     * @throws TokenException
     */
    private function saveToCache($key, $cachedValue)
    {
        $key = empty($key) ? self::generateToken() : $key;
        $value = json_encode($cachedValue);
        $expire_in = config('setting.token_cms_expire_in');
        // $request = Cache::remember($key, $value, $expire_in);
        $request = Redis::instance()->set($key, $value, $expire_in);
        if (!$request) {
            throw new TokenException([
                'msg' => '服务器缓存异常',
                'errorCode' => 20002
            ]);
        }

        return [
            'token' => $key,
            'role' => $cachedValue['role'],
            'grade' => $cachedValue['grade'],
            'company_id' => $cachedValue['company_id']
        ];
    }

    private function prepareCachedValue($admin)
    {

        $cachedValue = [
            'u_id' => $admin->id,
            'role' => $admin->role,
            'account' => $admin->account,
            'grade' => $admin->grade,
            'company_id' => $admin->c_id,
            'parent_id' => $admin->parent_id,
            'type' => 'cms'
        ];
        return $cachedValue;
    }

}