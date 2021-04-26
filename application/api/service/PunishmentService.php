<?php


namespace app\api\service;


use app\api\model\CompanyStaffT;
use app\api\model\PunishmentDetailT;
use app\api\model\PunishmentStrategyT;
use app\api\model\PunishmentUpdateT;
use app\api\model\StaffPunishmentT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;

class PunishmentService extends BaseService
{
    public function strategyDetails($page, $size, $company_id, $canteen_id)
    {
        $details = PunishmentStrategyT::strategyDetail($page, $size, $company_id, $canteen_id);
        return $details;
    }

    public function updateStrategy($params)
    {
        $detail = json_decode($params['detail'], true);

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
//        $header = ['企业名称', '饭堂', '人员类型', '姓名', '手机号码', '状态','订餐未就餐违规次数','未订餐就餐违规次数'];
//        $file_name = "惩罚管理";
//        $punishmentStaffInfo = StaffPunishmentT::prefixExportPunishmentStaffInfo($key, $company_id, $company_name, $status);
//        $url = (new ExcelService())->makeExcel($header, $punishmentStaffInfo, $file_name);
//        $data = ['url' => 'http://' . $_SERVER['HTTP_HOST'] . $url];
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
//        $header = ['日期','企业名称', '饭堂', '人员类型', '姓名', '手机号码', '旧状态','订餐未就餐违规次数(旧)','未订餐就餐违规次数(旧)','新状态','订餐未就餐违规次数(新)','未订餐就餐违规次数(新)'];
//        $file_name = "惩罚编辑详情";
//        $punishmentEditDetails = PunishmentUpdateT::prefixExportPunishmentEditDetails($key, $company_id, $company_name, $canteen_id,
//            $time_begin, $time_end);
//        $url = (new ExcelService())->makeExcel($header, $punishmentEditDetails, $file_name);
//        $data = ['url' => 'http://' . $_SERVER['HTTP_HOST'] . $url];
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
            $old_state_arr = json_decode($v['old_state'],true);
            $data['old_state'] = $this->getStatus($old_state_arr['status']);
            $data['old_no_meal'] = $old_state_arr['no_meal'] != '' ? $old_state_arr['no_meal'] : '0';
            $data['old_no_booking'] = $old_state_arr['no_booking'] != '' ? $old_state_arr['no_booking'] : '0';
            $new_state_arr = json_decode($v['new_state'],true);
            $data['new_state'] = $this->getStatus($new_state_arr['status']);
            $data['new_no_meal'] = $new_state_arr['no_meal'] != '' ? $new_state_arr['no_meal'] : '0';
            $data['new_no_booking'] = $new_state_arr['no_booking'] != '' ? $new_state_arr['no_booking'] : '0';
            array_push($dataList, $data);
        }
        return $dataList;

    }

    public function updatePunishmentStatus($params)
    {
        $punish = json_decode($params['new_state'], true);
        $new_status = $punish['status'];
        $res = CompanyStaffT::update(['status' => $new_status], ['id' => $params['staff_id']]);
        if (!$res) {
            throw new UpdateException();
        }
        $record = PunishmentUpdateT::where('staff_id', $params['staff_id'])->find();
        if ($record) {
            $update = PunishmentUpdateT::update(['admin_id' => $params['admin_id'], 'old_state' => $params['old_state'],
                'new_state' => $params['new_state']],
                ['staff_id' => $params['staff_id']]);
            if (!$update) {
                throw new UpdateException();
            }
        } else {
            $save = PunishmentUpdateT::create($params);
            if (!$save) {
                throw new SaveException();
            }
        }
    }

    private function getStatus($status)
    {
        if ($status == 1) {
            return "正常(未违规)";
        }
        elseif ($status == 2) {
            return "正常";
        }
        elseif ($status == 3) {
            return "白名单";
        }
        elseif ($status == 4) {
            return "黑名单";
        }
        else{
            return "";
        }
    }
}