<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 11:29
 */

namespace app\lib\enum;


class OrderEnum
{
    const PERSON_CHOICE_CANTEEN = 1;

    const PERSON_CHOICE_OUT = 2;

    const OVERDRAFT = 1;

    const OVERDRAFT_NO = 2;

    const EAT_CANTEEN = 1;

    const EAT_OUTSIDER = 2;

    const ORDERING_CHOICE = 'personal_choice';

    const ORDERING_ONLINE = 'online';


    const USER_ORDER_CANTEEN = 1;

    const USER_ORDER_OUTSIDE = 2;

    const USER_ORDER_SHOP = 3;

    const STATISTIC_BY_DEPARTMENT = 1;

    const STATISTIC_BY_USERNAME = 2;

    const STATISTIC_BY_STAFF_TYPE = 3;

    const STATISTIC_BY_CANTEEN = 4;

    const STATISTIC_BY_STATUS = 5;

}