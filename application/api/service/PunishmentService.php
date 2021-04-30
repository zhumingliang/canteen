<?php


namespace app\api\service;


use app\api\model\CanteenT;
use app\api\model\CompanyStaffT;
use app\api\model\ConsumptionStrategyT;
use app\api\model\PunishmentDetailT;
use app\api\model\PunishmentRecordsT;
use app\api\model\PunishmentStrategyT;
use app\api\model\PunishmentUpdateT;
use app\api\model\StaffPunishmentT;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;

class PunishmentService extends BaseService
{
    public function strategyDetails($page, $size, $company_id)
    {
        $nowdate = date('Y-m-d h:i:s');
        $staffTypeids = $this->getstaffType($company_id);
        foreach ($staffTypeids as $k2 => $v2) {
            $staffType_id = $v2['id'];
            $data = array();
            $data['company_id'] =$company_id;
            $data['staff_type_id']=$staffType_id;
            $data['create_time']=$nowdate;
            $data['update_time']=$nowdate;
            $strategies =PunishmentStrategyT::create($data);
            if (!$strategies) {
                throw  new SaveException();
            }
            $stratege_ids=$strategies->id;
            $createDetail=$this->createDetail($stratege_ids);
        }
        $details = PunishmentStrategyT::strategyDetail($page, $size, $company_id);
        return $details;
    }


    public function getstaffType($company_id)
    {
        $t_id = $this->staffsType($company_id);
        $staffsType_id = $this->getpunishment($company_id);
        $staffsType_ids = [];
        foreach ($t_id as $k1 => $v1) {
            if (in_array($v1['id'], $staffsType_id)) {
                $exitType[] = $v1['id'];
            } else {
                $staffsType_ids[] = [
                    'id' => $v1['id']
                ];
            }
        }
        return $staffsType_ids;
    }
    public function createDetail($staffTypeids){
        $nowdate = date('Y-m-d h:i:s');
        if (!empty($staffTypeids)){
            $data = array();
            for ($i=0;$i<2;$i++){
                $data[] = [
                    'strategy_id' => $staffTypeids,
                    'type' =>'',
                    'count'=>'',
                    'state'=>1,
                    'create_time' => $nowdate,
                    'update_time' => $nowdate
                ];
            }
            $strategyDetail = (new PunishmentDetailT())->saveAll($data);
            if (!$strategyDetail) {
                throw  new SaveException();
            }
        }
    }

    public function staffsType($company_id)
    {
        $types = CompanyStaffT::where('company_id', $company_id)
            ->distinct(true)
            ->field(' t_id as id')
            ->select();
        return $types;
    }

    public function getpunishment($company_id)
    {
        $Type = PunishmentStrategyT::where('company_id', $company_id)->distinct(true)
            ->field('staff_type_id')->select();
        $staffType_ids = [];
        foreach ($Type as $k => $v) {
            array_push($staffType_ids, $v['staff_type_id']);
        }
        return $staffType_ids;
    }

    public function updateStrategy($params)
    {
        $detail = json_decode($params['detail'], true);
        if (!count($detail)) {
            throw new ParameterException();
        }
        $res = (new PunishmentDetailT())->saveAll($detail);
        if (!$res) {
            throw new UpdateException();
        }
    }

    public function getPunishmentStaffStatus($page, $size, $key, $company_id, $company_name, $status)
    {
        $staffs = StaffPunishmentT::getStaffWithPunishmentStatus($page, $size, $key, $company_id, $company_name, $status);
        return $staffs;
    }

    public function prefixExportPunishmentStaffInfo($key, $company_id, $company_name, $status)
    {
        $staffs = StaffPunishmentT::prefixExportPunishmentStaffInfo($key, $company_id, $company_name, $status);
        $dataList = [];
        foreach ($staffs as $k => $v) {
            $data['company_name'] = $v['company_name'];
            $data['canteen_name'] = $v['canteen_name'];
            $data['staff_type'] = $v['staff_type'];
            $data['username'] = $v['username'];
            $data['phone'] = $v['phone'];
            $data['status'] = $this->getStatus($v['status']);
            $data['no_meal'] = $v['no_meal'] != '' ? $v['no_meal'] : '0';
            $data['no_booking'] = $v['no_booking'] != '' ? $v['no_booking'] : '0';
            array_push($dataList, $data);
        }
        return $dataList;
    }

    public function getPunishmentEditDetails($page, $size, $key, $company_id, $company_name,
                                             $canteen_id, $time_begin, $time_end)
    {
        $details = PunishmentUpdateT::getPunishmentEditDetails($page, $size, $key, $company_id, $company_name,
            $canteen_id, $time_begin, $time_end);
        return $details;
    }

    public function prefixExportPunishmentEditDetails($key, $company_id, $company_name, $canteen_id,
                                                      $time_begin, $time_end)
    {
        $details = PunishmentUpdateT::prefixExportPunishmentEditDetails($key, $company_id, $company_name, $canteen_id,
            $time_begin, $time_end);
        $dataList = [];
        foreach ($details as $k => $v) {
            $data['date'] = $v['date'];
            $data['company_name'] = $v['company_name'];
            $data['canteen_name'] = $v['canteen_name'];
            $data['staff_type'] = $v['staff_type'];
            $data['username'] = $v['username'];
            $data['phone'] = $v['phone'];
            $old_state_arr = json_decode($v['old_state'], true);
            $data['old_state'] = $this->getStatus($old_state_arr['status']);
            $data['old_no_meal'] = $old_state_arr['no_meal'] != '' ? $old_state_arr['no_meal'] : '0';
            $data['old_no_booking'] = $old_state_arr['no_booking'] != '' ? $old_state_arr['no_booking'] : '0';
            $new_state_arr = json_decode($v['new_state'], true);
            $data['new_state'] = $this->getStatus($new_state_arr['status']);
            $data['new_no_meal'] = $new_state_arr['no_meal'] != '' ? $new_state_arr['no_meal'] : '0';
            $data['new_no_booking'] = $new_state_arr['no_booking'] != '' ? $new_state_arr['no_booking'] : '0';
            array_push($dataList, $data);
        }
        return $dataList;

    }

    public function updatePunishmentStatus($params)
    {
        $newStateJson = json_decode($params['new_state'], true);
        $oldStateJson = json_decode($params['old_state'], true);
        if (empty($newStateJson) || empty($oldStateJson)) {
            throw new ParameterException(['msg' => '状态参数格式错误']);
        }
        $newStatus = $newStateJson['status'];
        $res = CompanyStaffT::update(['status' => $newStatus], ['id' => $params['staff_id']]);
        if (!$res) {
            throw new UpdateException();
        }
        $save = PunishmentUpdateT::create($params);
        if (!$save) {
            throw new SaveException();
        }
    }

    public function getStaffMaxPunishment($company_id, $t_id)
    {
        $data = (new PunishmentStrategyT())->getStaffMaxPunishment($company_id, $t_id);
        return $data;
    }

    public function penaltyDetails($page, $size, $time_begin, $time_end, $company_id
        , $canteen_id, $department_id, $staff_id, $meal)
    {
        $where = (new PunishmentRecordsT)->checkData($meal, $canteen_id, $department_id, $staff_id);
        $whereTime = [$time_begin, $time_end];
        $data = (new PunishmentRecordsT)->punishStaff($company_id)->where($where)->whereTime('day', $whereTime)->paginate($size, false, ['page' => $page])->toArray();

        foreach ($data['data'] as $key => $value) {
            $data['data'][$key]['state'] = '违规1次';
        }
        return $data;
    }

    public function ExportPenaltyDetails($time_begin, $time_end, $company_id
        , $canteen_id, $department_id, $staff_id, $meal)
    {
        $where = (new PunishmentRecordsT)->checkData($meal, $canteen_id, $department_id, $staff_id);
        $whereTime = [$time_begin, $time_end];
        $data = (new PunishmentRecordsT)->punishStaff($company_id)->where($where)->whereTime('day', $whereTime)->select()->toArray();
        foreach ($data as $key => $value) {
            $data[$key]['state'] = '违规1次';
        }
        return $data;
    }

    private function getStatus($status)
    {
        $values = [
            1 => '正常(未违规)',
            2 => '正常',
            3 => '白名单',
            4 => '黑名单'
        ];
        return empty($values[$status]) ? '' : $values[$status];
    }
}