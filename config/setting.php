<?php

return [
    'domain' => 'http://canteen.tonglingok.com',
    'qrcode_expire_in' => 10,
    'shop_qrcode_expire_in' => 10,
    'qrcode_url' => 'http://canteen.tonglingok.com/api/v1/consumption/staff?type=%s&code=%s&staff_id=%s',
    'token_cms_expire_in' => 3600 * 24,
    'token_mini_expire_in' => 3600 * 2,
    'token_machine_expire_in' => 3600 * 24 * 7,
    'token_official_expire_in' => 3600 * 24,
];
