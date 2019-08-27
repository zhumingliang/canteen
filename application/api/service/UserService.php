<?php


namespace app\api\service;


use app\api\model\AdminT;
use app\api\model\StaffQrcodeT;
use app\api\model\UserT;
use app\lib\enum\CommonEnum;
use app\lib\exception\AuthException;
use app\lib\exception\UpdateException;
use think\facade\Request;
use zml\tp_tools\Redis;

class UserService
{
    public function bindPhone($phone, $code)
    {
        $token = Request::header('token');
        $current_code = Redis::instance()->get($token);
        if (!$current_code) {
            throw new UpdateException(['errorCode' => '10007', 'msg' => '验证码过期，请重新获取']);
        }
        if ($current_code != $phone . '-' . $code) {
            throw new UpdateException(['errorCode' => '10002', 'msg' => '验证码不正确']);
        }


        $u_id = Token::getCurrentUid();
        $user = UserT::get($u_id);
        if (!empty($user->phone)) {
            throw new UpdateException(['msg' => '用户已经绑定手机号，不能重复绑定']);
        }
        $user->phone = $phone;
        $res = $user->save();
        if (!$res) {
            throw new UpdateException(['msg' => '绑定用户手机号失败']);
        }
        (new OfficialToken())->updatePhone($phone);
        return $this->prefixUserCompany();
    }

    private function prefixUserCompany()
    {
        $companies = (new CompanyService())->userCompanies();
        if (empty($companies)) {
            return [
                'count' => 0
            ];
        }
        if (count($companies) == 1) {
            //直接绑定用户当前企业
            $company_id = $companies[0]['company_id'];
            UserT::update(['current_company_id', $company_id], ['id', Token::getCurrentUid()]);
            return [
                'count' => 1
            ];
        } else {
            return [
                'count' => 2,
                'companies' => $companies
            ];

        }

    }

    public function bindCompany($company_id)
    {
        //获取用户归属企业
        $companies = (new CompanyService())->userCompanies();
        if (!count($companies)) {
            throw new UpdateException(['msg' => '当前用户不属于任何企业']);
        }
        $bind = false;
        foreach ($companies as $k => $v) {
            if ($company_id == $v['company_id']) {
                $res = UserT::update(['current_company_id', $company_id], ['id', Token::getCurrentUid()]);
                if ($res) {
                    $bind = true;
                }
                break;
            }
        }
        if (!$bind) {
            throw  new UpdateException(['msg' => '绑定失败']);
        }
        //更新用户缓存
        Token::updateCurrentTokenVar('current_company_id', $company_id);
    }

    public function getUserCurrentCompanyID()
    {
        $company_id = Token::getCurrentTokenVar('current_company_id');
        if (!empty($company_id)) {
            return $company_id;
        }
        $user = UserT::get(Token::getCurrentUid());
        if (empty($user->current_company_id)) {
            throw new AuthException(['msg' => '该用户无归属企业']);
        }
        Token::updateCurrentTokenVar('current_company_id', $user->current_company_id);
        return $user->current_company_id;
    }

    //检查用户是否为管理员
    public function checkUserAdminID()
    {
        $phone = Token::getCurrentTokenVar('phone');
        if (empty($phone)) {
            throw new AuthException(['msg' => '用户状态异常，未绑定手机号']);
        }
        $admin = AdminT::where('phone', $phone)->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if ($admin) {
            return $admin->id;
        }
        return 0;

    }

    //获取用户电子饭卡
    public function mealCard()
    {
        //$cart = StaffQrcodeT::where('s_id',)


    }

    private function getUserStaffID()
    {
        $phone = Token::getCurrentTokenVar('phone');


    }
}