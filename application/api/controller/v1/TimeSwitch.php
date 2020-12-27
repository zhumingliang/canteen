<?php


namespace app\api\controller\v1;



use app\api\service\Token;
use app\lib\exception\AuthException;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\Db;
use think\Exception;
use think\facade\Request;
use think\Queue;
use GatewayClient\Gateway;
use zml\tp_tools\Redis;
use app\api\service\GatewayService;
use app\api\service\LogService;

class TimeSwitch
{
    //增加定时开关
    public function addTimeSwitch(){
        $param = Request::param();
        $on_time = $param['on_time'];
        $off_time = $param['off_time'];
        $repeat = $param['repeat'];
        $device = $param['device'];
        $devices = explode(',',$device);
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $exist_devices = Db::table('canteen_machine_timeswitch_t')
            ->where('canteen_id',$canteen_id)
            ->where('company_id',$company_id)
            ->field('device')
            ->select();
        foreach ($exist_devices as $exist_device){
            $exist_device = $exist_device['device'];
            $exist_device = explode(',',$exist_device);
            foreach ($devices as $device2){
                if (in_array($device2,$exist_device)){
                    throw new AuthException(['msg' => '单个消费机仅能设定一个定时开关']);
                }
            }
        }
        if (empty($on_time)){
            throw new AuthException(['msg' => '请选择开启时间']);
        }
        if (empty($off_time)){
            throw new AuthException(['msg' => '请选择关闭时间']);
        }
        if (empty($repeat)){
            throw new AuthException(['msg' => '请选择重复次数']);
        }
        if (empty($device)){
            throw new AuthException(['msg' => '请选择绑定设备']);
        }
        $data = [
            'on_time' => $param['on_time'],
            'off_time' => $param['off_time'],
            'repeat' => $param['repeat'],
            'device' => $param['device'],
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
            'company_id' => $company_id,
            'canteen_id' => $canteen_id,
        ];
        $insert = Db::table('canteen_machine_timeswitch_t')
            ->insert($data);
        $id = Db::table('canteen_machine_timeswitch_t')->getLastInsID();
        foreach ($devices as $device2){
            $this->sendData($id,$on_time,$off_time,$repeat,$device2,1);
        }
        if ($insert){
            return json(new SuccessMessage());
        }else{
            throw new SaveException();
        }

    }

    //更新定时开关
    public function updateTimeSwitch(){
        $id = Request::param('id');
        $before= Db::table('canteen_machine_timeswitch_t')
            ->where('id',$id)
            ->find();
        $before_device = $before['device'];
        $before_device = explode(',',$before_device);
        $param = Request::param();
        $on_time = $param['on_time'];
        $off_time = $param['off_time'];
        $repeat = $param['repeat'];
        $device = $param['device'];
        if (empty($on_time)){
            throw new AuthException(['msg' => '请选择开启时间']);
        }
        if (empty($off_time)){
            throw new AuthException(['msg' => '请选择关闭时间']);
        }
        if (empty($repeat)){
            throw new AuthException(['msg' => '请选择重复次数']);
        }
        if (empty($device)){
            throw new AuthException(['msg' => '请选择绑定设备']);
        }
        $data = [
            'on_time' => $param['on_time'],
            'off_time' => $param['off_time'],
            'repeat' => $param['repeat'],
            'device' => $param['device'],
            'status' => 1,
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $update = Db::table('canteen_machine_timeswitch_t')
            ->where('id',$id)
            ->update($data);
        $devices = explode(',',$device);
        foreach ($before_device as $before_device2){
            if (!in_array($before_device2,$devices)){
                $this->sendData($id,$before['on_time'],$before['off_time'],$before['repeat'],$before_device2,2);
            }
        }
        foreach ($devices as $device2){
            $this->sendData($id,$on_time,$off_time,$repeat,$device2,1);
        }
        if ($update){
            return json(new SuccessMessage());
        }else{
            throw new SaveException();
        }
    }

    //显示定时开关
    public function showTimeSwitch(){
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $table = Db::table('canteen_machine_timeswitch_t')
            ->where('canteen_id',$canteen_id)
            ->where('company_id',$company_id)
            ->select();
        return json(new SuccessMessageWithData(['data' => $table]));
    }

    //删除定时开关
    public function deleteTimeSwitch(){
        $id = Request::param('id');
        $time = Db::table('canteen_machine_timeswitch_t')->where('id',$id)->find();
        $on_time = $time['on_time'];
        $off_time = $time['off_time'];
        $devices = $time['device'];
        $repeat = $time['repeat'];
        $devices = explode(',',$devices);
        foreach ($devices as $device){
            $this->sendData($id,$on_time,$off_time,$repeat,$device,2);
        }
        $delete = Db::table('canteen_machine_timeswitch_t')->where('id',$id)->delete();
        if ($delete){
            return json(new SuccessMessage());
        }else{
            throw new SaveException();
        }
    }

    //转换开关状态
    public function switchButton(){
        $id = Request::param('id');
        $time = Db::table('canteen_machine_timeswitch_t')->where('id',$id)->find();
        $on_time = $time['on_time'];
        $off_time = $time['off_time'];
        $devices = $time['device'];
        $repeat = $time['repeat'];
        $devices = explode(',',$devices);
        $status = $time['status'];
        if ($status == 1){
            foreach ($devices as $device){
                $this->sendData($id,$on_time,$off_time,$repeat,$device,2);
            }
            $switch = Db::table('canteen_machine_timeswitch_t')
                ->where('id',$id)
                ->update(['status' => '2','update_time' => date('Y-m-d H:i:s')]);
            if ($switch){
                return json(new SuccessMessage());
            }else{
                throw new SaveException();
            }
        }else {
            foreach ($devices as $device) {
                $this->sendData($id,$on_time,$off_time,$repeat,$device,1);
            }
            $switch = Db::table('canteen_machine_timeswitch_t')
                ->where('id', $id)
                ->update(['status' => '1','update_time' => date('Y-m-d H:i:s')]);
            if ($switch) {
                return json(new SuccessMessage());
            } else {
                throw new SaveException();
            }
        }
    }

    //发送开关至消费机
    public function sendData($id,$on_time,$off_time,$repeat,$device,$status){
        $jobHandlerClassName = 'app\api\job\SendSort2'; //负责处理队列任务的类
        $jobQueueName = "sendSortQueue";//队列名称
        $websocketCode = $this->saveRedisSortCode();
        $jobData = [
            'id' => $id,
            'off_time' => $off_time,
            'on_time' => $on_time,
            'repeat' => $repeat,
            'valid' => $status,
            'device' => $device,
            'websocketCode' => $websocketCode
        ];;//当前任务的业务数据
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
        //将该任务推送到消息队列
        if ($isPushed == false) {
            throw new SaveException(['msg' => '发送时间推送失败']);
        }
    }

    //显示设备号
    public function showDevice(){
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $devices = Db::table('canteen_machine_t')->where('belong_id',$canteen_id)
            ->where('company_id',$company_id)->field('id,name')->select();
        return json(new SuccessMessageWithData(['data' => $devices]));

    }


    public function saveRedisSortCode()
    {
        $set = "webSocketReceiveCode";
        $sortCode = getRandChar(8);
        Redis::instance()->sAdd($set, $sortCode);
        return $sortCode;
    }
}