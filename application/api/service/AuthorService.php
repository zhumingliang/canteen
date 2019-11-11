<?php


namespace app\api\service;


use app\lib\exception\AuthException;

class AuthorService
{
    public function checkAuthorSupplier()
    {
        $type = Token::getCurrentTokenVar('type');
        if ($type != "supplier") {
            throw new AuthException();
        }
        return Token::getCurrentUid();
    }

}