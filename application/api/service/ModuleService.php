<?php


namespace app\api\service;


use app\api\model\AdminCanteenT;
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
        $type = $params['type'];
        $res = null;
        if ($type == ModuleEnum::CANTEEN) {
            $res = SystemCanteenModuleT::update($params);
        } elseif ($type == ModuleEnum::SYSTEM) {
            $res = SystemModuleT::update($params);
        } elseif ($type == ModuleEnum::SHOP) {
            $res = SystemShopModuleT::update($params);
        }
        if (!$res) {
            throw new UpdateException();
        }
    }

    public function systemModules($type, $tree = 1)
    {
        $modules = array();
        if ($type == ModuleEnum::CANTEEN) {
            $modules = SystemCanteenModuleT::where('state', CommonEnum::STATE_IS_OK)
                ->hidden(['update_time'])
                ->order('create_time desc')
                ->select()
                ->toArray();
        } elseif ($type == ModuleEnum::SYSTEM) {
            $modules = SystemModuleT::where('state', CommonEnum::STATE_IS_OK)
                ->hidden(['update_time'])
                ->order('create_time desc')
                ->select()
                ->toArray();
        } elseif ($type == ModuleEnum::SHOP) {
            $modules = SystemShopModuleT::where('state', CommonEnum::STATE_IS_OK)
                ->hidden(['update_time'])
                ->order('create_time desc')
                ->select()
                ->toArray();
        }
        if (!$tree) {
            return $modules;
        }
        return getTree($modules);

    }


    public function updateModule($params)
    {

        $type = $params['type'];
        $res = null;
        if ($type == ModuleEnum::CANTEEN) {
            $res = SystemCanteenModuleT::update($params);
        } elseif ($type == ModuleEnum::SYSTEM) {
            $res = SystemModuleT::update($params);
        } elseif ($type == ModuleEnum::SHOP) {
            $res = SystemShopModuleT::update($params);
        }
        if (!$res) {
            throw new UpdateException();
        }
    }


    public function adminModules()
    {
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade == AdminEnum::SYSTEM_SUPER) {
            $modules = $this->getSuperModules();
            return getTree($modules);
        }
        return array();


    }

    private function getSuperModules()
    {
        $modules = SystemModuleT::getSuperModules();
        return $modules;

    }

    public function canteenModulesWithSystem($c_id)
    {
        return $this->canteenModules($c_id);
        /* return [
             'canteen' => $this->canteenModules($c_id),
             'shop' => $this->shopModules($c_id)
         ];*/


    }

    public function shopModulesWithSystem($s_id)
    {
        return $this->shopModules($s_id);
        /* return [
             'canteen' => $this->canteenModules($c_id),
             'shop' => $this->shopModules($c_id)
         ];*/


    }

    private function canteenModules($c_id)
    {
        $modules = CanteenModuleV::modules($c_id);
        $system = $this->systemModules(ModuleEnum::CANTEEN, 0);
        $modules = $this->prefixModules($modules, $system);
        $modules = getTree($modules);
        return $modules;

    }


    private function shopModules($s_id)
    {
        $modules = ShopModuleV::modules($s_id);
        $system = $this->systemModules(ModuleEnum::SHOP, 0);
        $modules = $this->prefixModules($modules, $system);
        $modules = getTree($modules);
        return $modules;
    }

    private function prefixModules($modules, $system)
    {
        foreach ($system as $k => $v) {
            $system[$k]['have'] = CommonEnum::STATE_IS_FAIL;
            foreach ($modules as $k2 => $v2) {
                if ($v2['id'] == $v['id']) {
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
            $canteen = $params['canteen'];
            $shop = $params['shop'];
            $company_id = $params['company_id'];
            if (strlen($canteen)) {
                $this->updateCanteenModule($canteen);
            }
            if (strlen($shop)) {
                $this->updateShopModule($company_id, $shop);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;

        }

    }

    private function updateCanteenModule($canteen)
    {

        $canteen = json_decode($canteen, true);
        if (!key_exists('c_id', $canteen)) {
            throw  new ParameterException();
        }
        $c_id = $canteen['c_id'];
        if (key_exists('add_modules', $canteen) && count($canteen['add_modules'])) {
            $add_data = [];
            $add_modules = $canteen['add_modules'];
            foreach ($add_modules as $k => $v) {
                $add_data[] = [
                    'c_id' => $c_id,
                    'state' => CommonEnum::STATE_IS_OK,
                    'm_id' => $v['m_id'],
                    'order' => $v['order']
                ];
            }

            $res = (new CanteenModuleT())->saveAll($add_data);
            if (!$res) {
                throw new SaveException(['msg' => '新增饭堂模块失败']);

            }

        }

        if (key_exists('cancel_modules', $canteen) && strlen($canteen['cancel_modules'])) {
            $cancel_modules = $canteen['cancel_modules'];
            $res = CanteenModuleT::where('c_id', $c_id)
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

    public function canteenModulesWithoutSystem($c_id)
    {
        $modules = CanteenModuleV::canteenModules($c_id);
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
        $adminModules = AdminCanteenT::where('admin_id', $admin_id)->find();
        $rules = $adminModules->rules;
        $modules = CanteenModuleV::mobileModulesWithID($rules);
        return $modules;

    }

    private function companyNormalMobileModules($company_id)
    {

        $modules = CanteenModuleV::companyNormalMobileModules($company_id);
        return $modules;
    }
}