<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\forms\OrderForm;
use common\models\forms\RegisterForm;

/**
 * TrusteePay
 * 2.3.2 借款人确认受托支付
 */
class TrusteePayPlus extends ApiBase
{
    public function getResult()
    {
        return $this->save();
    }

    private function save()
    {
        $rules = [
            ['dmId', true,  'number'],
            ['retUrl', true],
            ['notifyUrl', true],
        ];

        if($this->verify($rules)){
            //判断用户是否存在
            $info = [
                'borrower_id' => $this->getParameters('dmId'),
                'merchant_id' => $this->getPost('merchantId')
            ];
            if (!($borrowerInfo = \common\models\LoanBorrower::findOne($info))) {
                $this->setErrorCode(15001)->setErrorMsg(ApiErrorMsgText::ERROR_15001);
                return false;
            }
            $data = $this->getPost();
//            var_dump($data);exit;
            $orderForm = new OrderForm(['scenario' => OrderForm::SUBMIT_ORDER_AND_CONFIRM]);
            if (!$orderForm->load($data, '') || !$orderForm->save()) {
                $this->setErrorCode($orderForm->getErrorCode())->setErrorMsg($orderForm->getFirstError()?:'');
                return false;
            }
            $result = [
                'user_id' => $borrowerInfo->user_id,
                'bid' => $orderForm->bid,
                'retUrl' => $data['retUrl'],
            ];
            $this->setData($result);
            return true;
        }
        return false;
    }
    
}