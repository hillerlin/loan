<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'defaultRoute' => 'index',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],
    'modules' => [
    ],
    'components' => [
        'request' => [
            'parsers' => ['application/json' => 'yii\web\JsonParser',]
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-dmlc', 'httpOnly' => true],
            'class' => 'common\components\MyUser',
            'loginUrl' => '/login/index'
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'dmlc',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' =>false,//这句一定有，false发送邮件，true只是生成邮件在runtime文件夹下，不发邮件
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.exmail.qq.com',  //每种邮箱的host配置不一样
                'username' => 'info@damailicai.com',
                'password' => 'damailicai987inf',
                'port' => '465',
                'encryption' => 'ssl',
            ],
            'messageConfig'=>[
                'charset'=>'UTF-8',
                'from'=>['info@damailicai.com'=>'大麦理财']
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'page/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'api/wrb/fclc_loans/<invest_id:\d+>'=>'api/wrb/fclc_loans',
                'api/jpmapi/status/<bid:\d+>'=>'api/jpmapi/status/',
                'factoring/<bid:\d+>.html'=>'product/investment',
            ],
        ],
    ],
    'params' => $params,
];
