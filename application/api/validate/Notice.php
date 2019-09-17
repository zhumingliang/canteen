<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/17
 * Time: 9:12
 */

namespace app\api\validate;


class Notice extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'title ' => 'require|isNotEmpty',
        'content' => 'require|isNotEmpty',
        'author' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'send' => ['title', 'content','author'],
    ];
}