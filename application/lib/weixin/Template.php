<?php


namespace app\lib\weixin;


use app\api\service\LogService;

class Template extends Base
{

    public function send($openid, $template_id, $url, $data)
    {
        LogService::saveJob($openid);
        LogService::saveJob($template_id);
        LogService::saveJob($url,json_encode($data));
        $res = $this->app->template_message->send([
            'touser' => $openid,
            'template_id' => $template_id,
         //   'url' => $url,
            'data' => $data,
        ]);

        return $res;
    }

}