<?php
/**
 * Created by PhpStorm.
 * User: Neo
 * Date: 2017/12/8
 * Time: 11:30
 */

namespace api\controllers;

use api\handler\api\AccountOpen;
use api\handler\api\SmsCodeApply;
use api\handler\api\TrusteePay;
use api\handler\base\ApiErrorMsgText;
use api\handler\service\CheckSetPassword;
use api\handler\service\CheckTrusteePay;
use api\handler\service\CheckUserInfo;
use common\lib\custody\CustodyApi;
use common\lib\Helps;
use common\models\LoanOrder;
use Yii;
use yii\base\ErrorException;

class IndexController extends MyController
{

    /**
     * 主页
     * @param $token
     * @return string
     * @throws ErrorException
     */
    public function actionIndex($token) {
        $orderId = Helps::getDecodeOrderId($token);
        if ($orderId) {
            $orderInfo = LoanOrder::findOne(['order_id' => $orderId]);
            if ($orderInfo && $orderInfo->status < LoanOrder::STATUS_COLLECTING) {
                $data = [
                    'orderId' => $orderId,
                    'dmId' => $orderInfo->borrower_id,
                    'token' => $token,
                ];
                return $this->renderPartial('index.html', $data);
            }
        }
        throw new ErrorException('debt register error - Token does not exist');
    }

    /**
     * 校验用户信息
     */
    public function actionCheckUserInfo()
    {
        $service = new CheckUserInfo();
        if ($service->setIsLocal(true)->getResult()) {
            $this->json_success($service->getData());
        } else {
            $this->json_error($service->getErrorCode(), $service->getErrorMsg(), $service->getData());
        }
    }

    /**
     * 发送短信验证码
     */
    public function actionSendSmsCode()
    {
        $service = new SmsCodeApply();
        if ($service->setIsLocal(true)->getResult()) {
            $this->json_success($service->getData(), '发送成功');
        } else {
            $this->json_error($service->getErrorCode(), $service->getErrorMsg());
        }
    }

    /**
     * 开通银行存管&设置交易密码
     * @return string
     */
    public function actionAccountOpen()
    {
        $request = Yii::$app->request;
        $token = Helps::getEncodeOrderId($request->post('orderId'));
        $accountOpen = new AccountOpen();
        if ($accountOpen->setIsLocal(true)->getResult()) {
            $v = [
                'txCode' => 'passwordSet',
                'idType' => '01',
                'retUrl' => $request->hostInfo . '/index/loading?token=' . $token .'&type=accountOpen',
            ];
            $v = array_merge($accountOpen->getData(), $v);
            $custodyApi= new CustodyApi(CustodyApi::CHANNEL_H5);
            $params=$custodyApi->submitForm($v);
            return $this->renderPartial('/page/loading.html',$params);
        }else{
            if ($accountOpen->getErrorCode() == 15024) {
                $this->redirect('/index/success?token=' . $token .'&type=accountOpen');
            }
            //返回错误页面
            $data = [
                'retCode' => $accountOpen->getErrorCode(),
                'retMsg' => $accountOpen->getErrorMsg(),
                'orderId' => $request->post('orderId'),
                'dmId' => $accountOpen->getPost('dmId'),
                'merchantId' => 0,
                'token' => $token,
                'type' => 'accountOpen',
            ];
            return $this->renderPartial('fail.html', $data);
        }
    }

    /**
     * 判断是否设置交易密码
     */
    public function actionCheckSetPassword()
    {
        $service = new CheckSetPassword();
        if ($service->setIsLocal(true)->getResult()) {
            $this->json_success($service->getData());
        } else {
            $this->json_error($service->getErrorCode(), $service->getErrorMsg(), $service->getData());
        }
    }
    /**
     * 受托支付确认
     * @return string
     */
    public function actionTrusteePay()
    {
        $trusteePay = new TrusteePay();
        $token = Helps::getEncodeOrderId($trusteePay->getPost('orderId'));
        if ($trusteePay->setIsLocal(true)->getResult()) {
            $data = $trusteePay->getData();
            $api = new \common\custody\BorrowApi(CustodyApi::CHANNEL_H5);
            $params = $api->trusteePay($data['user_id'], $data['bid'], 0, $token);
            return $this->renderPartial('/page/loading.html', $params);
        }else{
            //返回错误页面
            $data = [
                'retCode' => $trusteePay->getErrorCode(),
                'retMsg' => $trusteePay->getErrorMsg(),
                'orderId' => Yii::$app->request->post('orderId'),
                'dmId' => Yii::$app->request->post('orderId'),
                'merchantId' => $trusteePay->getPost('merchantId'),
                'token' => $token,
                'type' => 'trusteePay',
            ];
            return $this->renderPartial('fail.html', $data);
        }
    }

    /**
     * 判断是否成功提交订单
     */
    public function actionCheckTrusteePay()
    {
        $service = new CheckTrusteePay();
        if ($service->setIsLocal(true)->getResult()) {
            $this->json_success($service->getData());
        } else {
            $this->json_error($service->getErrorCode(), $service->getErrorMsg(), $service->getData());
        }
    }

    //校验成功
    public function actionSuccess($token, $type='accountOpen') {
        $orderId = Helps::getDecodeOrderId($token);
        if ($orderId) {
            $data = [
                'dmId' => 0,
                'orderId' => $orderId,
                'merchantId' => 0,
                'token' => $token,
                'type' => $type,
            ];
            $orderInfo = LoanOrder::getMerchantIdFromOrderId($orderId);
            if ($type == 'trusteePay' && $orderInfo['status'] < LoanOrder::STATUS_COLLECTING) {
                throw new ErrorException('debt register error - token error');
            }
            if ($orderInfo) {
                $data['dmId'] = $orderInfo['borrower_id'];
                $data['merchantId'] = $orderInfo['merchant_id'];
            }
            return $this->renderPartial('success.html', $data);
        } else {
            throw new ErrorException('debt register error - Token does not exist');
        }
    }

    //校验中
    public function actionLoading($token, $type='accountOpen') {
        $orderId = Helps::getDecodeOrderId($token);
        if ($orderId) {
            $data = [
                'dmId' => 0,
                'merchantId' => 0,
                'orderId' => $orderId,
                'token' => $token,
                'type' => $type,
            ];
            $orderInfo = LoanOrder::getMerchantIdFromOrderId($orderId);
            if ($orderInfo) {
                $data['dmId'] = $orderInfo['borrower_id'];
                $data['merchantId'] = $orderInfo['merchant_id'];
            }
            return $this->renderPartial('loading.html', $data);
        } else {
            throw new ErrorException('debt register error - Token does not exist');
        }
    }

    //校验失败
    public function actionFail($token, $type='accountOpen') {
        $request = Yii::$app->request;
        $orderId = Helps::getDecodeOrderId($token);
        if ($orderId) {
            $data = [
                'retCode' => $request->post('retCode', 18004),
                'retMsg' => $request->post('retMsg', ApiErrorMsgText::ERROR_18004),
                'dmId' => 0,
                'merchantId' => 0,
                'orderId' => $orderId,
                'token' => $token,
                'type' => $type,
            ];
            $orderInfo = LoanOrder::getMerchantIdFromOrderId($orderId);
            if ($type == 'trusteePay' && $orderInfo['status'] >= LoanOrder::STATUS_COLLECTING) {
                throw new ErrorException('debt register error - token error');
            }
            if ($orderInfo) {
                $data['dmId'] = $orderInfo['borrower_id'];
                $data['merchantId'] = $orderInfo['merchant_id'];
            }
            return $this->renderPartial('fail.html', $data);
        } else {
            throw new ErrorException('debt register error - token error');
        }
    }

    //银行协议
    public function actionAgreement() {
        return $this->renderPartial('agreement.html');
    }
    
    //用户协议
    public function actionUser_agreement() {
        return $this->renderPartial('user_agreement.html');
    }
}