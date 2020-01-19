<?php


namespace app\api\service;


use app\api\model\AdminCanteenT;
use app\api\model\AdminModuleT;
use app\api\model\CanteenModuleT;
use app\api\model\CanteenModuleV;
use app\api\model\ShopModuleT;
use app\api\model\ShopModuleV;
use app\api\model\ShopT;
use app\api\model\SystemCanteenModuleT;
use app\api\model\SystemModuleT;
use app\api\model\SystemShopModuleT;
use app\lib\enum\AdminEnum;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use function GuzzleHttp\Psr7\str;
use think\Db;
use think\Exception;

class ModuleService
{
    public function saveSystem($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $module = SystemModuleT::create($params);
        if (!$module) {
            throw new SaveException();
        }
    }

    public function saveSystemCanteen($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $module = SystemCanteenModuleT::create($params);
        if (!$module) {
            throw new SaveException();
        }
    }

    public function saveSystemShop($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $module = SystemShopModuleT::create($params);
        if (!$module) {
            throw new SaveException();
        }
    }

    public function handelModule($params)
    {
        $res = SystemCanteenModuleT::update($params);
        if (!$res) {
            throw new UpdateException();
        }
    }

    public function systemModules($tree = 1)
    {
        $modules = SystemCanteenModuleT::order('create_time desc')
            ->select()
            ->toArray();
        if (!$tree) {
            return $modules;
        }
        return getTree($modules);

    }


    public function updateModule($params)
    {

        $res = SystemCanteenModuleT::update($params);
        if (!$res) {
            throw new UpdateException();
        }
    }


    public function adminModules()
    {
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade == AdminEnum::SYSTEM_SUPER) {
            $modules = $this->getSuperModules();
        } else if ($grade == AdminEnum::COMPANY_SUPER) {
            $company_id = Token::getCurrentTokenVar('company_id');
            //$modules = CanteenModuleT::companyModules($company_id);
            $modules = CanteenModuleV::canteenModules($company_id);
        } else if ($grade == AdminEnum::COMPANY_OTHER) {
            $admin_id = Token::getCurrentUid();
            $modules = $this->getAdminModules($admin_id);

        } else {
            throw new AuthException();
        }
        return getTree($modules);


    }

    private function getSuperModules()
    {
        $modules = SystemCanteenModuleT::getSuperModules();
        return $modules;

    }

    public function canteenModulesWithSystem($c_id)
    {
        return $this->canteenModules($c_id);


    }

    private function canteenModules($c_id)
    {
        $modules = CanteenModuleV::modules($c_id);
        $system = $this->systemModules(0);
        $modules = $this->prefixModules($modules, $system);
        $modules = getTree($modules);
        return $modules;

    }


    private function prefixModules($modules, $system)
    {
        foreach ($system as $k => $v) {
            $system[$k]['have'] = CommonEnum::STATE_IS_FAIL;
            foreach ($modules as $k2 => $v2) {
                if ($v2['m_id'] == $v['id']) {
                    $system[$k]['have'] = CommonEnum::STATE_IS_OK;
                    unset($modules[$k2]);
                    continue;
                }
            }
        }

        return $system;

    }

    public function updateCompanyModule($params)
    {
        try {
            $company_id = $params['company_id'];
            $canteen = $params['canteen'];
            if (strlen($canteen)) {
                $this->updateCanteenModule($canteen, $company_id);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;

        }

    }

    private function
    updateCanteenModule($canteen, $company_id)
    {

        $canteen = json_decode($canteen, true);
        if (key_exists('add_modules', $canteen) && count($canteen['add_modules'])) {
            $add_data = [];
            $add_modules = $canteen['add_modules'];
            foreach ($add_modules as $k => $v) {
                $add_data[] = [
                    'c_id' => $company_id,
                    'state' => CommonEnum::STATE_IS_OK,
                    'm_id' => $v['m_id'],
                    'order' => $v['order']
                ];
            }

            $res = (new CanteenModuleT())->saveAll($add_data);
            if (!$res) {
                throw new SaveException(['msg' => '新增企业模块失败']);

            }

        }

        if (key_exists('cancel_modules', $canteen) && strlen($canteen['cancel_modules'])) {
            $cancel_modules = $canteen['cancel_modules'];
            $res = CanteenModuleT::where('c_id', $company_id)
                ->whereIn('m_id', $cancel_modules)
                ->update(['state' => CommonEnum::STATE_IS_FAIL]);
            if (!$res) {
                throw new UpdateException(['msg' => '取消饭堂模块失败']);

            }

        }

    }

    private function updateShopModule($company_id, $shop)
    {
        $shop = json_decode($shop, true);
        if (!key_exists('s_id', $shop)) {
            $shopModel = ShopT::create(['state' => CommonEnum::STATE_IS_OK, 'c_id' => $company_id]);
            $s_id = $shopModel->id;
        } else {
            $s_id = $shop['s_id'];
        }

        if (key_exists('add_modules', $shop) && count($shop['add_modules'])) {
            $add_data = [];
            $add_modules = $shop['add_modules'];
            foreach ($add_modules as $k => $v) {
                $add_data[] = [
                    's_id' => $s_id,
                    'state' => CommonEnum::STATE_IS_OK,
                    'm_id' => $v['m_id'],
                    'order' => $v['order']
                ];
            }

            $res = (new ShopModuleT())->saveAll($add_data);
            if (!$res) {
                throw new SaveException(['msg' => '新增小卖部模块失败']);

            }

        }

        if (key_exists('cancel_modules', $shop) && strlen($shop['cancel_modules'])) {
            $cancel_modules = $shop['cancel_modules'];
            $res = ShopModuleT::where('s_id', $s_id)
                ->whereIn('m_id', $cancel_modules)
                ->update(['state' => CommonEnum::STATE_IS_FAIL]);
            if (!$res) {
                throw new UpdateException(['msg' => '取消小卖部模块失败']);

            }

        }

    }

    public function canteenModulesWithoutSystem($company_id)
    {
        //$company_id = Token::getCurrentTokenVar('company_id');
        $modules = CanteenModuleV::canteenModules($company_id);
        return getTree($modules);
    }

    public function userMobileModules()
    {
        $admin_id = (new UserService())->checkUserAdminID();
        //当前用户为管理员
        if ($admin_id) {
            $modules = $this->getAdminMobileModules($admin_id);
            return $modules;
        }
        //当前用户为普通用户
        $company_id = (new UserService())->getUserCurrentCompanyID();
        $modules = $this->companyNormalMobileModules($company_id);
        return $modules;
    }

    private function getAdminMobileModules($admin_id)
    {
        $adminModules = AdminModuleT::where('admin_id', $admin_id)->find();
        $rules = $adminModules->rules;
        $modules = CanteenModuleV::mobileModulesWithID($rules);
        return $modules;

    }

    public function getAdminModules($admin_id)
    {
        $adminModules = AdminModuleT::where('admin_id', $admin_id)->find();
        $rules = $adminModules->rules;
        $modules = CanteenModuleV::adminModulesWithID($rules);
        return $modules;

    }

    private function companyNormalMobileModules($company_id)
    {
        $modules = CanteenModuleV::companyNormalMobileModules($company_id);
        return $modules;
    }

    public function handelModuleDefaultStatus($params)
    {
        $modules = $params['modules'];
        $modules = json_decode($modules, true);
        if (empty($modules)) {
            throw new ParameterException();
        }
        $res = (new SystemCanteenModuleT())->saveAll($modules);
        if (!$res) {
            throw new UpdateException();
        }

    }

    public function getModuleCompanyID($company_id, $module_id)
    {
        $module = CanteenModuleT::where('c_id', $company_id)
            ->where('m_id', $module_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if (empty($module)) {
            throw  new ParameterException(['msg' => '模块状态异常']);
        }
        return $module->id;

    }
}