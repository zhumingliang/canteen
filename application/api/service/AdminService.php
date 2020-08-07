<?php


namespace app\api\service;


use app\api\model\AdminCanteenT;
use app\api\model\AdminModuleT;
use app\api\model\AdminShopT;
use app\api\model\AdminT;
use app\api\model\CanteenModuleV;
use app\api\model\CompanyStaffT;
use app\api\model\StaffTypeT;
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
            //检测角色账号是否重复
            $check = AdminT::check( $params['account']);
            if ($check) {
                throw new SaveException(['msg' => '账号：' . $params['account'] . '已经存在']);

            }
            $canteens = [];
            if (!empty($params['canteens'])) {
                $canteens = json_decode($params['canteens'], true);
            }
            //新增账户信息
            $params['parent_id'] = Token::getCurrentUid();
            $params['passwd'] = sha1($params['passwd']);
            $admin = AdminT::create($params);
            $admin_id = $admin->id;
            //新增账户可见模块信息
            $res = AdminModuleT::create(['admin_id' => $admin_id, 'rules' => $params['rules']]);
            if (!$res) {
                throw new SaveException();
            }
            //新增角色-饭堂关联
            $this->saveAdminCS($admin_id, $canteens, 'canteen');
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
        if (!empty($data['add'])) {
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

        if (!empty($data['cancel'])) {
            $ids = explode(',', $data['cancel']);
            $list = [];
            foreach ($ids as $k => $v) {
                array_push($list, [
                    'id' => $v,
                    'state' => CommonEnum::STATE_IS_FAIL
                ]);

            }
            if ($type == "canteen") {
                $res = (new AdminCanteenT())->saveAll($list);

            } else {
                $res = (new AdminShopT())->saveAll($list);
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
            if (!empty($params['canteens'])) {
                $canteens = json_decode($params['canteens'], true);
                //修改角色-饭堂关联
                $this->updateAdminCS($admin_id, $canteens, 'canteen');
            }
            if (!empty($params['shops'])) {
                $shops = json_decode($params['shops'], true);
                //修改角色-小卖部关联
                $this->updateAdminCS($admin_id, $shops, 'shop');
            }

            //修改账户信息
            if (!empty($params['passwd'])) {
                $params['passwd'] = sha1($params['passwd']);
            }
            $admin_res = AdminT::update($params);
            if (!$admin_res) {
                throw new UpdateException();
            }

            //修改账户可见模块信息
            if (!empty($params['rules'])) {
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

    public function save($account, $passwd, $role, $grade, $c_id, $remark, $company = '')
    {

        $data = [
            'account' => $account,
            'passwd' => sha1($passwd),
            'role' => $role,
            'grade' => $grade,
            'state' => CommonEnum::STATE_IS_OK,
            'c_id' => $c_id,
            'remark' => $remark,
            'company' => $company
        ];

        $admin = AdminT::create($data);
        if (!$admin) {
            throw new SaveException();
        }

        return $admin->id;
    }

    public function roles($page, $size, $state, $key, $c_name)
    {
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade == AdminEnum::SYSTEM_SUPER) {
            $roles = AdminT::roles($page, $size, $state, $key, $c_name);
        } else {
            $company_ids = (new  CompanyService())->getUserCompaniesWithOutSystemManager($grade);
            $roles = AdminT::rolesWithIds($page, $size, $state, $key, $c_name, $company_ids);
        }
        return $roles;
    }


    public function handel($params)
    {
        /*  if ($params['state'] == CommonEnum::STATE_IS_OK) {
              if (Token::getCurrentTokenVar('grade') != AdminEnum::COMPANY_SUPER) {
                  throw new AuthException();
              }
          }*/
        $res = AdminT::update(['state' => $params['state']], ['id' => $params['id']]);
        if (!$res) {
            throw  new UpdateException();
        }
    }

    public function roleTypes($page, $size, $key)
    {
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade == AdminEnum::SYSTEM_SUPER) {
            $types = StaffTypeT::roleTypes($page, $size, $key);
            return $types;
        } else {
            $allTypes = StaffTypeT::allTypes();
            $company_id = Token::getCurrentTokenVar('company_id');
            $companyTypes = CompanyStaffT::staffsType($company_id, $page, $size);
            $data = $companyTypes['data'];
            foreach ($data as $k => $v) {
                foreach ($allTypes as $k2 => $v2) {
                    if ($v['id'] == $v2['id']) {
                        $data[$k]['name'] = $v2['name'];
                    }
                }
            }
            $companyTypes['data'] = $data;
            return $companyTypes;
        }

    }

    public function allTypes()
    {
        $types = StaffTypeT::allTypes();
        return $types;
    }

    public function updatePasswd($params)
    {
        $old_passwd = $params['oldPasswd'];
        $new_passwd = $params['newPasswd'];
        $id = Token::getCurrentUid();
        $admin = AdminT::get($id);
        if (sha1($old_passwd) != $admin->passwd) {
            throw new UpdateException(['msg' => '密码不正确']);
        }
        $admin->passwd = sha1($new_passwd);
        $res = $admin->save();
        if (!$res) {
            throw new UpdateException();
        }
    }

    public function rechargeAdmins($module_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        //获取模块对应企业中关联id
        $module_company_id = (new ModuleService())->getModuleCompanyID($company_id, $module_id);
        //获取该企业下所有管理员
        $admins = AdminT::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with(['rule'])
            ->field('id,role')
            ->select()->toArray();
        if (empty($admins)) {
            return $admins;
        }
        $admin = [];
        foreach ($admins as $k => $v) {
            if (!empty($v['rule']) && !empty($v['rule']['rules'])) {
                $rules = explode(',', $v['rule']['rules']);
                if (in_array($module_company_id, $rules)) {
                    array_push($admin, [
                        'id' => $v['id'],
                        'role' => $v['role']
                    ]);

                }

            }

        }

        return $admin;
    }

    public function role($id)
    {
        $role = AdminT::admin($id);
        $adminModules = AdminModuleT::where('admin_id', $role['id'])
            ->field('rules')->find();
        //获取企业所有模块
        $modules = CanteenModuleV::modules($role['c_id']);
        $adminModulesArr = explode(',', $adminModules['rules']);
        foreach ($modules as $k => $v) {
            $modules[$k]['have'] = CommonEnum::STATE_IS_FAIL;
            if (in_array($v['c_m_id'], $adminModulesArr)) {
                $modules[$k]['have'] = CommonEnum::STATE_IS_OK;
            }

        }
        $role['modules'] = getTree($modules);
        return $role;
    }

    public function saveStaffType($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $staffType = StaffTypeT::where('name', $params['name'])
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count('id');
        if ($staffType) {
            throw new SaveException(['msg' => '人员类型已经存在，不能重复添加']);
        }
        $type = StaffTypeT::create($params);
        if (!$type) {
            throw new SaveException(['msg' => '保存人员类型失败']);
        }
        return $type->id;
    }

}