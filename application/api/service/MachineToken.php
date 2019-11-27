<?php


namespace app\api\service;


use app\api\model\AdminT;
use app\api\model\MachineT;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\TokenException;
use GatewayClient\Gateway;
use think\Exception;
use think\facade\Cache;

class MachineToken
{

    public function get($code, $passwd, $client_id)
    {
        try {

            $machine = MachineT::where('code', '=', $code)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->find();

            if (is_null($machine) || (sha1($passwd) != $machine->passwd)) {
                throw new TokenException([
                    'msg' => '账号或密码不正确',
                    'errorCode' => 30000
                ]);
            }
            if (empty($machine->company_id)) {
                throw new ParameterException(['msg' =>'设备异常，没有归属企业']);
            }

            /**
             * 获取缓存参数
             */
            $cachedValue = $this->prepareCachedValue($machine);
            /**
             * 缓存数据
             */
            $token = $this->saveToCache('', $cachedValue);

            //进行绑定
            $this->bind($cachedValue['company_id'], $client_id, $cachedValue['u_id']);
            return $token;

        } catch (Exception $e) {
            throw $e;
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
        $expire_in = config('setting.token_machine_expire_in');
        $request = Cache::remember($key, $value, $expire_in);


        if (!$request) {
            throw new TokenException([
                'msg' => '服务器缓存异常',
                'errorCode' => 20002
            ]);
        }

        return [
            'token' => $key,
            'u_id' => $cachedValue['u_id']
        ];
    }

    private function prepareCachedValue($machine)
    {

        $cachedValue = [
            'u_id' => $machine->id,
            'company_id' => $machine->company_id,
            'belong_id' => $machine->belong_id,
            'name' => $machine->name,
            'code' => $machine->code,
            'number' => $machine->number,
            'type' => $machine->machine_type
        ];
        return $cachedValue;
    }


    private function bind($company_id, $client_id, $machine_id)
    {
        $group = 'canteen:company:' . $company_id;
        Gateway::joinGroup($client_id, $group);
        Gateway::bindUid($client_id, $machine_id);

    }

}