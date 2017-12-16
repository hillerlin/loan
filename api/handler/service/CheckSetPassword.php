<?php

namespace api\handler\service;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\LoanBorrower;
use common\models\User;

class CheckSetPassword extends ApiBase
{
    public function getResult()
    {
        return $this->get();
    }

    private function get()
    {
        $rules = [
            ['dmId', true,  'number'],
        ];

        if($this->verify($rules)){
            if ($borrowerInfo = LoanBorrower::findIdentity($this->getParameters('dmId'))) {
                $userInfo = User::findIdentity($borrowerInfo->user_id);
                if (!$userInfo->set_pwd) {
                    $this->setErrorCode(18001)->setErrorMsg(ApiErrorMsgText::ERROR_18001);
                    return false;
                }
            }else{
                $this->setErrorCode(15001)->setErrorMsg(ApiErrorMsgText::ERROR_15001);
                return false;
            }
            return true;
        }
        return false;
    }

}