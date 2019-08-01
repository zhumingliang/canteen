<?php


namespace app\api\service;


use app\api\model\AdminCanteenT;
use app\api\model\AdminModuleT;
use app\api\model\AdminShopT;
use app\api\model\AdminT;
use app\lib\enum\AdminEnum;
use app\lib\enum\CommonEnum;
use app\lib\exception\AuthException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;


class AdminService
{

    public function saveRole($params)
    {
        try {
            Db::startTrans();
            $canteens = [];
            $shops = [];
            if (key_exists('canteens', $params) && strlen($params['canteens'])) {
                $canteens = json_decode($params['canteens'], true);
            }
            if (key_exists('shops', $params) && strlen($params['shops'])) {

                $shops = json_decode($params['shops'], true);
            }
            //新增账户信息
            $params['parent_id'] = Token::getCurrentUid();
            $admin = AdminT::create($params);
            $admin_id = $admin->id;
            //新增账户可见模块信息
            $res = AdminModuleT::create(['admin_id' => $admin_id, 'rules' => $params['rules']]);
            if (!$res) {
                throw new SaveException();
            }
            //新增角色-饭堂关联
            $this->saveAdminCS($admin_id, $canteens, 'canteen');
            //新增角色-小卖部关联
            $this->saveAdminCS($admin_id, $shops, 'shop');
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;

        }

    }

    public function saveAdminCS($admin_id, $data, $type)
    {
        if (!count($data)) {
            return true;
        }
        foreach ($data as $k => $v) {
            $data[$k]['state'] = CommonEnum::STATE_IS_OK;
            $data[$k]['admin_id'] = $admin_id;
        }
        if ($type == "canteen") {
            $res = (new AdminCanteenT())->saveAll($data);

        } else {
            $res = (new AdminShopT())->saveAll($data);
        }
        if (!$res) {
            throw new SaveException();
        }
        return true;

    }

    public function updateAdminCS($admin_id, $data, $type)
    {
        if (key_exists('add', $data) && count($data['add'])) {
            $add = $data['add'];
            foreach ($add as $k => $v) {
                $add[$k]['state'] = CommonEnum::STATE_IS_OK;
                $add[$k]['admin_id'] = $admin_id;
            }

            if ($type == "canteen") {
                $res = (new AdminCanteenT())->saveAll($add);

            } else {
                $res = (new AdminShopT())->saveAll($add);
            }
            if (!$res) {
                throw new SaveException();
            }

        }

        if (key_exists('cancel', $data) && strlen($data['cancel'])) {
            if ($type == "canteen") {
                $res = AdminCanteenT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => [
                    'in', $data['cancel']]]);

            } else {
                $res = AdminShopT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => [
                    'in', $data['cancel']]]);
            }
            if (!$res) {
                throw new SaveException();
            }

        }

        return true;

    }

    public function updateRole($params)
    {
        try {
            Db::startTrans();
            $admin_id = $params['id'];
            if (key_exists('canteens', $params) && strlen($params['canteens'])) {
                $canteens = json_decode($params['canteens'], true);
                //修改角色-饭堂关联
                $this->updateAdminCS($admin_id, $canteens, 'canteen');
            }
            if (key_exists('shops', $params) && strlen($params['shops'])) {
                $shops = json_decode($params['shops'], true);
                //修改角色-小卖部关联
                $this->updateAdminCS($admin_id, $shops, 'shop');
            }

            //修改账户信息
            if (key_exists('passwd', $params)) {
                $params['passwd'] = sha1($params['passwd']);
            }
            $admin_res = AdminT::update($params);
            if (!$admin_res) {
                throw new UpdateException();
            }

            //修改账户可见模块信息
            if (key_exists('rules', $params) && strlen($params['rules'])) {
                $adminModule = AdminModuleT::where('admin_id', $admin_id)->find();
                if (!$adminModule) {
                    $res = AdminModuleT::create(['admin_id' => $admin_id, 'rules' => $params['rules']]);
                } else {
                    if ($params['rules'] != $adminModule->rules) {
                        $adminModule->rules = $params['rules'];
                        $res = $adminModule->save();
                    } else {
                        $res = true;
                    }

                }
                if (!$res) {
                    throw new SaveException();
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;

        }

    }

    public function save($account, $passwd, $role, $grade, $c_id, $remark)
    {

        $data = [
            'account' => $account,
            'passwd' => sha1($passwd),
            'role' => $role,
            'grade' => $grade,
            'state' => CommonEnum::STATE_IS_OK,
            'c_id' => $c_id,
            'remark' => $remark
        ];

        $admin = AdminT::create($data);
        if (!$admin) {
            throw new SaveException();
        }

        return $admin->id;
    }

    public function roles($page, $size, $state, $key, $c_name)
    {
        $roles = AdminT::roles($page, $size, $state, $key, $c_name);
        return $roles;
    }

    public function handel($params)
    {
        if ($params['state'] = CommonEnum::STATE_IS_OK) {
            if (Token::getCurrentTokenVar('grade') != AdminEnum::COMPANY_SUPER) {
                throw new AuthException();
            }
        }
        $res = AdminT::update($params);
        if (!$res) {
            throw  new UpdateException();
        }


    }


}