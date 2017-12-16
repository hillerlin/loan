<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pc\modules\custody\controllers;

use common\models\Normallucky;
use Yii;
use common\models\UserAccount;
use yii\web\Controller;
use common\lib\custody\CustodyApi;
use common\models\User;
use common\custody\ResponeCode;
use common\models\CommonAuthSql;
use common\models\Redenvelope;
use common\models\Borrowfix;

/**
 * Description of NotifyController
 *
 * @author Administrator
 * @datetime 2017-3-14 11:12:14
 */
class NotifyController extends Controller {

    //put your code here
    const EVENT_AFTER_SUCCESS = 'afterSuccess';
    public $enableCsrfValidation = false;       //接收外部数据，不做csrf校验

    public function actionIndex() {
        $bgData = Yii::$app->request->post('bgData');
        Yii::info('original data: ' . $bgData, 'notify');
        $params = json_decode($bgData, true);
        if (empty($params)) {
            exit;
        }
        $custody = new CustodyApi();
        //校验数据
        if ($custody->validateSign($params) === false) {
            exit;
        }
        //处理是否成功
        if ($custody->isSuccess() === true) {
            $params['is_success'] = true;
        } else {
            $params['is_success'] = false;
        }
        Yii::info('decode data: ' . $bgData, 'notify');
        //业务处理
        $this->on(self::EVENT_AFTER_SUCCESS, [$this, $params['txCode']], $params);
        $this->trigger(self::EVENT_AFTER_SUCCESS);
        //接受到以后不管业务处理都要返回
        echo 'success';
    }

    //密码设置
    protected function passwordSet($event) {
        $params = $event->data;
        if ($params['retCode'] != ResponeCode::SUCCESS) {
            return;
        }
        $user_model = User::findByAccountId($params['accountId']);
        //没有设置过密码
        if ($user_model->set_pwd == 0) {
            $user_model->set_pwd = 1;
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();
            try {
                User::updateInfo('user_id=' .$user_model->user_id, ['set_pwd' => 1]);
                $redenvelope = new Redenvelope();
                $redenvelope->sendSpecify($user_model->user_id, \common\lib\RedEnvelopesUtility::ACTION_SET_TRAS_PWD);
                $transaction->commit();
            } catch (Exception $exc) {
                Yii::error('密码设置修改失败'.$exc->getMessage(), 'notify');
                $transaction->rollBack();
            }
        }
    }

    //提现回调
    protected function withdraw($event) {
        $params = $event->data;
        $userObj = User::findByAccountId($params['accountId']);
        $result = UserAccount::updateUserInfo($userObj->user_id, '3', $params);
/*        if ($result) {
            echo 'success';
        }*/
    }

    //线上充值回调
    protected function directRecharge($event) {
        $params = $event->data;
        $userObj = User::findByAccountId($params['accountId']);
        $db = Yii::$app->db;
        try {
            UserAccount::updateUserInfo($userObj->user_id, '6', $params);
            //首次投资送红包
            if (\common\models\AccountRecharge::isFirst($userObj->user_id)) {
                $redenvelope = new Redenvelope();
                $redenvelope->sendSpecify($userObj->user_id, \common\lib\RedEnvelopesUtility::ACTION_FIRST_RECHARGE);
                Normallucky::sendTicketsToUser($userObj->user_id, 1, 9);
            }
        } catch (Exception $exc) {
            Yii::error('回调订单状态修改失败'.$exc->getMessage(), 'notify');
        }
    }

    protected function offlineRechargeCall($event)
    {
        $params = $event->data;
        $userObj = User::findByAccountId($params['payAccountId']);
        $db = Yii::$app->db;
        try {
            UserAccount::updateUserInfo($userObj->user_id, '6', $params);
            //首次投资送红包
            if (\common\models\AccountRecharge::isFirst($userObj->user_id)) {
                $redenvelope = new Redenvelope();
                $redenvelope->sendSpecify($userObj->user_id, \common\lib\RedEnvelopesUtility::ACTION_FIRST_RECHARGE);
                Normallucky::sendTicketsToUser($userObj->user_id, 1, 9);
            }
        } catch (Exception $exc) {
            Yii::error('回调订单状态修改失败'.$exc->getMessage(), 'notify');
        }
    }

    //自动投标签约回调
    protected function autoBidAuthPlus($event) {
        $params = $event->data;
        if ($params['is_success'] === true) {
            $status = \common\models\AutoBidauth::STAUTS_SUCCESS;
        } else {
            $status = \common\models\AutoBidauth::STAUTS_FAILED;
        }
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
//        var_dump($transaction->level);exit;
        //更新订单
        $autoBidauth = new \common\models\AutoBidauth();
        if (!$autoBidauth->updateStatus($params['orderId'], $status)) {
            $transaction->rollBack();
            return;
        }
        if ($params['is_success'] === true) {
            //成功才更新用户状态
            $user = new User();
            if ($params['is_success'] && !$user->updateInfo(['accountId' => $params['accountId']], ['auto_bid' => 1])) {
                $transaction->rollBack();
                return;
            }
        }
        $transaction->commit();
    }
    
    //批次放款回调
    protected function batchLendPay($event){
        $params = $event->data;
        $result = Borrowfix::realLendPayO($params);
        if ($result) {
            echo 'success';
        }
    }

    //批次还款回调
    protected function batchRepay($event){
        $params = $event->data;
        $result = Borrowfix::realRepay($params);
        if ($result) {
            echo 'success';
        }
    }
    //批次还款回调
    protected function batchCreditEnd($event){
        $params = $event->data;
        $result = Borrowfix::realCreditEnd($params);
        if ($result) {
            echo 'success';
        }
    }
    
    //受托支付回调
    protected function trusteePay($event) {
        $params = $event->data;
        //处理不成功
        if ($params['is_success']) {
            $borrow = new \common\models\Borrow();
            $result = $borrow->updateAll(['is_entrusted' => 2, 'status' => 2], 'bid='.$params['productId']);
            $borrow_info = $borrow->getBorrowByBid($params['productId']);
            $borrow->delDebtor($borrow_info['to_userid']);
            if (!$result) {
                Yii::error('trusteePay: bid-' . $params['productId'], 'notify');
            }
        } else {
            Yii::error('trusteePay: bid-' . $params['productId'] .'errmsg-' . $params['retMsg'], 'notify');
        }
    }
}
