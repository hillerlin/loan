<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\LoanBorrower;
use common\models\LoanOrder;

/**
 * DebtDetailsQuery
 * 2.4.1 借款人还款计划表
 */
class DebtDetailsQuery extends ApiBase
{
    public function getResult()
    {
        return $this->save();
    }

    private function save()
    {
        $rules = [
            ['dmId', true,  'number'],
            ['orderId', true],
        ];

        if($this->verify($rules)){
            //判断用户是否存在
            $loanBorrowerModel = new LoanBorrower();
            if (!($loanBorrowerModel->findByIdAndMerchantId($this->getParameters('dmId'),$this->getPost('merchantId')))) {
                $this->setErrorCode(15001)->setErrorMsg(ApiErrorMsgText::ERROR_15001);
                return false;
            }
            if (!($orderInfo = LoanOrder::findByIdAndBorrowerId($this->getParameters('orderId'), $this->getParameters('dmId')))) {
                $this->setErrorCode(15101)->setErrorMsg(ApiErrorMsgText::ERROR_15101);
                return false;
            }
            $data = [
                'orderId' => $this->getParameters('orderId'),
                'amount' => $orderInfo->amount,
                'duration' => $orderInfo->duration,
                'unit' => $orderInfo->unit,
                'repayStyle' => $orderInfo->repay_style,
                'state' => LoanOrder::getStateFromStatus($orderInfo->status),
                'rate' => $orderInfo->rate,
            ];
            $this->setData($data);
            return true;
        }
        return false;
    }
}