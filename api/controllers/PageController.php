<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace api\controllers;

use api\handler\api\AccountOpen;
use api\handler\api\TrusteePay;
use api\handler\api\TrusteePayPlus;
use common\lib\custody\CustodyApi;
use common\lib\Queue;
use common\models\LoanPacket;
use Yii;

/**
 * Description of HomeController
 *
 * @author Administrator
 * @datetime 2017-2-23 11:46:25
 */
class PageController extends MyController {

    public function actions() {
        return [
            //错误页面
            'error' => [
                'class' => 'yii\web\ErrorAction',
                'view' => 'error.html'
            ],
        ];
    }

    //受托支付跳转
    public function actionTrusteePayPlus() {
        $trusteePay = new TrusteePayPlus();
        if ($trusteePay->getResult()) {
            $data = $trusteePay->getData();
            $api = new \common\custody\BorrowApi($data['channel']);
            $params = $api->trusteePay($data['user_id'], $data['bid'], $data['retUrl']);
            echo $this->renderPartial('loading.html', $params);
        }else{
            $data = [
                'retCode' => $trusteePay->getErrorCode(),
                'retMsg' => $trusteePay->getErrorMsg(),
                'orderId' => Yii::$app->request->post('orderId'),
            ];
            $loanPacketModel = new LoanPacket();
            $notifyData = $loanPacketModel->formatNotifyData($trusteePay->getPacketLogId(), $data);
            $loanPacketModel->notify($notifyData);;
            $data['retUrl'] = Yii::$app->request->post('retUrl');
            return $this->renderPartial('error.html', $data);
        }
    }

    //受托支付跳转
    public function actionTrusteePay() {
        $trusteePay = new TrusteePay();
        if ($trusteePay->getResult()) {
            $data = $trusteePay->getData();
            $api = new \common\custody\BorrowApi($trusteePay->getPost('channel'));
            $params = $api->trusteePay($data['user_id'], $data['bid'], $trusteePay->getPacketLogId());
            echo $this->renderPartial('loading.html', $params);
        }else{
            //返回错误页面
            $data = [
                'retCode' => $trusteePay->getErrorCode(),
                'retMsg' => $trusteePay->getErrorMsg(),
                'orderId' => Yii::$app->request->post('orderId'),
            ];
            if ($trusteePay->getInherit()) {
                $loanPacketModel = new LoanPacket();
                $notifyData = $loanPacketModel->formatNotifyData($trusteePay->getPacketLogId(), $data);
                $loanPacketModel->notify($notifyData);
            }
            $data['retUrl'] = Yii::$app->request->post('retUrl');
            return $this->renderPartial('error.html', $data);
        }
    }

    /**
     * 开通存管
     * @return bool|string
     */
    public function actionAccountOpen() {
        $request = Yii::$app->request;
        $accountOpen = new AccountOpen();
        if ($accountOpen->getResult()) {
            $v = [
                'txCode' => 'passwordSet',
                'idType' => '01',
                'retUrl' => $request->hostInfo . '/page/ret-url?log='.$accountOpen->getPacketLogId(),
            ];
            $v = array_merge($v, $accountOpen->getData());
            $custodyApi= new CustodyApi($accountOpen->getPost('channel'));
            $params=$custodyApi->submitForm($v);
            return $this->renderPartial('loading.html',$params);
        }else{
            //返回错误页面
            $data = [
                'retCode' => $accountOpen->getErrorCode(),
                'retMsg' => $accountOpen->getErrorMsg(),
                'dmId' => $accountOpen->getPost('dmId')
            ];
            if ($accountOpen->getInherit()) {
                $loanPacketModel = new LoanPacket();
                $notifyData = $loanPacketModel->formatNotifyData($accountOpen->getPacketLogId(), $data);
                $loanPacketModel->notify($notifyData);
            }
            $data['retUrl'] = $accountOpen->getPost('retUrl');
            return $this->renderPartial('error.html', $data);
        }
    }

    public function actionRetUrl()
    {
//        var_dump(Yii::$app->request->post());
//        var_dump(Yii::$app->request->get());
//        exit();
        $logId = Yii::$app->request->get('log');
        if ($logId && $packet = LoanPacket::findIdentity($logId)){
            $packet =LoanPacket::findIdentity($logId)->packet;
            $requestData = json_decode($packet, true);
            $retUrl = isset($requestData['retUrl']) ? $requestData['retUrl'] : '';
            $this->redirect($retUrl);
        }else{
            $this->redirect('');
        }
    }
}
