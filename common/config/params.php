<?php
return [
    'version' => '1',
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'link_addr' => '深圳市福田区深南大道6033号金运世纪20楼G-I',
    'link_mail' => 'ad@damailicai.com',
    'link_kftel' => '400-0822-188',
    'projectStartTime' => '2017-3-31', //项目开始的时间
    'page.limit' => 10,
    'account.limit'=>3,//资金流水翻页
    'dmUrl'=>'http://htmlnew.atrmoney.com',
    'urlMobile'=>'http://mnew.atrmoney.com',
    'urlApp'=>'mnew.atrmoney.com',
    'custody' => require(__DIR__ . '/custody_config/custody.php'),
    //存管字段和内部字段对应表
    'custody_map' => require(__DIR__ . '/custody_config/map.php'),
    'rate_map' => [
        '1' => [
            3 => ['rate' => 8.5, 'real_rate' => 14.94, 'min_money' => 1000],
            6 => ['rate' => 9.0, 'real_rate' => 16.95, 'min_money' => 1000],
            9 => ['rate' => 9.5, 'real_rate' => 17.66, 'min_money' => 1000],
            12 => ['rate' => 10, 'real_rate' => 17.98, 'min_money' => 1000],
            18 =>['rate' => 10.5,  'real_rate' => 18.18, 'min_money' => 15000],
        ],
        '100002' => [
            3 => ['rate' => 8.5, 'real_rate' => 14.94, 'min_money' => 1000],
            6 => ['rate' => 9.0, 'real_rate' => 16.95, 'min_money' => 1000],
            9 => ['rate' => 9.5, 'real_rate' => 17.66, 'min_money' => 1000],
            12 => ['rate' => 10, 'real_rate' => 17.98, 'min_money' => 1000],
            18 => ['rate' => 10.5,  'real_rate' => 18.18, 'min_money' => 15000],
        ],
        '100003' => [
            3 => ['rate' => 8.5, 'min_money' => 1000],
            6 => ['rate' => 9.0, 'min_money' => 1000],
            9 => ['rate' => 9.5, 'min_money' => 1000],
            12 => ['rate' => 10, 'min_money' => 1000],
            18 => ['rate' => 10.5, 'min_money' => 10000],
            24 => ['rate' => 11,  'real_rate' => 16, 'min_money' => 20000],
        ],
    ],
    'notify_url' => [
        '1' => '',
        '100002' => 'https://fenqi.resants.com/loan/callback',
        '100003' => 'http://jsg4.moootooo.com/jsg_wx/js_wx_custom/dm_log_test.php',
    ],
    'account_log_type'=>['0'=>'全部','1'=>'充值成功','2'=>'投资体验金','3'=>'投资产品','4'=>'产品还款','5'=>'投资奖励','6'=>'申请提现','7'=>'扣除资金','8'=>'待收本息'],
    'black_list' => ['32020219790403401X'],
];
