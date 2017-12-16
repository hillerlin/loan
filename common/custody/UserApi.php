<?php

/*
 * To change this license header] =  choose License Headers in Project Properties.
 * To change this template file] =  choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\custody;

use api\handler\base\ApiErrorMsgText;
use Yii;
use common\lib\custody\CustodyApi;

/**
 * Description of UserApi
 * 用户处理类
 * 只做数据封装，不做逻辑处理
 * @author Administrator
 * @datetime 2017-3-9 16:26:20
 */
class UserApi extends CustodyApi {

    /**
     * 获取上一次发送验证码的服务码
     * @param type $user_id
     * @return type
     */
    public static function getLastSrvAuthCode($user_id) {
        return Yii::$app->redis->GET($user_id . ':srvAuthCode');
    }

    //开户增强
    public function accountOpenPlus($params) {
        $data["txCode"] = "accountOpenPlus";
        $data["idType"] = "01";
        $data["idNo"] = $params['id_card'];
        $data["name"] = $params['real_name'];
        $data["mobile"] = $params['mobile'];
        $data["cardNo"] = $params['bank_card'];
        $data["email"] = "";
        $data["acctUse"] = isset($params['acctUse']) ? $params['acctUse'] : "00000";
        $data['lastSrvAuthCode'] = self::getLastSrvAuthCode($params['user_id']);
        if (empty($data['lastSrvAuthCode'])) {
            $this->errNo = 15006;
            $this->errMsg = ApiErrorMsgText::ERROR_15006;
            return false;
        }
        $data['smsCode'] = $params['smsCode'];
        $data["acqRes"] = "";
        return $this->submitApi($data);
    }

    /**
     * 请求发送短信验证码
     * @param array $params
     * @return bool|array
     */
    public function smsCodeApply($params) {
        $data["txCode"] = "smsCodeApply";
        $data["srvTxCode"] = $params['srvTxCode'];
        $data["mobile"] = $params['mobile'];
        return $this->submitApi($data);
    }

    //形成form表单
//    public function passwordSet($params) {
//        $data["txCode"] = "passwordSet";
//        $data["idType"] = "01";
//        $data["accountId"] = $params['accountId'];
//        $data["idNo"] = $params['real_card'];
//        $data["name"] = $params['username'];
//        $data["mobile"] = $params['real_phone'];
//        $data["cardNo"] = $params['card_bid'];
//        //默认同一地址
////        $data["notifyUrl"] = $params['notifyUrl'];
//        $data["retUrl"] = $params['retUrl'];
//        return $this->submitForm($data);
//    }

    //设置密码表单
    public function passwordSet($params) {
        $keys = ['accountId', 'idNo', 'name', 'mobile', 'cardNo', 'retUrl'];
        $data = $this->map($keys, $params);
        //如果有短信验证码就是重置
        $data["txCode"] = isset($params['smsCode']) ? 'passwordResetPlus' : "passwordSet";
        if ($data["txCode"] == 'passwordResetPlus') {
            $data["smsCode"] = $params['smsCode'];
            $data["lastSrvAuthCode"] = $params['srvTxCode'];
        }
        $data["idType"] = "01";
        //默认同一地址
        //$data["notifyUrl"] = $params['notifyUrl'];
        return $this->submitForm($data);
    }
    
    //设置密码表单
    public function withdraw($params) {
        $keys = ['accountId', 'accountId', 'idNo', 'name', 'mobile', 'cardNo', 'retUrl', 'txAmount', 'txFee'];
        $data = $this->map($keys, $params);
        //如果有短信验证码就是重置
        $data["txCode"] = isset($params['smsCode']) ? 'passwordResetPlus' : "passwordSet";
        $data["idType"] = "01";
        //忘记密码跳转地址必填
        $data["forgotPwdUrl"] = "";
        //默认同一地址
        //$data["notifyUrl"] = $params['notifyUrl'];
        return $this->submitForm($data);
    }
    
    //查询银行卡余额
    public function balanceQuery($params) {

        $data["accountId"] = $params['accountId'];
        $data["txCode"] = "balanceQuery";
        return $this->submitApi($data);
    }
    //发送红包底层
    public function sendVoucherPay($params){
        //平台的红包账号
        $data['accountId']=$this->config['red']['accountId'];
        $data['forAccountId']=$params['accountId'];
        $data['txAmount']=$params['txAmount'];
        $data['desLineFlag']='1';
        if($params['desLine']){
            $data['desLine']=$params['desLine'];
        }else{
            $data['desLine']='平台红包';
        }
        $data["txCode"] = "voucherPay";
        return $this->submitApi($data);
    }
    //根据accountId查询相关信息
    public function mobileMaintainace($params){
        $data['accountId']=$params['accountId'];
        $data['option']='0';//0为查询
        $data['txCode']='mobileMaintainace';
    }
    
}
