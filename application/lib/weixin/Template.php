<?php


namespace app\lib\weixin;


class Template extends Base
{

    public function send($openid, $template_id, $url, $data)
    {
        $res = $this->app->template_message->send([
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => $url,
            'data' => $data,
        ]);

        return $res;
    }

}