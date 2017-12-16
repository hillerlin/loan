<?php

namespace api\handler\service;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\LoanOrder;

class CheckTrusteePay extends ApiBase
{
    public function getResult()
    {
        return $this->get();
    }

    private function get()
    {
        $rules = [
            ['orderId', true,  'number'],
        ];

        if($this->verify($rules)){
            if ($orderInfo = LoanOrder::findOne($this->getParameters('orderId'))) {
                if ($orderInfo->status < LoanOrder::STATUS_COLLECTING) {
                    $this->setErrorCode(18002)->setErrorMsg(ApiErrorMsgText::ERROR_18002);
                    $this->setData(['id' => $this->getParameters('orderId')]);
                    return false;
                }
            }else{
                $this->setErrorCode(15101)->setErrorMsg(ApiErrorMsgText::ERROR_15101);
                $this->setData(['id' => $this->getParameters('orderId')]);
                return false;
            }
            $this->setData(['id' => $this->getParameters('orderId')]);
            return true;
        }
        return false;
    }

}