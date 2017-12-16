<?php

namespace common\models\forms;

use Yii;
use yii\base\Model;
use common\models\User;
use common\lib\custody\CustodyApi;


/**
 * Login form
 */
class RechargeForm extends Model {

    public $txAmount;
    public $userId;
    public $forgotPwdUrl;
    public $acqRes;
    public $channel;
    private $_isEnterprise;
    private $_user;

    const INIT = 'init';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::INIT] = ['version', 'service', 'parterId', 'txDate', 'txTime', 'seqNo', 'sign', 'channel',];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            //必填属性
            [['version', 'service', 'parterId', 'txDate', 'txTime', 'seqNo', 'sign', 'channel',], 'required', 'message' => '不能为空'],
            [['txAmount',], 'required', 'message' => '充值金额不能为空'],
            //captcha
            //referee推荐人
            //password
//            ['apwd', 'string', 'min' => 6, 'message' => '密码长度太短'],
//            ['real_phone', 'unique', 'targetClass' => 'common\models\User', 'message' => '手机号已被注册'],
//            ['real_name', 'match', 'pattern' => '/^[\x{4e00}-\x{9fa5}]{2,8}$/u', 'message' => '请输入正确的真实姓名!'],
//            ['gtuser', 'validateGtuser', 'skipOnEmpty' => false],
        ];
    }
    
    protected function validateSign($attribute, $params = null) {
        $sign = $data['sign'];
        unset($data['sign']);
        return strcasecmp(ApiEncrypt::encode($data), $sign) === 0;
    }

    //用户注册
    public function submit() {
        if ($this->validate()) {
            $active = [1,2,3,4,5,13,9,10,17];//也可以写成$atctive=['accountId','real_name','txAmount'.......]
            $sendData = \common\lib\Helps::joinMappingPlus($active, $this->userId, 'directRechargeOnline');
            $requestApi = new CustodyApi($this->channel);
            $sendData['seqNo'] = $requestApi->getSeqNo();
            $sendData['txDate'] = $requestApi->getTxDate();
            $sendData['txTime'] = $requestApi->getTxTime();
            
            //先插入一条待审核的提现记录
            $log = [
                'txAmount' => $sendData['txAmount'],
                'txDate' => $sendData['txDate'],
                'txTime' => $sendData['txTime'],
                'seqNo' => $sendData['seqNo'],
//                'accountId' => $sendData['accountId'],
            ];
            $account_recharge = new \common\models\AccountRecharge();
            if (!$account_recharge->addLog($this->userId, $log)) {
                return false;
            }
            $orderId = $account_recharge->getOrderId();
            $sendData['retUrl']=Yii::$app->request->hostInfo.'/public/successreturl?txCode=directRecharge&orderId='.$orderId;
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
