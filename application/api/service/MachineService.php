<?php


namespace app\api\service;


use app\api\model\MachineT;
use app\api\model\OfflineReceiveT;
use app\lib\exception\ParameterException;
use app\lib\exception\UpdateException;

class MachineService
{
    public function offlineReceive($code)
    {
        if (empty($code)) {
            throw new ParameterException();
        }
        $record = OfflineReceiveT::where('code', $code)->find();
        if (!$record) {
            throw new ParameterException(['msg' => "记录不存在"]);
        }
        $record->state = 0;
        $record->save();

    }


    public function records($company_id, $day, $machine_id, $status)
    {
        $records = OfflineReceiveT::records($company_id, $day, $machine_id, $status);
        if ($records) {
            foreach ($records as $k => $v) {
                $records[$k]['status'] = $v['status'] ? 2 : 1;

                if ($status == 1 && $v['status']) {
                    unset($records[$k]);
                    continue;
                }
                if ($status == 2 && !$v['status']) {
                    unset($records[$k]);
                    continue;
                }
            }
        }
        return $records;
    }


    public function detail($machineId, $day)
    {
        return OfflineReceiveT::detail($machineId, $day);
    }

    public function machines($companyId)
    {
        return MachineT::companyMachinesForOffline($companyId);
    }

    public function updateMachineConfig($params)
    {
        $config = MachineConfigT::update($params);
        if (!$config) {
            throw new UpdateException();
        }
    }
    public function updateFaceConfig($params)
    {
        $config = FaceConfigT::update($params);
        if (!$config) {
            throw new UpdateException();
        }
    }

    public function getMachineConfig($company_id,$page,$size,$canteen_id,$type,$name,$code)
    {
        $list=MachineT::machineConfig($company_id,$page,$size,$canteen_id,$type,$name,$code);
        return $list;
    }


}