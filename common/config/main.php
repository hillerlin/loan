<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone'=>'PRC',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'tracelevel' => YII_DEBUG ? 3:0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning' , 'info'],
                    'logFile' => '@app/runtime/logs/Mylog/'.date('Y-m-d').'damailicai.log',
                    'maxFileSize' => 1024*10,
                    'maxLogFiles' => 30,
                ],
                [  
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error' ,'warning' , 'info', 'trace'],
                    'categories' => ['site'],
                    'logFile' => '@app/runtime/logs/Mylog/'.date('Y-m-d').'site.log',
                    'maxFileSize' => 1024*10,
                    'maxLogFiles' => 30,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'], 
                    'categories' => ['cusdoy_success'],
                    'logFile' => '@app/runtime/logs/cusdoy/'.date('Y-m-d').'success.log',
                    'maxFileSize' => 1024*10,
                    'maxLogFiles' => 30,
                    'logVars' => ['_SESSION']
                ],
                [  
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'], 
                    'categories' => ['custody_error'],
                    'logFile' => '@app/runtime/logs/cusdoy/'.date('Y-m-d').'error.log',
                    'maxFileSize' => 1024*10,
                    'maxLogFiles' => 30,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['merNotify'],
                    'logFile' => '@app/runtime/logs/merNotify/'.date('Y-m-d').'.log',
                    'maxFileSize' => 1024*2,
                    'maxLogFiles' => 20,
                ],
                [
                    //银行notify数据
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['notify'],
                    'logFile' => '@app/runtime/logs/notify/'.date('Y-m-d').'.log',
                    'maxFileSize' => 1024*2,
                    'maxLogFiles' => 20,
                ],
            ],
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => '120.24.68.184',
            'port' => 6379,
            'database' => 0,
            'password' => 'woshimima',
        ],
        'redis_2' => [
            'class' => '\Redis()',
            'hostname' => '120.24.68.184',
            'port' => 6379,
            'database' => 0,
            'password' => 'woshimima',
        ],
        //用户信息，余额刷新等
        'redisUser' => [
            'class' => 'yii\redis\Connection',
            'hostname' => '120.24.68.184',
            'port' => 6379,
            'database' => 0,
            'password' => 'woshimima',
        ],
        'client'=> [
            'class' => 'common\components\Client'
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'suffix' => '',
            'rules' => [
                'party/landing' => 'landing/index', //渠道落地页
            ],
        ],
    ],
];
