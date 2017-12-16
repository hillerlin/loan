<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\custody\UserApi;
use common\models\LoanBorrower;
use Yii;

/**
 * smsCodeApply
 * 2.2.3 发送短信验证码
 */
class SmsCodeApply extends ApiBase
{
    public function getResult()
    {
        return $this->send();
    }

    private function send()
    {
        $rules = [
            ['mobile',true, 'mobile'],
            ['srvTxCode',true, '', 'enum'],
        ];

        if($this->verify($rules)){
            $data = [
                'mobile' => $this->getParameters('mobile'),
                'srvTxCode' => $this->getParameters('srvTxCode'),
            ];
            if (!($userInfo = \common\models\User::findByPhone($data['mobile']))) {
                $this->setErrorCode(15002)->setErrorMsg(ApiErrorMsgText::ERROR_15002);
                return false;
            }
            if (!$this->getIsLocal()) {
                //判断用户是否存在
                $loanBorrowerModel = new LoanBorrower();
                if (!($loanBorrowerModel->findByUidAndMerchantId($userInfo->user_id,$this->getPost('merchantId')))) {
                    $this->setErrorCode(15002)->setErrorMsg(ApiErrorMsgText::ERROR_15002);
                    return false;
                }
            }
            $userCustody = new UserApi();
            $ret = $userCustody->smsCodeApply(['mobile' => $data['mobile'], 'srvTxCode' => $data['srvTxCode']]);
            if ($ret === false) {
                $this->setErrorCode($userCustody->getErrorNo())->setErrorMsg($userCustody->getError());
                return false;
            } else {
                Yii::$app->redis->SETEX($userInfo->user_id . ':srvAuthCode', 120, $ret['srvAuthCode']);
                $retData = [
                    'mobile' => $data['mobile'],
                    'srvTxCode' => $data['srvTxCode'],
                    'sendTime' => $ret['sendTime'],
                    'smsSeq' => $ret['smsSeq'],
                    'validTime' => $ret['validTime'],
                ];
                $this->setData($retData);
                return true;
            }
        }
        return false;
    }

}