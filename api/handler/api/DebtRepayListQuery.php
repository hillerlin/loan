<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\BorrowCollection;
use common\models\LoanBorrower;
use common\models\LoanOrder;

/**
 * DebtRepayListQuery
 * 2.4.2 借款人还款计划表
 */
class DebtRepayListQuery extends ApiBase
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
            $borrowCollectionList = [];
            if ($orderInfo->borrow_id) {
                $borrowCollectionModel = new BorrowCollection();
                $borrowCollectionList = $borrowCollectionModel->formatDebtSchedule($borrowCollectionModel->getDebtScheduleFromBid($orderInfo->borrow_id));
            }
            if ($borrowCollectionList == []) {
                $borrowCollectionList = ['subPacks' => [], 'repaymentAmt' => '0.00', 'repaymentInt' => '0.00'];
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
            $this->setData(array_merge($data, $borrowCollectionList));
            return true;
        }
        return false;
    }
}