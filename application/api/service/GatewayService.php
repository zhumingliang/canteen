<?php


namespace app\api\service;


use GatewayClient\Gateway;

class GatewayService
{
    public static function sendToMachine($u_id, $message)
    {
        Gateway::sendToUid( $u_id, $message);
    }

}