<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\custody;

use Yii;
use common\lib\custody\CustodyApi;
use common\models\EnterUsers;

/**
 * Description of TenderApi
 *
 * @author Administrator
 * @datetime 2017-3-29 16:11:41
 */
class BorrowApi extends CustodyApi {

    const BATCH_STATUS_A = 'A';     //待处理
    const BATCH_STATUS_D = 'D';     //处理中
    const BATCH_STATUS_S = 'S';     //处理结束
    const BATCH_STATUS_F = 'F';     //处理失败
    const BATCH_STATUS_C = 'C';     //已撤销

    /**
     * 返回状态描述
     * @param type $status
     * @return string
     */

    public static function batchStatusDesc($status = null) {
        $desc = [BorrowApi::BATCH_STATUS_A => '待处理', BorrowApi::BATCH_STATUS_D => '处理中', BorrowApi::BATCH_STATUS_S => '处理结束', BorrowApi::BATCH_STATUS_F => '处理失败', BorrowApi::BATCH_STATUS_C => '已撤销'];
        if ($status === null) {
            return $desc;
        }
        return isset($desc[$status]) ? $desc[$status] : '';
    }

    //项目登记
    public function debtRegister($borrowinfo) {
        if (empty($borrowinfo['auto_time'])) {
            $borrowinfo['auto_time'] = $borrowinfo['addtime'];
        }
        $data['raiseDate'] = date('Ymd', $borrowinfo['auto_time']);
        if (empty($data['raiseDate'])) {
            $data['raiseDate'] = date('Ymd', time());
        }
        $data['accountId'] = (string)$borrowinfo['accountId'];
        $data['productId'] = (string)$borrowinfo['bid'];
        $data['productDesc'] = $borrowinfo['bid'] . "_" . $borrowinfo['contract_no'];
        $valid_time = $borrowinfo['valid_time'];
        $data['raiseEndDate'] = date('Ymd', strtotime($data['raiseDate']) + $valid_time * 3600 * 24);
        if ($borrowinfo['repay_style'] == 2) {
            $data['intType'] = '0';
        } else {
            $data['intType'] = '2';
        }
        if ($borrowinfo['if_attorn'] == 2) {
            $data['duration'] = $borrowinfo['limit_time'];
        } else {
            $data['duration'] = $borrowinfo['limit_time'] * 30;
        }
        //担保标
        if ($borrowinfo['is_entrusted']) {
            $data['entrustFlag'] = (string)$borrowinfo['is_entrusted'];
            $danbao = EnterUsers::getGuaranteeInfoById($borrowinfo['receipt_id'], 'u.accountId');
            $data['receiptAccountId'] = (string)$danbao['accountId'];
        }
        $data['duration'] = "{$data['duration']}";
        $data['txAmount'] = (string)$borrowinfo['account'];
        $data['rate'] = (string)$borrowinfo['apr'];
        $data['txFee'] = (string)$borrowinfo['Intermediary_fee'];

        $data["txCode"] = "debtRegister";

        return $this->submitApi($data);
    }

    //项目撤销
    public function debtRegisterCancel($borrowinfo) {
        $data['accountId'] = $borrowinfo['accountId'];
        $data['productId'] = $borrowinfo['bid'];
        if (empty($borrowinfo['auto_time'])) {
            $borrowinfo['auto_time'] = $borrowinfo['addtime'];
        }
        $data['raiseDate'] = date('Ymd', $borrowinfo['auto_time']);
        if (empty($data['raiseDate'])) {
            $data['raiseDate'] = date('Ymd', time());
        }

        $data["txCode"] = "debtRegisterCancel";
        return $this->submitApi($data);
    }

    //放款
    public function lendPay($params) {

        $data = $params;
        $data['txCode'] = 'lendPay';
        return $this->submitApi($data);
    }

    //放款 还款撤销
    public function lendPaych($params) {
        //需要找出原交易的时间，进行处理
        $data = $params;
        $data['txCode'] = 'payCancel';
        return $this->submitApi($data);
    }

    //放款 还款状态查询
    //不能查询批次，只是封装的单个订单信息的查询
    public function lendPaycx($params, $reqTxCode, $reqOrderId) {
        $data = $params;
        $data['reqType'] = "2";
        $data['reqTxCode'] = $reqTxCode;
        $data['reqOrderId'] = $reqOrderId;
        $data['txCode'] = 'transactionStatusQuery';
        return $this->submitApi($data);
    }

    //还款
    public function repay($params) {
        $data = $params;
        $data['txCode'] = 'repay';
        return $this->submitApi($data);
    }

    /**
     * 批次放款接口
     * 投资人投标以后，P2P平台通过本交易申请将资金从投资人电子账户划转到融资人电子账户，实际生效的时间视银行处理情况而定。
     */
    public function batchLendPay($params) {
        $data = $params;
        $data['notifyURL'] = $this->config['sys']['notifyUrl'];
        $data['retNotifyURL'] = $this->config['sys']['notifyUrl'];
        $data["txCode"] = "batchLendPay";
        $this->setSendType(1);
        return $this->submitApi($data);
    }

    /**
     * 批次还款的接口
     * 功能说明：融资人向投资人还款，P2P平台通过本交易申请将资金从融资人电子账户划转到投资人电子账户，实际生效的时间视银行处理情况而定，支持多笔交易，同一个批次号的交易一起处理，但是可能仅部分交易成功。后台收到请求以后，
     * 同步回应接收结果，异步通知请求方报文收取和合法性判断的结果（P2P平台收到后回应success表示收到异步通知），业务处理也异步通知到相应的URL（P2P平台收到后回应success表示收到异步通知），或者请求方可以主动查询。
     * 本交易不会主动冻结融资人资金，如需要先冻结，请调用“资金冻结”接口。
     */
    public function batchRepay($params) {

        $data = $params;
        $data['notifyURL'] = $this->config['sys']['notifyUrl'];
        $data['retNotifyURL'] = $this->config['sys']['notifyUrl'];

        $data["txCode"] = "batchRepay";
        $this->setSendType(2);
        return $this->submitApi($data);
    }

    /**
     * 批次结束债券
     */
    public function batchCreditEnd($params) {
        $data = $params;
        $data['notifyURL'] = $this->config['sys']['notifyUrl'];
        $data['retNotifyURL'] = $this->config['sys']['notifyUrl'];

        $data["txCode"] = "batchCreditEnd";
        $this->setSendType(3);
        return $this->submitApi($data);
    }

    /**
     * 查询交易状态
     */
    public function batchQuery($params) {
        $data = $params;
        $data['txCode'] = 'batchQuery';
        return $this->submitApi($data);
    }

    /* 还款资金冻结 */

    public function balanceFreeze($params) {
        $data = $params;
        $data['txCode'] = 'balanceFreeze';
        return $this->submitApi($data);
    }

    /**
     * 查询交易明细
     */
    public function batchDetailsQuery($params) {
        $data = $params;
        $data['type'] = "0";
        $data['txCode'] = 'batchDetailsQuery';
        return $this->submitApi($data);
    }

    /**
     * 受托支付借款人确认接口
     * @param $user_id
     * @param $bid
     * @param $logId
     * @param string $token
     * @return array
     */
    public function trusteePay($user_id, $bid, $logId, $token='') {
        $user = new \common\models\User();
        $debtor = $user->getByPk('accountId, real_card, gtuser', $user_id);
        $guarantor = EnterUsers::getGuaranteeInfoByBId($bid, 'u.accountId');

        $data['idType'] = '01';
        $idType = $debtor['gtuser'];
        if ($idType == 20 || $idType == 25) {
            $data['idType'] = $idType;
        }
        $data['accountId'] = $debtor['accountId'];
        $data['receiptAccountId'] = $guarantor['accountId'];
        $data['productId'] = $bid;
        $data['idNo'] = $debtor['real_card'];
        $data['forgotPwdUrl'] = $this->config['sys']['forgotPwdUrl'];
        $data["txCode"] = "trusteePay";
        $data["acqRes"] = $logId;
        if ($logId) {
            $data['retUrl'] = Yii::$app->request->hostInfo . '/page/ret-url?log=' . $logId;
        }else{
            $data['retUrl'] = Yii::$app->request->hostInfo . '/index/loading?token=' . $token .'&type=trusteePay';
        }
        return $this->submitForm($data);
    }

}
