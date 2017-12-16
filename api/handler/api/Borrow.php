<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\forms\OrderForm;
use common\models\LoanBorrower;

/**
 * Created by PhpStorm.
 * User: Neo
 * Date: 2017/11/9
 * Time: 20:13
 */
class Borrow extends ApiBase
{
    public function getResult()
    {
        return $this->save();
    }

    private function save()
    {
        $rules = [
            ['merchantId', true, 'number'],
            ['dmId', true, 'number'],
            ['amount', true],
            ['duration', true, 'number'],
            ['unit', true, 'enum'],
            ['repayStyle', true, 'enum'],
            ['merchantOrderNum', false, '']
        ];

        if($this->verify($rules)){
            $data = $this->getParameters();
            $borrower_id = $this->getParameters('dmId');
            $merchant_id = $this->getParameters('merchantId');
            $loanBorrower = new LoanBorrower();
            if (!($loanBorrower->findByIdAndMerchantId($borrower_id, $merchant_id))) {
                $this->setErrorCode(15001)->setErrorMsg(ApiErrorMsgText::ERROR_15001);
            }
//            $data['user_id'] = $loanBorrower->user_id;
            $orderForm = new OrderForm(['scenario' => OrderForm::SUBMIT_ORDER]);
            if (!$orderForm->load($data, '') || !$orderForm->create()) {
                $this->setErrorCode($orderForm->getErrorCode())->setErrorMsg($orderForm->getFirstError() ? : '');
                return false;
            }
            //基本信息
            $params['orderId'] = $orderForm->orderId;
            $this->setData($params);
            return true;
        }
        return false;
    }

}