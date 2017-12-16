<?php

namespace common\models\forms;

use api\handler\base\ApiErrorMsgText;
use common\models\Account;
use common\models\AccountBank;
use common\models\LoanBorrower;
use Yii;
use common\models\User;
use common\custody\UserApi;
/**
 * Login form
 */
class RegisterForm extends BaseModel {

    public $real_phone;
    public $real_card;
    public $real_name;
    public $real_mail;
    public $reg_source;
    public $addip;
    public $phone_status;
    public $cpc_soure;
    public $cpc_soure_cid;
    public $dmId;

    public $user_id;
    public $merchant_id;
    public $credit;
    public $dm_credit;

//    public $user_id;
    public $id_card;
//    public $real_name;
    public $mobile;
    public $bank_card;
    public $smsCode;
    public $accountId;

    public $auser;
    private $_user;

    const SCENARIO_REGISTER = 'register';
    const SCENARIO_VERIFY_ID_AND_OPEN_CUSTODY = 'verify_id_and_open_custody';    //验证身份证并开通存管
    const SCENARIO_CREATE_BORROWER='create_borrower';//创建借款人
    const SETPWD='setpwd';//重设交易密码

    const EVENT_SEND_CREDITS = 'send_credits';
    
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['real_phone', 'real_card', 'real_name','real_mail', 'reg_source', 'addip', 'phone_status', 'cpc_soure_cid', 'cpc_soure'];
        $scenarios[self::SCENARIO_VERIFY_ID_AND_OPEN_CUSTODY] = ['user_id', 'id_card', 'real_name', 'mobile', 'bank_card', 'smsCode'];
        $scenarios[self::SCENARIO_CREATE_BORROWER]=['user_id', 'merchant_id', 'credit', 'dm_credit'];
        $scenarios[self::SETPWD]=['img_verify_code'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            //必填属性
            [['real_phone', 'awpd', 'bank_card', 'smsCode', 'user_id', 'mobile'], 'required', 'message' => '必填字段不能为空'],
            //password
            ['apwd', 'string', 'min' => 6, 'message' => '密码长度太短'],
            ['credit', 'validateCredit', 'skipOnEmpty' => false],
            ['real_phone', 'unique', 'targetClass' => 'common\models\User', 'message' => '手机号已被注册'],
//            [\u4E00-\u9FA5]{2,5}(?:·[\u4E00-\u9FA5]{2,5})*
            ['real_name', 'match', 'pattern'=>'/^[\x{4e00}-\x{9fa5}]{2,16}(?:·[\x{4e00}-\x{9fa5}]{2,6})*$/u', 'message' => '请输入正确的真实姓名!'],
            ['real_card', 'validateIdCard', 'skipOnEmpty' => false] ,
        ];
    }

    //验证身份证
    public function validateIdCard($attribute, $params = null) {
        if (!$this->hasErrors()) {
            $sAge = substr($this->$attribute, 6, 4);
            $eAge = date('Y', time());
            $realAge = $eAge - $sAge;
            if ($realAge < 18) {
                $this->setErrorCode(15021);
                $this->addError($attribute, ApiErrorMsgText::ERROR_15021);
            }
            $user_info = $this->getUser();
            //未认证过身份证的用户，对身份证唯一性做判断
            if (empty($user_info['real_card'])) {
                $result = Yii::$app->db->createCommand('select user_id from xx_user where real_card="' . $this->$attribute.'"')->queryColumn();
                if ($result) {
                    $this->setErrorCode(15022);
                    $this->addError($attribute, ApiErrorMsgText::ERROR_15022);
                }
            }
        }
    }

    //验证信用额度
    public function validateCredit($attribute, $params = null) {
        if (!$this->hasErrors()) {
            $this->credit = (float)$this->credit;
            if ($this->credit>0) {
                if($this->dm_credit < $this->credit){
                    $this->setErrorCode(16001);
                    $this->addError($attribute, ApiErrorMsgText::ERROR_16001);
                }
            }else{
                $this->credit = $this->dm_credit;
            }
        }
    }

    //用户注册
    public function register() {
        if ($this->validate()) {
            /** @var User $user */
            $user = new User();
            $this->loadAttributes($user);
            if (!$user->register()) {
                $this->setErrorCode(12000);
                return false;
            }
            $this->user_id = $user->user_id;
            $account = new Account();
            //开账户
            if (!$account->addUser(['user_id' => $this->user_id])) {
                $this->setErrorCode(12000);
                return false;
            }
            return true;
        }
        return false;
    }

    public function createBorrower()
    {
        if ($this->validate()) {
            $params = $this->attributes;
            $borrower = new LoanBorrower();
            if (!$borrower->addBorrower($params)) {
                $this->setErrorCode(12000);
                return false;
            }
            $this->dmId = $borrower->borrower_id;
            return true;
        }
        return false;
    }

    //用户开户和认证身份证信息
    public function openCustodyAccount() {
        if ($this->validate()) {
            $params = $this->attributes;
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();
            //在江西银行开账户
            $userCustody = new UserApi();
            $ret = $userCustody->accountOpenPlus($params);
            if ($ret === false) {
                $this->setErrorCode($userCustody->getErrorNo());
                $this->addError('open', $userCustody->getError());
                return false;
            }
            //开通成功修改数据库
            $accountBank = new AccountBank();
            //将银行卡信息保存在系统中
            if ($accountBank->setDefaultBankCard($params) === false) {
                $this->setErrorCode(12000);
                $transaction->rollBack();
                return false;
            }
            $this->accountId = $ret['accountId'];
            //将江西银行卡号存在用户表
            $userUpdate = [
                'accountId' => $this->accountId,
                'card_status' => time(),
            ];
            if (User::updateInfo('user_id = ' . $this->user_id, $userUpdate) === false) {
                $this->setErrorCode(12000);
                $transaction->rollBack();
                return false;
            }
            $transaction->commit();
            return true;
        }
    }
   
    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser() {
        if ($this->_user === null) {
            if (isset($this->user_id)) {
                $this->_user = User::findIdentity($this->user_id);
            } elseif (isset($this->auser)) {
                $this->_user = User::findByUsernameOrEmailOrPhone($this->auser);
            }
        }
        return $this->_user;
    }

    protected function loadAttributes(User $user) {
        $user->setAttributes($this->attributes, false);
    }


    public function returnCheck()
    {
        return $this->validate();
    }
}
