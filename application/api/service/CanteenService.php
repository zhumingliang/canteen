<?php


namespace app\api\service;


use app\api\model\AdminCanteenT;
use app\api\model\AdminCanteenV;
use app\api\model\CanteenAccountT;
use app\api\model\CanteenModuleT;
use app\api\model\CanteenT;
use app\api\model\CompanyStaffV;
use app\api\model\CompanyT;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\model\MenuT;
use app\api\model\StaffV;
use app\api\model\SystemCanteenModuleT;
use app\lib\enum\AdminEnum;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;

class CanteenService
{

    public function save($params)
    {
        try {
            Db::startTrans();
            $canteens = $params['canteens'];
            $canteens = json_decode($canteens, true);
            $c_id = $params['c_id'];
            foreach ($canteens as $K => $v) {
                $id = $this->saveDefault($c_id, $v);
                if (!$id) {
                    Db::rollback();
                    throw new SaveException();
                    break;
                }
            }
            Db::commit();
        } catch (Exception$e) {
            Db::rollback();
            throw  $e;
        }

    }

    public function saveDefault($company_id, $name)
    {
        $data = [
            'c_id' => $company_id,
            'name' => $name,
            'state' => CommonEnum::STATE_IS_OK
        ];
        $canteen = CanteenT::create($data);
        if (!$canteen) {
            throw new SaveException();
        }
        //新增饭堂默认功能模块
        $this->saveDefaultCanteen($canteen->id);
        return $canteen->id;

    }

    private function saveDefaultCanteen($c_id)
    {
        $modules = SystemCanteenModuleT::defaultModules();
        $data = array();
        if (count($modules)) {
            $pc_order = $mobile_order = 1;
            foreach ($modules as $k => $v) {
                if ($v->type == ModuleEnum::MOBILE) {
                    $order = $mobile_order;
                    $mobile_order++;
                } else {
                    $order = $pc_order;
                    $pc_order++;
                }

                $data[] = [
                    'c_id' => $c_id,
                    'state' => CommonEnum::STATE_IS_OK,
                    'm_id' => $v->id,
                    'type' => $v->type,
                    'category' => CommonEnum::STATE_IS_OK,
                    'order' => $order
                ];


            }
            if (!count($data)) {
                return true;
            }
            $res = (new CanteenModuleT())->saveAll($data);
            if (!$res) {
                throw new SaveException();
            }

        }


    }

    public function saveConfiguration($params)
    {
        try {
            $c_id = $params['c_id'];
            $dinners = json_decode($params['dinners'], true);
            $account = json_decode($params['account'], true);
            $this->prefixDinner($c_id, $dinners);
            $this->prefixCanteenAccount($c_id, $account);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;

        }
    }

    private function prefixDinner($c_id, $dinners)
    {
        foreach ($dinners as $k => $v) {
            $dinners[$k]['c_id'] = $c_id;
            $dinners[$k]['state'] = CommonEnum::STATE_IS_OK;
        }
        $res = (new DinnerT())->saveAll($dinners);
        if (!$res) {
            throw new SaveException();
        }

    }

    private function prefixCanteenAccount($c_id, $account)
    {
        $account['state'] = CommonEnum::STATE_IS_OK;
        $account['c_id'] = $c_id;
        $res = CanteenAccountT::create($c_id);
        if (!$res) {
            throw new SaveException();
        }
    }

    public function configuration($c_id)
    {
        return [
            'dinners' => DinnerT::dinners($c_id),
            'account' => CanteenAccountT::account($c_id)
        ];

    }

    public function updateConfiguration($params)
    {
        try {
            $c_id = $params['c_id'];
            if (key_exists('dinners', $params) && strlen($params['dinners'])) {
                $dinners = $params['dinners'];
                $dinners = json_decode($dinners, true);
                foreach ($dinners as $k => $v) {
                    if (!key_exists('id', $v)) {
                        $dinners[$k]['c_id'] = $c_id;
                        $dinners[$k]['state'] = CommonEnum::STATE_IS_OK;
                        $add_data[] = $dinners[$k];
                    }

                }
                if (count($dinners)) {
                    $res = (new DinnerT())->saveAll($dinners);
                    if (!$res) {
                        throw new SaveException();
                    }
                }

            }
            if (key_exists('account', $params) && strlen($params['account'])) {
                $account = json_decode($params['account'], true);

                if (!key_exists('id', $account)) {
                    throw new ParameterException();
                }
                $res = CanteenAccountT::update($account);
                if (!$res) {
                    throw new UpdateException();
                }
            }
        } catch (Exception$e) {
            Db::rollback();
            throw  $e;
        }
    }

    public function companyCanteens($company_id)
    {
        $canteens = CanteenT::where('c_id', $company_id)
            ->field('id,name')->select()->toArray();
        return $canteens;
    }

    public function saveConsumptionStrategy($params)
    {
        $c_id = $params['c_id'];
        //获取饭堂餐次
        $dinners = $this->getDinners($c_id);
        if (!count($dinners)) {
            throw new SaveException(['msg' => '新增消费策略失败，该饭堂没有设置餐次']);
        }
        $data = array();
        foreach ($dinners as $k => $v) {
            $data[] = [
                'c_id' => $c_id,
                't_id' => $params['t_id'],
                'd_id' => $v['id'],
                'unordered_meals' => $params['unordered_meals'],
                'consumption_count' => $params['consumption_count'],
                'ordered_count' => $params['ordered_count'],
                'state' => CommonEnum::STATE_IS_OK
            ];
        }
        $strategies = (new ConsumptionStrategyT())->saveAll($data);
        if (!$strategies) {
            throw  new SaveException();
        }
        return $this->consumptionStrategy($c_id);
    }

    public function consumptionStrategy($c_id)
    {
        $info = ConsumptionStrategyT::info($c_id);
        return $info;
    }

    private function getDinners($c_id)
    {
        return DinnerT::dinners($c_id);

    }

    public function roleCanteens()
    {
        $grade = Token::getCurrentTokenVar('grade');
        $admin_id = Token::getCurrentUid();
        if ($grade == AdminEnum::COMPANY_SUPER) {
            $c_id = Token::getCurrentTokenVar('c_id');
            if (!$c_id) {
                return null;
            }
            return $this->superCompanyMangerCanteens($c_id);

        } else {
            $canteens = $this->companyManagerCanteens($admin_id);
            return $canteens;
        }

    }

    private function superCompanyMangerCanteens($c_id)
    {

        $canteens = (new CompanyService())->superManagerCompanies($c_id);
        return $canteens;

    }

    private function companyManagerCanteens($admin_id)
    {
        $canteens = AdminCanteenV::companyRoleCanteens($admin_id);
        if (empty($canteens)) {
            return $canteens;
        }
        foreach ($canteens as $k => $v) {
            $ids = $v['canteen_ids'];
            $names = $v['canteen_names'];
            unset($canteens[$k]['canteen_ids'], $canteens [$k]['canteen_names']);
            $ids = explode(',', $ids);
            $names = explode(',', $names);
            $data = [];
            foreach ($ids as $k2 => $v2) {
                $data = [
                    'id' => $v2,
                    'name' => $names[$k2],
                ];
            }
            $canteens[$k]['canteen'] = $data;
        }

        return $canteens;
    }


    //获取管理员可管理的饭堂和对应饭堂的菜单信息
    public function  adminCanteens()
    {
        $admin_id = 7;//(new UserService())->checkUserAdminID();
        if (empty($admin_id)) {
            throw  new AuthException();
        }
        //获取归属饭堂
        $canteens = AdminCanteenT::where('admin_id', $admin_id)
            ->field('c_id,name')
            ->select();
        if (!count($canteens)) {
            throw  new AuthException(['msg' => '用户没有可管理饭堂']);
        }

        foreach ($canteens as $k => $v) {
            $canteens[$k]['dinners'] = DinnerT::dinnerMenusForFoodManager($v['c_id']);
        }
        return $canteens;
    }



    //获取当前用户归属饭堂和企业信息
    public function userCanteens()
    {
        $phone = Token::getCurrentTokenVar('phone');
        //获取用户所有饭堂
        $canteens = StaffV::getStaffCanteens($phone);
        return $canteens;
    }


}