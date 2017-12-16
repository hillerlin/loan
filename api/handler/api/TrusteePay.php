<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\forms\OrderForm;
use common\models\LoanBorrower;

/**
 * trusteePay
 * 2.3.2 借款人确认受托支付
 */
class TrusteePay extends ApiBase
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
            ['retUrl', true],
            ['notifyUrl', true],
        ];

        if($this->verify($rules)){
            //判断用户是否存在
            $loanBorrowerModel = new LoanBorrower();
            if ($this->getIsLocal()) {
                $borrowerInfo = LoanBorrower::findIdentity($this->getParameters('dmId'));
            }else {
                if (!($borrowerInfo = $loanBorrowerModel->findByIdAndMerchantId($this->getParameters('dmId'), $this->getPost('merchantId')))) {
                    $this->setErrorCode(15001)->setErrorMsg(ApiErrorMsgText::ERROR_15001);
                    return false;
                }
            }
            $data = $this->getPost();

            $orderForm = new OrderForm(['scenario' => OrderForm::CONFIRM_ORDER]);
            if (!$orderForm->load($data, '') || !$orderForm->confirmOrder()) {
                $this->setErrorCode($orderForm->getErrorCode())->setErrorMsg($orderForm->getFirstError()?:'');
                return false;
            }
            $result = [
                'user_id' => $borrowerInfo->user_id,
                'bid' => $orderForm->bid,
            ];
            $this->setData($result);
            return true;
        }
        return false;
    }
}