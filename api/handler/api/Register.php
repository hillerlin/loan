<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\lib\jf\JfApi;
use common\models\forms\RegisterForm;
use common\models\LoanBorrower;
use common\models\User;
use Yii;
use yii\base\Exception;

/**
 * register
 * 2.2.1 个人注册
 */
class Register extends ApiBase
{
    public function getResult()
    {
        return $this->save();
    }

    private function save()
    {
        $rules = [
            ['mobile',true, 'mobile'],
            ['idNo', true, 'id_no'],
            ['realName', true],
            ['email', false, 'email', ''],
            ['creditLine', false, '', 0],
        ];

        if($this->verify($rules)){
            $mobile = $this->getParameters('mobile');
            $transaction = Yii::$app->db->beginTransaction();
            try {
                //获取征信
                $dmCredit = $this->getDmCredit($mobile);
                if ($dmCredit === false) {
                    return false;
                }
                //判断手机号是否已注册
                $userInfo = User::findByPhone($mobile);
                if ($userInfo) {
                    //对已注册用户信息进行对比和处理
                    if (!$this->checkExistUser($userInfo)) {
                        $transaction->rollBack();
                        return false;
                    }
                    $userId = $userInfo->user_id;
                }else{
                    //注册新用户
                    $regData = [
                        'real_phone' => $mobile,
                        'real_card' => $this->getParameters('idNo'),
                        'real_name' => $this->getParameters('realName'),
                        'real_mail' => $this->getParameters('email'),
                        'reg_source' => User::REG_SOURCE_LOAN,
                        'addip' => Yii::$app->getRequest()->getUserIP(),
                        'phone_status' => 2, //手机认证 2 已认证
                        'cpc_soure' => 85,
                        'cpc_soure_cid' => 'asset',
                    ];
                    $registerForm = new RegisterForm(['scenario' => RegisterForm::SCENARIO_REGISTER]);
                    if (!($registerForm->load($regData, '') && $registerForm->register())) {
                        $transaction->rollBack();
                        $this->setErrorCode($registerForm->getErrorCode())->setErrorMsg($registerForm->getFirstError()?:'');
                        return false;
                    }
                    $userId = $registerForm->user_id;
                }
                //添加新借款人
                $borrowerData = [
                    'user_id' => $userId,
                    'merchant_id' => $this->getPost('merchantId'),
                    'credit' => $this->getParameters('creditLine'),
                    'dm_credit' => $dmCredit,
                ];
                $registerForm = new RegisterForm(['scenario' => RegisterForm::SCENARIO_CREATE_BORROWER]);
                if (!($registerForm->load($borrowerData, '') && $registerForm->createBorrower())) {
                    $transaction->rollBack();
                    $this->setErrorCode($registerForm->getErrorCode())->setErrorMsg($registerForm->getFirstError()?:'');
                    return false;
                }
                $transaction->commit();
                $retData = [
                    'dmId' => $registerForm->dmId,
                    'creditLine' => $registerForm->credit,
                ];
                $this->setData($retData);
                return true;
            } catch (Exception $exc) {
                $transaction->rollBack();
                $this->setErrorCode(11001);
                return false;
            }
        }
        return false;
    }

    private function checkExistUser(User $userInfo)
    {
        $info = [
            'user_id' => $userInfo->user_id,
            'merchant_id' => $this->getPost('merchantId')
        ];
        //判断该账号是否已和该商户关联
        if ($borrowerInfo = LoanBorrower::findOne($info)) {
            $this->setErrorCode(15003)->setErrorMsg(ApiErrorMsgText::ERROR_15003);
            $this->setData(['dmId' => $borrowerInfo->borrower_id]);
            return false;
        }
        //判断和更新用户的个人信息
        if ($userInfo->real_card && $userInfo->real_name) {
            if (!($userInfo->real_card == $this->getParameters('idNo') &&
                $userInfo->real_name == $this->getParameters('realName'))) {
                $this->setErrorCode(15004)->setErrorMsg(ApiErrorMsgText::ERROR_15004);
                return false;
            }
        }else{
            $userInfo->real_card = $this->getParameters('idNo');
            $userInfo->real_name = $this->getParameters('realName');
            if (!$userInfo->save(false)) {
                $this->setErrorCode(12000);
                return false;
            };
        }
        return true;
    }

    private function getDmCredit($mobile)
    {
        if (YII_DEBUG) {
            return '200000';
        }
        $param = [
            'merchantId' => $this->getPost('merchantId'),
            'mobile' => $mobile,
            'real_name' => $this->getParameters('realName'),
            'id_no' => $this->getParameters('idNo'),
        ];
        $jfApi = new JfApi();
        $result = $jfApi->getToken()->requestData($param);
        if ($result) {
            if ($result['result']) {
                return $result['maxQuota'];
            }else{
                $this->setErrorCode(16003)->setErrorMsg(ApiErrorMsgText::ERROR_16003);
                return false;
            }
        }else{
            $this->setErrorCode($jfApi->getErrorNo())->setErrorMsg($jfApi->getErrorMsg()?:'');
            return false;
        }
    }

}