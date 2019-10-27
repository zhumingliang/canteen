<?php


namespace app\api\service;


use app\api\model\AdminCanteenT;
use app\api\model\AdminCanteenV;
use app\api\model\CanteenAccountT;
use app\api\model\CanteenCommentT;
use app\api\model\CanteenModuleT;
use app\api\model\CanteenT;
use app\api\model\CompanyStaffT;
use app\api\model\CompanyStaffV;
use app\api\model\CompanyT;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\model\MachineT;
use app\api\model\MenuT;
use app\api\model\StaffCanteenV;
use app\api\model\StaffV;
use app\api\model\SystemCanteenModuleT;
use app\lib\enum\AdminEnum;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\exception\AuthException;
use app\lib\exception\DeleteException;
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
            $c_id = $params['c_id'];
            $canteens = $params['canteens'];
            /*  Db::startTrans();
              $canteens = $params['canteens'];
              $canteens = json_decode($canteens, true);
              $c_id = $params['c_id'];
              foreach ($canteens as $K => $v) {
                  $id = $this->saveDefault($c_id, $v);
                  if (!$id) {
                      throw new SaveException();
                      break;
                  }
              }*/
            $id = $this->saveDefault($c_id, $canteens);
            Db::commit();
            return $id;
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
            // Db::commit();
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
        if (!empty($account)) {
            $account['state'] = CommonEnum::STATE_IS_OK;
            $account['c_id'] = $c_id;
            $res = CanteenAccountT::create($account);
            if (!$res) {
                throw new SaveException();
            }
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
            if (!empty($params['dinners'])) {
                $dinners = $params['dinners'];
                $dinners = json_decode($dinners, true);
                foreach ($dinners as $k => $v) {
                    if (!key_exists('id', $v)) {
                        $dinners[$k]['c_id'] = $c_id;
                        $dinners[$k]['state'] = CommonEnum::STATE_IS_OK;
                    }

                }
                if (count($dinners)) {
                    $res = (new DinnerT())->saveAll($dinners);
                    if (!$res) {
                        throw new SaveException();
                    }
                }

            }
            if (!empty($params['account'])) {
                $account = json_decode($params['account'], true);

                if (!key_exists('id', $account)) {
                    $account['c_id'] = $c_id;
                    $res = CanteenAccountT::create($account);
                } else {
                    $res = CanteenAccountT::update($account);
                }
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

    public function getStaffConsumptionStrategy($c_id, $d_id, $t_id)
    {
        $info = ConsumptionStrategyT::getStaffConsumptionStrategy($c_id, $d_id, $t_id);
        return $info;
    }

    public function getDinnerConsumptionStrategyForNoMeals($c_id, $d_id)
    {
        $info = ConsumptionStrategyT::getDinnerConsumptionStrategy($c_id, $d_id);
        if ($info->isEmpty()) {
            throw new ParameterException(['msg' => '餐次未设置消费策略']);
        }
        $dataList = [];
        foreach ($info as $k => $v) {
            $data['t_id'] = $v['t_id'];
            $detail = json_decode($v['detail'], true);
            foreach ($detail as $k2 => $v2) {
                if ($v2['status'] == 'ordering_meals') {
                    $detail['sub_money'] = $v2['sub_money'];
                }

            }
        }
        return $data;
    }

    public function getDinners($c_id)
    {
        return DinnerT::dinners($c_id);

    }


    public function getDinnerNames($c_id)
    {
        return DinnerT::dinnerNames($c_id);

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
    public function adminCanteens()
    {
        $admin_id = (new UserService())->checkUserAdminID();
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
        $canteens = CompanyStaffT::getStaffCanteens($phone);
        return $canteens;
    }

    public function saveComment($params)
    {
        $params['u_id'] = Token::getCurrentUid();
        $params['c_id'] = Token::getCurrentTokenVar('current_canteen_id');
        $comment = CanteenCommentT::create($params);
        if (!$comment) {
            throw  new SaveException();
        }
    }

    //获取饭堂评分
    public function canteenScore($canteen_id)
    {
        $taste = CanteenCommentT::where('c_id', $canteen_id)->avg('taste');
        $service = CanteenCommentT::where('c_id', $canteen_id)->avg('service');
        return [
            'taste' => round($taste, 1),
            'service' => round($service, 1),
        ];
    }

    public function staffStrategy($c_id, $t_id)
    {
        $info = ConsumptionStrategyT::staffStrategy($c_id, $t_id);
        return $info;
    }

    public function getCanteenBelongCompanyID($canteen_id)
    {
        $canteen = CanteenT::canteen($canteen_id);
        if (empty($canteen)) {
            throw new ParameterException(['msg' => '参数错误，饭堂不存在']);
        }
        return $canteen->c_id;
    }

    public function saveMachine($params)
    {
        if (!empty($params['pwd'])) {
            $params['pwd'] = sha1($params['pwd']);
        }
        $params['company_id'] = Token::getCurrentTokenVar('company_id');
        $machine = MachineT::create($params);
        if (!$machine) {
            throw new SaveException();
        }
    }

    public function updateMachine($params)
    {
        if (!empty($params['pwd'])) {
            $params['pwd'] = sha1($params['pwd']);
        }
        $machine = MachineT::update($params);
        if (!$machine) {
            throw new UpdateException();
        }
    }

    /**
     * 获取企业下所有饭堂信息和饭堂模块信息/设备信息
     */
    public function getCanteensForCompany($company_id)
    {
        $staffs = (new DepartmentService())->getCompanyStaffCounts($company_id);
        $canteens = CanteenT::getCanteensForCompany($company_id);
        return [
            'staffs' => $staffs,
            'canteens' => $canteens
        ];

    }

    private function prefixMachinesState($machines)
    {
        if (count($machines)) {
            foreach ($machines as $k => $v) {
                $machines[$k]['state'] = $this->checkMachineState($v['id']);
            }
        }
        return $machines;
    }

    private function checkMachineState($machine_id)
    {
        return 1;

    }

    public function deleteMachine($machine_id)
    {
        $machine = MachineT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $machine_id]);
        if (!$machine) {
            throw  new DeleteException();
        }
    }

    public function managerCanteens()
    {
        $admin_id = Token::getCurrentUid();
        $canteens = AdminCanteenV::managerCanteens($admin_id);
        return $canteens;
    }

    public function diningMode()
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $account = CanteenAccountT::where('c_id', $canteen_id)
            ->field('dining_mode')
            ->find();
        if (empty($account)) {
            throw new ParameterException(['msg' => '未设置饭堂账户信息']);
        }
        return $account;
    }

    public function machines($belong_id, $machine_type, $page, $size)
    {
        $machines = MachineT::machines($page, $size, $belong_id, $machine_type);

        $data = $machines['data'];
        $machines['data'] = $this->prefixMachinesState($data);
        return $machines;
    }

    public function companyMachines($company_id, $page, $size)
    {
        $machines = MachineT::companyMachines($company_id, $page, $size);
        $data = $machines['data'];
        $machines['data'] = $this->prefixMachinesState($data);
        return $machines;
    }

}