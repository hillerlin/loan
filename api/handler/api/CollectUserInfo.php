<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\lib\ParameterUtil;
use common\models\LoanBorrower;
use common\models\PlBorrow;
use common\models\User;

/**
 * collectUserInfo
 * 2.2.4 借款人信息完善
 */
class CollectUserInfo extends ApiBase
{
    public function getResult()
    {
        return $this->save();
    }

    private function save()
    {
        $rules = [
            ['dmId', true,  'number'],
            ['degree', true, 'enum'],
            ['marriage', true, 'enum'],
            ['address', true],
            ['carNo', true],
            ['property', true, 'enum'],
            ['income', true],
            ['companyIndustry',  false, '', '无'],
            ['companyNature', false, '', '无'],
            ['companyPosition', false, '', '无'],
        ];

        if($this->verify($rules)){
            if ($this->getParameters('income') > 80 || $this->getParameters('income') < 5) {
                $this->setErrorCode(15025)->setErrorMsg(ApiErrorMsgText::ERROR_15025);
                return false;
            }
            //判断用户是否存在
            $loanBorrowerModel = new LoanBorrower();
            if (!($borrowerInfo = $loanBorrowerModel->findByIdAndMerchantId($this->getParameters('dmId'),$this->getPost('merchantId')))) {
                $this->setErrorCode(15001)->setErrorMsg(ApiErrorMsgText::ERROR_15001);
                return false;
            }
            $userInfo = User::findIdentity($borrowerInfo->user_id);
            $insData = [
                'user_id' => $borrowerInfo->user_id,
                'sex' => ParameterUtil::getGenderFromIdCard($userInfo->real_card),
                'age' => ParameterUtil::getAgeFromIdCard($userInfo->real_card),
                'degree' => $this->getParameters('degree'),
                'marriage' => $this->getParameters('marriage'),
                'nowaddress' => $this->getParameters('address'),
                'car' => $this->getParameters('carNo'),
                'property' => $this->getParameters('property'),
                'income' => $this->getParameters('income'),
                'company_industry' => $this->getParameters('companyIndustry'),
                'company_nature' => $this->getParameters('companyNature'),
                'company_position' => $this->getParameters('companyPosition'),
                'residence_address' => ParameterUtil::getAddressFromIdCard($userInfo->real_card),
            ];
            if ($borrowerInfo->plb_id && $plBorrowInfo = PlBorrow::findOne(['id' => $borrowerInfo->plb_id])) {
                $plBorrowInfo->setAttributes($insData, false);
                if (!$plBorrowInfo->save(false)) {
                    $this->setErrorCode(12000);
                    return false;
                }
            }else{
                $insData['addtime'] = time();
                $plBorrowModel = new PlBorrow();
                if(!$plBorrowModel->addPlBorrow($insData)){
                    $this->setErrorCode(12000);
                    return false;
                }
                $borrowerInfo->plb_id = $plBorrowModel->id;
                if (!$borrowerInfo->save(false)) {
                    $this->setErrorCode(12000);
                    return false;
                }
            }
            $result = [
                'dmId' => $this->getParameters('dmId'),
            ];
            $this->setData($result);
            return true;
        }
        return false;
    }
}