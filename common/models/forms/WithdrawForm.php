<?php

namespace common\models\forms;

use Yii;
use yii\base\Model;
use common\models\User;
use common\models\BadAccount;
use common\models\AccountPayment;
use common\lib\custody\CustodyApi;
/**
 * Login form
 */
class WithdrawForm extends Model {

    public $phone_verify_code;
    public $mobileNew;
    public $mobile;
    public $bankCard;
    public $smsCode;
    public $txAmount;
    public $userId;
    public $txFee = 0.0;
    public $routeCode = 1;
    public $forgotPwdUrl;
    public $reAmount;
    public $smsType;
    public $cardBankCnaps;
    public $acqRes;
    public $channel;
    private $_isEnterprise;
    private $_user;

    const WITHDRAW = 'withdraw';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::WITHDRAW] = ['txAmount', 'userId', 'txFee', 'forgotPwdUrl', 'routeCode', 'cardBankCnaps', 'gtuser'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            //必填属性
            ['txAmount', 'required', 'message' => '提现金额不能为空'],
            ['txFee', 'required', 'message' => '提现手续费不能为空'],
            [['userId', 'channel'], 'required', 'message' => '参数不能为空'],
//            ['gtuser', 'validateGtuser', 'skipOnEmpty' => false],
            ['cardBankCnaps', 'validateCardBankCnaps', 'skipOnEmpty' => false],
            ['forgotPwdUrl', 'match', 'pattern' => '/^(http|https)?:\/\/.*/', 'message' => '链接地址不对'],
            ['txAmount', 'required', 'message' => '提现金额不能为空', 'when' => function() {
                    if ($this->txAmount < 5) {
                        $this->addError('toCheckWithdraw', '提现金额不得少于5元');
                    }
                    $userAccount = \common\models\UserAccount::_pay_account_recharge($this->userId);
                    if (($this->txAmount) > $userAccount['userUseMoney']) {
                        $this->addError('toCheckWithdraw', '提现金额不能大于可用余额');
                    }
                }],
            // 手续费金额验证先注释
            ['txFee', 'required', 'when' => function() {
                    $userAccount = \common\models\UserAccount::_pay_account_recharge($this->userId, $this->txAmount);
                    if ($userAccount['poundage'] != round($this->txFee, 2)) {
                        $this->addError('toCheckTxfee', '提现失败，建议使用电脑网页版提现');
                    }
                }],
        ];
    }

    //判断用户推荐信息
    public function validateCardBankCnaps($attribute, $params = null) {
        if (!$this->hasErrors()) {
            $is_enterprise = $this->isEnterprise();
            if ($is_enterprise || $this->txAmount > 50000) {
                $this->routeCode = 2;
                if (!$this->cardBankCnaps) {
                    $this->addError($attribute, '请输入联行号');
                }
                $this->acqRes = $this->cardBankCnaps;
            }
            
        }
    }

    //用户注册
    public function submit() {
        if ($this->validate()) {
            $atctive = [1, 2, 3, 4, 5, 13, 14, 15, 16, 9, 10]; //也可以写成$atctive=['accountId','real_name','txAmount'.......]
            $sendData = \common\lib\Helps::joinMappingPlus($atctive, $this->userId, 'withdraw');
            $bad = new BadAccount();
            $sendData['txFee'] = $bad->getWithdrawFee($this->txFee, $this->userId);
            $sendData['txAmount'] = $this->txAmount - $this->txFee;
            $requestApi = new \common\lib\custody\CustodyApi($this->channel);
            $sendData['seqNo'] = $requestApi->getSeqNo();
            $sendData['txDate'] = $requestApi->getTxDate();
            $sendData['txTime'] = $requestApi->getTxTime();
            $sendData['routeCode'] = $this->routeCode;
            $sendData['cardBankCnaps'] = $this->cardBankCnaps;
            $sendData['acqRes'] = $this->acqRes;
            
            //企业账户手机号从企业账户获取
            if ($this->isEnterprise()) {
                $sendData['idType'] = $this->idType;
                $mobile = EnterUsers::getMobileByUserId($this->userId);
                if ($mobile) {
                    $sendData['mobile'] = $mobile;
                }
            }
            //先插入一条待审核的提现记录
            $log = [
                'txAmount' => $sendData['txAmount'],
                'txFee' => $sendData['txFee'],
                'txDate' => $sendData['txDate'],
                'txTime' => $sendData['txTime'],
                'seqNo' => $sendData['seqNo'],
                'accountId' => $sendData['accountId'],
            ];
            $account_payment = new AccountPayment();
            if (!$account_payment->addLog($this->userId, $log)) {
                return false;
            }
            $orderId = $account_payment->getOrderId();
            $sendData['retUrl'] = $sendData['retUrl'] . '?txCode=withdraw&orderId=' . $orderId;
            if ($this->channel == CustodyApi::CHANNEL_APP) {
                $sendData['retUrl'] .= '&from=app';
            }
            $sendData['forgotPwdUrl'] = Yii::$app->request->hostInfo . '/custody/goto/passwordset?type=0&_csrf-mobile=' . Yii::$app->request->csrfToken;
            $requset = $requestApi->submitForm($sendData);
            return $requset;
        }
        return false;
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

    protected function isEnterprise() {
        if ($this->_isEnterprise === null) {
            if (isset($this->gtuser)) {
                $this->_isEnterprise = User::isEnterprise($this->gtuser);
            }
        }
        return $this->_isEnterprise;
    }
}
