<?php


namespace app\api\service;


class AccountService
{
    public function save($params)
    {
        $admin = Token::getCurrentTokenVar('username');

    }

}