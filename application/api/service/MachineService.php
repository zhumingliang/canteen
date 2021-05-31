<?php


namespace app\api\service;


use app\api\model\OfflineReceiveT;
use app\lib\exception\ParameterException;

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
        $record->state = 2;
        $record->save();

    }

}