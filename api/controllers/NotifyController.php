<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace api\controllers;

use common\lib\Queue;
use common\models\LoanOrder;
use common\models\LoanPacket;
use Yii;
use yii\web\Controller;
use common\lib\custody\CustodyApi;
use common\models\User;
use common\custody\ResponeCode;

/**
 * Description of NotifyController
 *
 * @author Administrator
 * @datetime 2017-3-14 11:12:14
 */
class NotifyController extends Controller {

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
        $user_model = User::findByAccountId($params['accountId']);
        if ($params['retCode'] == ResponeCode::SUCCESS) {
            //没有设置过密码
            if ($user_model->set_pwd == 0) {
                $user_model->set_pwd = 1;
                if (!User::updateInfo('user_id=' .$user_model->user_id, ['set_pwd' => 1])) {
                    Yii::error('密码设置修改失败'.$user_model->user_id, 'notify');
                }
            }
        }
        $this->formatNotifyMerchantData($params, ['dmId']);
    }

    //受托支付回调
    protected function trusteePay($event) {
        $params = $event->data;
        //处理不成功
        if ($params['is_success']) {
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();
            $borrow = new \common\models\Borrow();
            $resultBorrow = $borrow->updateAll(['is_entrusted' => 2, 'status' => 2], 'bid='.$params['productId']);
            $resultOrder = LoanOrder::updateInfo(['borrow_id' => $params['productId']], ['status'=>LoanOrder::STATUS_COLLECTING]);
            if (!$resultBorrow || !$resultOrder) {
                $transaction->rollBack();
                Yii::error('trusteePay: bid-' . $params['productId'], 'notify');
            }
            $transaction->commit();
        } else {
            Yii::error('trusteePay: bid-' . $params['productId'] .'errmsg-' . $params['retMsg'], 'notify');
        }
        $this->formatNotifyMerchantData($params, ['orderId']);
    }

    private function formatNotifyMerchantData($params, $inherit)
    {
        $logId = $params['acqRes'];
        if ($logId) {
            $loanPacketModel = new LoanPacket();
            $callBackData = [
                'retCode' => $params['retCode'],
                'retMsg' => $params['retMsg'],
            ];
            $notifyData = $loanPacketModel->formatNotifyData($logId, $callBackData, $inherit);
            $loanPacketModel->notify($notifyData);;
        }
    }

}
