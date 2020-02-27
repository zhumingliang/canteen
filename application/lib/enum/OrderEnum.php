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

    const REFUND = 3;

    const EAT_CANTEEN = 1;

    const EAT_OUTSIDER = 2;

    const ORDERING_CHOICE = 'personal_choice';

    const ORDERING_ONLINE = 'online';

    const ORDERING_NO = 'no';

    const USER_ORDER_CANTEEN = 1;

    const USER_ORDER_OUTSIDE = 2;

    const USER_ORDER_SHOP = 3;

    const STATISTIC_BY_DEPARTMENT = 1;

    const STATISTIC_BY_USERNAME = 2;

    const STATISTIC_BY_STAFF_TYPE = 3;

    const STATISTIC_BY_CANTEEN = 4;

    const STATISTIC_BY_STATUS = 5;

    const STATUS_PAID = 1;

    const STATUS_CANCEL = 2;

    const STATUS_RECEIVE = 3;

    const STATUS_COMPLETE = 4;

    const STATUS_REFUND = 5;

}