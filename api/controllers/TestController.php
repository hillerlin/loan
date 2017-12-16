<?php

namespace api\controllers;

use common\custody\UserApi;
use common\lib\ParameterUtil;
use common\models\LoanMerchant;
use Yii;
use yii\web\Controller;

/**
 * Site controller
 */
class TestController extends Controller {

    /**
     * @inheritdoc
     */
    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
    
    public function actionTrusteePayPlus() {
        
        return $this->renderPartial('trusteePayPlus');
    }
    
    public function actionTrusteePay() {
        
        return $this->renderPartial('trusteePay');
    }

    public function actionIndex()
    {
        return $this->renderPartial('test.html');
    }

    public function actionArray()
    {
        $service = Yii::$app->request->post('service', '');
        $arrayDefault = [
            ['name' => 'dev', 'desc' => '开发环境|dev', 'value' => '1', 'remark' => '1-不验签'],
            ['name' => 'version', 'desc' => '版本号|version', 'value' => '1', 'remark' => ''],
            ['name' => 'merchantId', 'desc' => '机构代码|merchantId', 'value' => '1', 'remark' => ''],
            ['name' => 'txDate', 'desc' => '交易日期|txDate', 'value' => '20171115', 'remark' => 'YYYYMMDD'],
            ['name' => 'txTime', 'desc' => '交易时间|txTime', 'value' => '000000', 'remark' => 'hhmmss'],
            ['name' => 'seqNo', 'desc' => '交易流水号|seqNo', 'value' => '111111', 'remark' => '定长6位'],
            ['name' => 'sign', 'desc' => '签名|sign', 'value' => 'abc', 'remark' => '验签'],
            ['name' => 'channel', 'desc' => '交易渠道|channel', 'value' => '000001', 'remark' => '000001手机APP|000002网页|000003微信|000004柜面'],
            ['name'=>'acqRes', 'desc'=>'请求方保留|acqRes', 'value' => '', 'remark'=>''],
        ];
        $array = [
            'register' => [
                'url' => '',
                'param' => [
                    ['name'=>'mobile', 'desc'=>'手机号|mobile', 'value' => '', 'remark'=>''],
                    ['name'=>'idNo', 'desc'=>'身份证号|idNo', 'value' => '', 'remark'=>''],
                    ['name'=>'realName', 'desc'=>'真实姓名|realName', 'value' => '', 'remark'=>''],
                    ['name'=>'creditLine', 'desc'=>'信用额度|creditLine', 'value' => '', 'remark'=>'20万以内'],
                ],
            ],
            'accountOpen' => [
                'url' => '/page/account-open',
                'param' => [
                    ['name'=>'dmId', 'desc'=>'大麦用户ID|dmId', 'value' => '', 'remark'=>''],
                    ['name'=>'cardNo', 'desc'=>'银行卡号|cardNo', 'value' => '', 'remark'=>'绑定银行卡号'],
                    ['name'=>'smsCode', 'desc'=>'短信验证码|smsCode', 'value' => '', 'remark'=>'手机接收到短信验证码'],
                    ['name'=>'retUrl', 'desc'=>'前台跳转链接|retUrl', 'value' => 'http://120.24.68.184:9018', 'remark'=>''],
                    ['name'=>'notifyUrl', 'desc'=>'后台通知链接|notifyUrl', 'value' => 'http://120.24.68.184:9018', 'remark'=>''],
                ],
            ],
            'smsCodeApply' => [
                'url' => '',
                'param' => [
                    ['name'=>'mobile', 'desc'=>'手机号|mobile', 'value' => '', 'remark'=>''],
                    ['name'=>'srvTxCode', 'desc'=>'业务交易代码|srvTxCode', 'value' => 'accountOpenPlus', 'remark'=>'开通存管账号accountOpenPlus|绑定银行卡cardBindPlus|修改手机号mobileModifyPlus|重置交易密码passwordResetPlus'],
                ],
            ],
            'collectUserInfo' => [
                'url' => '',
                'param' => [
                    ['name'=>'dmId', 'desc'=>'大麦用户ID|dmId', 'value' => '', 'remark'=>''],
                    ['name'=>'degree', 'desc'=>'学历|degree', 'value' => '', 'remark'=>'1 – 博士|2 – 硕士|3 – 本科|4 – 专科|5 – 高中|6 – 初中|7 – 小学及以下'],
                    ['name'=>'marriage', 'desc'=>'婚姻状况|marriage', 'value' => '', 'remark'=>'1 – 已婚|2 – 未婚|3 – 离异'],
                    ['name'=>'address', 'desc'=>'现居住地|address', 'value' => '', 'remark'=>''],
                    ['name'=>'carNo', 'desc'=>'车辆信息|carNo', 'value' => '', 'remark'=>'车辆信息+车牌号，没有则填“无”'],
                    ['name'=>'property', 'desc'=>'房产情况|property', 'value' => '', 'remark'=>'1 – 有房|2 – 无房'],
                    ['name'=>'income', 'desc'=>'年收入|income', 'value' => '', 'remark'=>'年收入（单位：万元）'],
                    ['name'=>'companyIndustry', 'desc'=>'所处行业|companyIndustry', 'value' => '', 'remark'=>''],
                    ['name'=>'companyNature', 'desc'=>'公司性质|companyNature', 'value' => '', 'remark'=>''],
                    ['name'=>'companyPosition', 'desc'=>'担任职务|companyPosition', 'value' => '', 'remark'=>''],
                ],
            ],
            'borrow' => [
                'url' => '',
                'param' => [
                    ['name'=>'dmId', 'desc'=>'大麦用户ID|dmId', 'value' => '', 'remark'=>''],
                    ['name'=>'amount', 'desc'=>'借款金额|amount', 'value' => '', 'remark'=>''],
                    ['name'=>'duration', 'desc'=>'借款期限|duration', 'value' => '', 'remark'=>''],
                    ['name'=>'unit', 'desc'=>'借款期限单位|unit', 'value' => '', 'remark'=>'1- 天|2- 月'],
                    ['name'=>'repayStyle', 'desc'=>'还款方式|repayStyle', 'value' => '', 'remark'=>'1- 按月付息|2- 到期付息|3- 按季度付息|4- 等额本息|(商户可选项以商户最终开通的一种或多种方式为准，如商户只开通按月付息，只能使用1)'],
                ],
            ],
            'trusteePay' => [
                'url' => '/page/trustee-pay',
                'param' => [
                    ['name'=>'dmId', 'desc'=>'大麦用户ID|dmId', 'value' => '', 'remark'=>''],
                    ['name'=>'orderId', 'desc'=>'发起借款返回的订单号|orderId', 'value' => '', 'remark'=>''],
                    ['name'=>'retUrl', 'desc'=>'前台跳转链接|retUrl', 'value' => 'http://120.24.68.184:9018', 'remark'=>''],
                    ['name'=>'notifyUrl', 'desc'=>'后台通知链接|notifyUrl', 'value' => 'http://120.24.68.184:9018', 'remark'=>''],
                ],
            ],
            'debtDetailsQuery' => [
                'url' => '',
                'param' => [
                    ['name'=>'dmId', 'desc'=>'大麦用户ID|dmId', 'value' => '', 'remark'=>''],
                    ['name'=>'orderId', 'desc'=>'发起借款返回的订单号|orderId', 'value' => '', 'remark'=>''],
                ],
            ],
            'debtRepayListQuery' => [
                'url' => '',
                'param' => [
                    ['name'=>'dmId', 'desc'=>'大麦用户ID|dmId', 'value' => '', 'remark'=>''],
                    ['name'=>'orderId', 'desc'=>'发起借款返回的订单号|orderId', 'value' => '', 'remark'=>''],
                ],
            ],
            'contract' => [
                'url' => '',
                'param' => [
                    ['name'=>'orderId', 'desc'=>'发起借款返回的订单号|orderId', 'value' => '', 'remark'=>''],
                ],
            ],
            'getCreditReport' => [
                'url' => '',
                'param' => [
                    ['name'=>'mobile', 'desc'=>'手机号|mobile', 'value' => '', 'remark'=>''],
                    ['name'=>'idNo', 'desc'=>'身份证号|idNo', 'value' => '', 'remark'=>''],
                    ['name'=>'realName', 'desc'=>'真实姓名|realName', 'value' => '', 'remark'=>''],
                ],
            ],
        ];
        if (isset($array[$service])) {
            $array[$service]['param'] = array_merge($arrayDefault, $array[$service]['param']);
            $res = $array[$service];
        }else{
            $res = ['url' => '', 'param' => []];
        }
        return json_encode($res);
    }

    public function actionGetSign()
    {
//        $json = '{"version" : "1", "service" : "accountOpen", "merchantId" : "100002",
//        "txDate" : "20171117", "txTime" : "091645", "seqNo" : "100004", "channel" : "000001",
//        "dmId" : "38", "cardNo" : "6259650803484515", "smsCode" : "111111",
//        "retUrl" : "http://172.16.1.181:8080/", "notifyUrl" : "xxxxx", "acqRes" : ""}';
//        $param = json_decode($json, true);
        $param = [
            "version" => "1",
            "service" => "register",
            "merchantId" => "100002",
            "txDate" => "20171117",
            "txTime" => "114720",
            "seqNo" => "100002",
            "channel" => "000001",
            "mobile" => "15259130959",
            "idNo" => "340123199005206921",
            "realName" => "张三",
            "creditLine" => "10000",
            "acqRes" => "",
        ];
        $key = LoanMerchant::getMerchantInfoById($param['merchantId'], 'key');
        echo ParameterUtil::getSignFromData($param, $key);
    }

    public function actionTest()
    {
        $sql1 = 'select * from xx_borrow WHERE bid = 4582';
        var_dump(Yii::$app->db->createCommand($sql1)->queryOne());
        $userApi = new UserApi();
        var_dump($userApi->balanceQuery(['accountId' => '6212461790000127145']));
        $redis = Yii::$app->redis;
        $redis->set('key', 'value');
        echo $redis->get('key');
        $redis->del('key');
        echo $redis->get('key');
    }
}
