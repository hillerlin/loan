<?php

namespace api\handler\service;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\AccountBank;
use common\models\LoanBorrower;
use common\models\User;

class CheckUserInfo extends ApiBase
{
    public function getResult()
    {
        return $this->get();
    }

    private function get()
    {
        $rules = [
            ['dmId', true,  'number'],
            ['idNo', true, 'id_no'],
            ['cardNo', true],
            ['realName', true],
        ];

        if($this->verify($rules)){
            if ($borrowerInfo = LoanBorrower::findIdentity($this->getParameters('dmId'))) {
                $userInfo = User::findIdentity($borrowerInfo->user_id);
                if (!($userInfo->real_card == $this->getParameters('idNo') &&
                    $userInfo->real_name == $this->getParameters('realName'))) {
                    $this->setErrorCode(15004)->setErrorMsg(ApiErrorMsgText::ERROR_15004);
                    return false;
                }
                $cardInfo = AccountBank::findActiveByUid($borrowerInfo->user_id);
                if ($cardInfo['card_bid'] != $this->getParameters('cardNo')) {
                    $this->setErrorCode(15004)->setErrorMsg(ApiErrorMsgText::ERROR_15004);
                    return false;
                }
            }else{
                $this->setErrorCode(15001)->setErrorMsg(ApiErrorMsgText::ERROR_15001);
                return false;
            }
            $data = [
                'dmId' => $this->getParameters('dmId'),
                'mobile' => $userInfo->real_phone,
                'setPwd' => $userInfo->set_pwd,
            ];
            $this->setData($data);
            return true;
        }
        return false;
    }

}