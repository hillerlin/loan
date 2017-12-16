<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pc\modules\custody\controllers;

use Yii;
use common\lib\custody\CustodyApi;
use pc\controllers\UserController;
use common\models\AutoBidauth;
use common\models\AccountPayment;
use common\models\Borrow;
/**
 * Description of CallbackController
 * 银行前台回调页面，现在默认需要登录，以后可扩展用于app\h5
 * @author Administrator
 * @datetime 2017-5-6 17:34:17
 */
class CallbackController extends UserController{
    public $enableCsrfValidation = false;       //接收外部数据，不做csrf校验
    
    //银行回调页面
    public function actionIndex() {
        $data = parent::web_info();
        $request = Yii::$app->request;
        $txCode = $request->get('txCode');
        $orderId = $request->get('orderId');
        if (empty($orderId)) {
            throw new \yii\web\BadRequestHttpException("您找错页面了！");
        }
        $params = $this->process($txCode, $orderId);
//        var_dump($params);exit;
        $data = array_merge($data, $params);
        $data['txCode'] = $txCode; //业务标识码
        return $this->render('index.html', $data);
    }
    
    public function process($txCode, $orderId) {
        //充值，提现，自动投标签约，密码设置，密码重置，借款人受托支付申请确认
        if (!in_array($txCode, ['directRechargePlus', 'withdraw', 'autoBidAuthPlus', 'passwordSet', 'passwordReset', 'trusteePay'])) {
            throw new \yii\web\BadRequestHttpException("您找错页面了！");
        }
        switch ($txCode) {
            case 'withdraw'://提现
            case 'directRecharge'://充值
            case 'directRechargePlus'://充值
                    $params = $this->$txCode($orderId);
                    break;
                case 'autoBidAuthPlus':
                    $autobidStatus = AutoBidauth::getInfobyOrderId($orderId);
                    $params['status'] = $autobidStatus;
                    break;
                case 'passwordSet':
                case 'passwordReset':
                    $params['status'] = 1;
                    break;
                default:
                    break;
        }
        return $params;
    }
    
    protected function withdraw($orderId) {
        $accountPaymentInfo = AccountPayment::findOne(['order_no' => $orderId])->toArray();
        $paymentStatus = $accountPaymentInfo['status'];
        $paymentStatus == 2 ? $params['status'] = AccountPayment::STATUS_SUCCESS : ($paymentStatus == 1 ? $params['status'] = AccountPayment::STATUS_WAIT : $params['status'] = AccountPayment::STATUS_FAILED);
        $params['txAmount'] = $accountPaymentInfo['at_money']; //提现金额
        $params['userMoney'] = $accountPaymentInfo['account']; //对应的账户总额
        $params['borrow_recommend'] = Borrow::getIndexBorrow(Borrow::MTJH_BL . ',' . Borrow::MTJH_GYL, 0, 2);   //推荐标
        return $params;
    }
    
    protected function directRechargePlus() {
        
        $params = Yii::$app->request->post();
        //如果数据为空，抛出404
        if (empty($params)) {
            throw new \yii\web\BadRequestHttpException("您找错页面了！");
        }
        $custody = new CustodyApi();
        //校验数据
        if ($custody->validateSign($params) === false) {
            throw new \yii\web\BadRequestHttpException("您找错页面了！");
        }
        //处理是否成功
        $params['is_success'] = $custody->isSuccess();
        //去数据库查询该订单号的状态
        $accountPaymentInfo = AccountRecharge::findOrderByOrderNo($params['seqNo']);
        $params['status'] = $accountPaymentInfo['status']; //AccountPayment::STATUS_SUCCESS;
        $params['userMoney'] = UserAccount::getAllMoney($accountPaymentInfo['user_id'])['use_money_custody'];
        return $params;
    }
}
