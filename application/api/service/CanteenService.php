<?php


namespace app\api\service;


use app\api\model\AdminCanteenT;
use app\api\model\AdminCanteenV;
use app\api\model\CanteenAccountT;
use app\api\model\CanteenAddressT;
use app\api\model\CanteenCommentT;
use app\api\model\CanteenModuleT;
use app\api\model\CanteenT;
use app\api\model\CompanyStaffT;
use app\api\model\CompanyStaffV;
use app\api\model\CompanyT;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\model\MachineReminderT;
use app\api\model\MachineT;
use app\api\model\MenuT;
use app\api\model\OutConfigT;
use app\api\model\OutConfigV;
use app\api\model\OutsiderCompanyT;
use app\api\model\PrinterT;
use app\api\model\ReceptionConfigT;
use app\api\model\ShopT;
use app\api\model\StaffCanteenV;
use app\api\model\StaffV;
use app\api\model\StrategyDetailT;
use app\api\model\SystemCanteenModuleT;
use app\lib\enum\AdminEnum;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\enum\UserEnum;
use app\lib\exception\AuthException;
use app\lib\exception\DeleteException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use GatewayClient\Gateway;
use think\Db;
use think\Exception;
use think\Model;
use function Sodium\add;

class CanteenService
{

    public function save($params)
    {
        try {
            Db::startTrans();
            $c_id = $params['c_id'];
            $canteens = preg_replace('# #', '', $params['canteens']);
            $this->checkCanteenExit($c_id, $canteens);
            //新增饭堂默认功能模块
            $id = $this->saveDefault($c_id, $canteens);
            Db::commit();
            return $id;
        } catch (Exception$e) {
            Db::rollback();
            throw  $e;
        }

    }

    public function checkCanteenExit($company_id, $name)
    {
        $canteen = CanteenT::where('c_id', $company_id)
            ->where('name', $name)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count('id');
        if ($canteen) {
            throw new SaveException(['msg' => '企业已经存在饭堂：' . $name]);
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
        return $canteen->id;

    }

    public function saveDefaultCanteen($c_id)
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
            if (!empty($params['dinners'])) {
                $dinners = json_decode($params['dinners'], true);
                $this->prefixDinner($c_id, $dinners);
            }

            if (!empty($params['account'])) {
                $account = json_decode($params['account'], true);
                $account['c_id'] = $c_id;
                $this->prefixCanteenAccount($account);
            }
            if (!empty($params['out_config'])) {
                $out_config = json_decode($params['out_config'], true);
                $out_config['canteen_id'] = $c_id;
                $this->prefixOutConfig($out_config);
            }
            if (!empty($params['address'])) {
                $address = json_decode($params['address'], true);
                $this->prefixCanteenAddress($c_id, $address, []);
            }
            if (!empty($params['reception_config'])) {
                $reception = json_decode($params['reception_config'], true);
                $this->prefixReception($reception, $c_id);
            }


            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;

        }
    }


    private function prefixReception($reception, $canteen_id)
    {
        $reception['canteen_id'] = $canteen_id;
        $reception['state'] = CommonEnum::STATE_IS_OK;
        $reception = ReceptionConfigT::create($reception);
        if (!$reception) {
            throw  new ParameterException(['msg' => "接待票参数异常"]);
        }

    }

    private function prefixOutConfig($out_config)
    {
        if (key_exists('id', $out_config)) {
            $outConfig = OutConfigT::update($out_config);
        } else {
            $outConfig = OutConfigT::create($out_config);
        }
        if (!$outConfig) {
            throw new SaveException(['msg' => '处理外送配置失败']);
        }

    }

    private function prefixReceptionConfig($reception_config, $canteen_id)
    {
        if (key_exists('id', $reception_config)) {
            $outConfig = ReceptionConfigT::update($reception_config);
        } else {
            $reception_config['canteen_id'] = $canteen_id;
            $outConfig = ReceptionConfigT::create($reception_config);
        }
        if (!$outConfig) {
            throw new SaveException(['msg' => '处理接待票配置失败']);
        }

    }


    private function prefixCanteenAddress($canteen_id, $address, $cancel)
    {

        $dataList = [];
        if (count($address)) {
            foreach ($address as $k => $v) {
                $v['canteen_id'] = $canteen_id;
                $v['state'] = CommonEnum::STATE_IS_OK;
                $dataList[] = $v;
            }
        }
        if (count($cancel)) {
            foreach ($cancel as $k => $v) {
                $dataList[] = [
                    'id' => $v,
                    'state' => CommonEnum::STATE_IS_FAIL
                ];
            }
        }

        $address = (new CanteenAddressT())->saveAll($dataList);
        if (!$address) {
            throw new SaveException(['msg' => '保存外送限制地址失败']);
        }
    }

    private function prefixDinner($c_id, $dinners)
    {
        //检测该饭堂有没有设置消费策略
        foreach ($dinners as $k => $v) {
            $sub = $dinners;
            unset($sub[$k]);
            $this->checkDinnerMealTime($v['meal_time_begin'], $v['meal_time_end'], $sub);
            $dinners[$k]['c_id'] = $c_id;
            $dinners[$k]['state'] = CommonEnum::STATE_IS_OK;
        }
        $res = (new DinnerT())->saveAll($dinners);
        if (!$res) {
            throw new SaveException();
        }
    }

    private function checkDinnerMealTime($time_begin, $time_end, $dinners)
    {
        $time_begin = strtotime($time_begin);
        $time_end = strtotime($time_end);
        foreach ($dinners as $k => $v) {
            $check = $this->is_time_cross($time_begin, $time_end, strtotime($v['meal_time_begin']), strtotime($v['meal_time_end']));
            if ($check) {
                throw new SaveException(['msg' => '就餐时间段重复，请检查']);
            }
        }

    }

    private function is_time_cross($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '')
    {
        $status = $beginTime2 - $beginTime1;
        if ($status > 0) {
            $status2 = $beginTime2 - $endTime1;
            if ($status2 >= 0) {
                return false;
            } else {
                return true;
            }
        } else {
            $status2 = $endTime2 - $beginTime1;
            if ($status2 > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    private function prefixCanteenAccount($account)
    {
        if (key_exists('id', $account)) {
            $account = CanteenAccountT::update($account);
        } else {
            $account = CanteenAccountT::create($account);
        }
        if (!$account) {
            throw new SaveException(['msg' => '处理饭堂配置失败']);
        }
    }

    public function configuration($c_id)
    {
        return [
            'dinners' => DinnerT::dinners($c_id),
            'account' => CanteenAccountT::account($c_id),
            'out_config' => OutConfigT::config($c_id),
            'address' => CanteenAddressT::address($c_id),
            'reception_config' => ReceptionConfigT::config($c_id)
        ];

    }

    public function updateConfiguration($params)
    {
        try {
            $c_id = $params['c_id'];
            if (!empty($params['dinners'])) {
                $dinners = $params['dinners'];
                $dinners = json_decode($dinners, true);
                $dinnersId = [];
                foreach ($dinners as $k => $v) {
                    $sub = $dinners;
                    unset($sub[$k]);
                    $this->checkDinnerMealTime($v['meal_time_begin'], $v['meal_time_end'], $sub);
                    if (!key_exists('id', $v)) {
                        $dinners[$k]['c_id'] = $c_id;
                        $dinners[$k]['state'] = CommonEnum::STATE_IS_OK;
                    } else {
                        array_push($dinnersId, $v['id']);
                    }

                }
                if (count($dinners)) {
                    $res = (new DinnerT())->saveAll($dinners);
                    if (!$res) {
                        throw new SaveException();
                    }
                }

                $staffStrategies = ConsumptionStrategyT::staffStrategies($c_id);
                if (!$staffStrategies->isEmpty()) {
                    $data = [];
                    foreach ($res as $k => $v) {
                        foreach ($staffStrategies as $k2 => $v2) {
                            if (!in_array($v->id, $dinnersId)) {
                                $data[] = [
                                    'c_id' => $c_id,
                                    't_id' => $v2->t_id,
                                    'd_id' => $v->id,
                                    'unordered_meals' => 1,
                                    'consumption_count' => 1,
                                    'ordered_count' => 1,
                                    'consumption_type' => 1,
                                    'state' => CommonEnum::STATE_IS_OK
                                ];
                            }
                        }

                    }
                    $saveRes = (new ConsumptionStrategyT())->saveAll($data);
                    if (!$saveRes) {
                        throw new SaveException(['msg' => '更新消费策略失败']);
                    }

                }

            }
            if (!empty($params['account'])) {
                $account = json_decode($params['account'], true);
                $account['c_id'] = $c_id;
                $this->prefixCanteenAccount($account);
            }
            if (!empty($params['out_config'])) {
                $out_config = json_decode($params['out_config'], true);
                $out_config['canteen_id'] = $c_id;
                $this->prefixOutConfig($out_config);
            }
            if (!empty($params['address'])) {
                $address = json_decode($params['address'], true);
                $add = $cancel = [];
                if (!empty($address['add'])) {
                    $add = $address['add'];
                }
                if (!empty($address['cancel'])) {
                    $cancel = $address['cancel'];
                }
                $this->prefixCanteenAddress($c_id, $add, $cancel);
            }


            if (!empty($params['reception_config'])) {
                $reception = json_decode($params['reception_config'], true);
                $this->prefixReceptionConfig($reception, $c_id);
            }
            Db::commit();
        } catch (Exception$e) {
            Db::rollback();
            throw  $e;
        }
    }

    public function companyCanteens($company_id)
    {
        if (empty($company_id)) {
            $company_id = Token::getCurrentTokenVar('company_id');
        }
        $canteens = CanteenT::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')->select()->toArray();
        return $canteens;
    }

    public function consumptionPlace($company_id)
    {
        if (empty($company_id)) {
            $company_id = Token::getCurrentTokenVar('company_id');
        }
        $canteens = CanteenT::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name,"canteen" as type')->select()->toArray();

        //获取企业小卖部
        $shop = ShopT::where('c_id', $company_id)
            ->field('id,name,"shop" as type')
            ->find();
        if ($shop) {
            array_push($canteens, $shop);
        }
        return $canteens;
    }

    public function saveConsumptionStrategy($params)
    {
        $c_id = $params['c_id'];
        $t_id = $params['t_id'];
        $this->checkStrategyExit($c_id, $t_id);
        //获取饭堂餐次
        $dinners = $this->getDinners($c_id);
        if (!count($dinners)) {
            throw new SaveException(['msg' => '新增消费策略失败，该饭堂没有设置餐次']);
        }
        $data = array();
        foreach ($dinners as $k => $v) {
            $data[] = [
                'c_id' => $c_id,
                't_id' => $t_id,
                'd_id' => $v['id'],
                'unordered_meals' => $params['unordered_meals'],
                'consumption_count' => 1,
                'ordered_count' => 1,
                'state' => CommonEnum::STATE_IS_OK
            ];
        }
        $strategies = (new ConsumptionStrategyT())->saveAll($data);
        if (!$strategies) {
            throw  new SaveException();
        }
        return $this->consumptionStrategy($c_id);
    }

    public function checkStrategyExit($canteen_id, $staff_type_id)
    {
        $exit = ConsumptionStrategyT::where('c_id', $canteen_id)
            ->where('t_id', $staff_type_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count('id');
        if ($exit) {
            throw  new SaveException(['msg' => '指定人员类型已存在消费策略，不能重复添加']);
        }

    }

    public function consumptionStrategy($c_id)
    {
        $info = ConsumptionStrategyT::info($c_id);
        if (count($info)) {
            foreach ($info as $k => $v) {
                $dinner = $v['dinner'];
                if ($dinner['state'] == CommonEnum::STATE_IS_FAIL) {
                    unset($info[$k]);
                }

            }
        }
        return $info;
    }

    public function getStaffConsumptionStrategy($c_id, $d_id, $t_id)
    {
        $info = ConsumptionStrategyT::getStaffConsumptionStrategy($c_id, $d_id, $t_id);
        return $info;
    }

    public function getStaffAllConsumptionStrategy($c_id, $t_id)
    {
        $info = ConsumptionStrategyT::getStaffAllConsumptionStrategy($c_id, $t_id);
        return $info;
    }

    public function getDinnerConsumptionStrategyForNoMeals($c_id, $d_id)
    {
        $info = ConsumptionStrategyT::getDinnerConsumptionStrategy($c_id, $d_id);
        if ($info->isEmpty()) {
            throw new ParameterException(['msg' => '餐次未设置消费策略']);
        }
        $data = [];
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
        $outsider = Token::getCurrentTokenVar('outsiders');
        if ($outsider == UserEnum::INSIDE) {
            $phone = Token::getCurrentTokenVar('phone');
            //获取用户所有饭堂
            $canteens = CompanyStaffT::getStaffCanteens($phone);
        } else {
            $user_id = Token::getCurrentUid();
            $companies = OutsiderCompanyT::companies($user_id);
            if (empty($companies)) {
                return [];
            }
            $companyIds = $companies['ids'];
            $canteens = OutConfigV::canteens($companyIds);

        }
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
        Db::startTrans();
        try {
            if (!empty($params['pwd'])) {
                $params['pwd'] = sha1($params['pwd']);
            }

            $machine = MachineT::create($params);
            if (!$machine) {
                throw new SaveException();
            }
            //处理提醒人员信息
            if (!empty($params['remind']) && $params['remind'] == CommonEnum::STATE_IS_OK) {
                if (!empty($params['reminder'])) {
                    $this->prefixReminder($params['reminder'], $machine->id);
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function prefixReminder($reminder, $machineId)
    {
        $reminder = json_decode($reminder, true);
        $data = [];
        if (!empty($reminder['add'])) {
            $add = $reminder['add'];
            $addArr = explode(',', $add);

            foreach ($addArr as $k => $v) {
                $staff = CompanyStaffT::get($v);
                array_push($data, [
                    'machine_id' => $machineId,
                    'staff_id' => $v,
                    'username' => $staff->username,
                    'openid' => ((new UserService()))->getOpenidWithStaffId($v),
                    'state' => CommonEnum::STATE_IS_OK
                ]);
            }

        }


        if (!empty($reminder['cancel'])) {
            $cancel = $reminder['cancel'];
            $cancelArr = explode(',', $cancel);
            foreach ($cancelArr as $k => $v) {
                array_push($data, [
                    'id' => $v,
                    'state' => CommonEnum::STATE_IS_FAIL
                ]);
            }
        }
        if (count($data)) {
            $res = (new MachineReminderT())->saveAll($data);
            if (!$res) {
                throw new SaveException(['msg' => "提醒用户添加失败"]);
            }
        }
    }

    public function updateMachine($params)
    {

        Db::startTrans();
        try {
            if (!empty($params['pwd'])) {
                $params['pwd'] = sha1($params['pwd']);
            }

            $machine = MachineT::update($params);
            if (!$machine) {
                throw new UpdateException();
            }
            if (!empty($params['reminder'])) {
                $this->prefixReminder($params['reminder'], $params['id']);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 获取企业下所有饭堂信息
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

    public function checkMachineState($machine_id)
    {
        if (Gateway::isUidOnline($machine_id)) {
            return 1;
        }
        return 2;
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
            ->find();
        if (empty($account)) {
            throw new ParameterException(['msg' => '未设置饭堂账户信息']);
        }
        $outsider = Token::getCurrentTokenVar('outsiders');
        if ($outsider == UserEnum::INSIDE) {
            return $account->dining_mode;
        }
        if ($account->out == CommonEnum::STATE_IS_FAIL) {
            throw  new AuthException(['msg' => "饭堂未开通外来人员就餐功能"]);
        }
        return $account->out_dining_mode;
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

    public function getCanteenCompanyID($canteen_id)
    {
        $canteen = CanteenT::get($canteen_id);
        if (!$canteen) {
            throw  new ParameterException(['msg' => '饭堂不存在']);
        }
        return $canteen->c_id;
    }

    public function updateConsumptionStrategy($params)
    {
        try {
            Db::startTrans();
            if (!empty($params['consumption_type'])) {
                if (!in_array($params['consumption_type'], [1, 2])) {
                    throw new ParameterException(['msg' => '扣费类型异常']);
                }
            }
            $strategy = ConsumptionStrategyT::update($params);
            if (!$strategy) {
                throw new UpdateException();
            }
            if (!empty($params['detail'])) {
                $strategy = ConsumptionStrategyT::get($params['id']);
                $detail = json_decode($params['detail'], true);
                $this->prefixStrategyDetail($strategy->id, $strategy->c_id, $strategy->d_id, $strategy->t_id, $detail);
            }
            if (!empty($params['consumption_type'])) {
                ConsumptionStrategyT::update(['consumption_type' => $params['consumption_type']],
                    ['c_id' => $strategy->c_id]);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function prefixStrategyDetail($strategy_id, $canteen_id, $dinner_id, $staff_type_id, $detail)
    {
        //删除消费策略下明细列表
        StrategyDetailT::destroy(function ($query) use ($strategy_id) {
            $query->where('strategy_id', '=', $strategy_id);
        });
        //处理消费策略明细
        $dataList = [];
        foreach ($detail as $k => $v) {
            if (empty($v['strategy'])) {
                throw new  ParameterException(['msg' => '消费策略数据不合法']);
                break;
            }
            $strategy = $v['strategy'];
            foreach ($strategy as $k2 => $v2) {
                array_push($dataList, [
                    'strategy_id' => $strategy_id,
                    'canteen_id' => $canteen_id,
                    'dinner_id' => $dinner_id,
                    'staff_type_id' => $staff_type_id,
                    'number' => $v['number'],
                    'status' => $v2['status'],
                    'money' => empty($v2['money']) ? 0 : $v2['money'],
                    'sub_money' => empty($v2['sub_money']) ? 0 : $v2['sub_money'],
                    'state' => CommonEnum::STATE_IS_OK
                ]);
            }
        }
        $res = (new StrategyDetailT())->saveAll($dataList);
        if (!$res) {
            throw new SaveException(['msg' => '保存消费策略明细失败']);
        }
    }

    public function checkConfirm($canteen_id)
    {
        if (!$canteen_id) {
            $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        }
        $account = CanteenAccountT::where('c_id', $canteen_id)
            ->find();
        if (!$account) {
            throw new ParameterException(['msg' => '饭堂未设置账户']);
        }
        return [
            'confirm' => $account->confirm
        ];
    }

    public function deleteDinner($id)
    {
        $res = DinnerT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$res) {
            throw new DeleteException();
        }
        //清除消费策略
        ConsumptionStrategyT::update(['state' => CommonEnum::STATE_IS_FAIL], ['d_id' => $id]);
        StrategyDetailT::update(['state' => CommonEnum::STATE_IS_FAIL], ['dinner_id' => $id]);
    }
}