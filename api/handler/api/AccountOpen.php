<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\models\forms\RegisterForm;
use common\models\LoanBorrower;

/**
 * accountOpen
 * 2.2.2 开通银行存管&去设置交易密码
 */
class AccountOpen extends ApiBase
{
    public function getResult()
    {
        return $this->save();
    }

    private function save()
    {
        $rules = [
            ['dmId', true,  'number'],
            ['cardNo', true],
            ['smsCode', true, 'number'],
            ['retUrl', true],
            ['notifyUrl', true],
        ];

        if($this->verify($rules)){
            //判断用户是否存在
            $loanBorrowerModel = new LoanBorrower();
            if ($this->getIsLocal()) {
                $borrowerInfo = LoanBorrower::findIdentity($this->getParameters('dmId'));
            }else{
                if (!($borrowerInfo = $loanBorrowerModel->findByIdAndMerchantId($this->getParameters('dmId'),$this->getPost('merchantId')))) {
                    $this->setErrorCode(15001)->setErrorMsg(ApiErrorMsgText::ERROR_15001);
                    return false;
                }
            }

            //判断用户是否开通存管&设置交易密码
            $userInfo = \common\models\User::findOne(['user_id' => $borrowerInfo->user_id]);
            if ($userInfo->accountId) {
                if ($userInfo->set_pwd == 1) {
                    $this->setErrorCode(15024)->setErrorMsg(ApiErrorMsgText::ERROR_15024);
                    return false;
                }
                $accountId = $userInfo->accountId;
            } else {
                //开通存管
                $data = [
                    'user_id' => $borrowerInfo->user_id,
                    'id_card' => $userInfo->real_card,
                    'real_name' => $userInfo->real_name,
                    'mobile' => $userInfo->real_phone,
                    'bank_card' => $this->getParameters('cardNo'),
                    'smsCode' => $this->getParameters('smsCode'),
                ];
                $registerForm = new RegisterForm(['scenario' => RegisterForm::SCENARIO_VERIFY_ID_AND_OPEN_CUSTODY]);
                if (!$registerForm->load($data, '') || !$registerForm->openCustodyAccount()) {
                    $this->setErrorCode($registerForm->getErrorCode())->setErrorMsg($registerForm->getFirstError()?:'');
                    return false;
                }
                $accountId = $registerForm->accountId;
            }
            $result = [
                'accountId' => $accountId,
                'idNo' => $userInfo->real_card,
                'name' => $userInfo->real_name,
                'mobile' => $userInfo->real_phone,
                'acqRes' => $this->getPacketLogId(),
            ];
            $this->setData($result);
            return true;
        }
        return false;
    }
}