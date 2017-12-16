<?php

namespace common\models\forms;

use Yii;
use common\models\User;
use common\models\LoanBorrower;
use common\models\LoanMerchant;
use api\handler\base\ApiErrorMsgText;
use common\models\LoanOrder;
/**
 * Login form
 */
class OrderForm extends BaseModel {

    public $orderId;
    public $bid;
    public $merchantId;
    public $dmId;
    public $amount;
    public $duration;
    public $unit;
    public $repayStyle;
    public $merchantOrderNum;

    private $_borrower;
    private $_merchant;
    private $_order;

    const SUBMIT_ORDER_AND_CONFIRM = 'submit_order_and_confirm';
    const SUBMIT_ORDER = 'submit_order';
    const SUBMIT_ORDER_LOCAL = 'submit_order_local';
    const CONFIRM_ORDER = 'confirm_order';

    //['version', 'service', 'parterId', 'txDate', 'txTime', 'seqNo', 'sign', 'channel']
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::SUBMIT_ORDER] = ['merchantId', 'dmId', 'amount', 'duration', 'unit', 'repayStyle', 'merchantOrderNum'];
        $scenarios[self::SUBMIT_ORDER_LOCAL] = ['merchantId', 'dmId', 'amount', 'duration', 'unit', 'repayStyle', 'merchantOrderNum'];
        $scenarios[self::CONFIRM_ORDER] = ['merchantId', 'dmId', 'orderId',];
        $scenarios[self::SUBMIT_ORDER_AND_CONFIRM] = ['merchantId', 'dmId', 'amount', 'duration', 'unit', 'repayStyle',];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            //必填属性
            [['merchantId', ], 'required', 'message' => 'merchantId参数不能为空'],
            [['amount', 'dmId', 'duration', 'unit'], 'required', 'message' => '参数不能为空'],
            [['unit', 'repayStyle'], 'required', 'message' => '参数不能为空'],
            [['merchantId', 'dmId', 'amount', 'duration', 'unit', 'repayStyle',], 'required', 'message' => '参数不能为空'],
            ['dmId', 'validateBorrowerId', 'skipOnEmpty' => false],
            ['amount', 'validateAccount', 'skipOnEmpty' => false],
            ['duration', 'validateDurationAndUnit', 'skipOnEmpty' => false],
            ['repayStyle', 'validateRepayStyle', 'skipOnEmpty' => false],
            ['orderId', 'validateOrderId', 'skipOnEmpty' => false],
        ];
    }

    //判断用户推荐信息
    public function validateBorrowerId($attribute, $params = null) {
        if (!$this->hasErrors()) {
            $borrower = $this->getBorrower();
            if (!$borrower || $borrower->merchant_id != $this->merchantId) {
                $this->setErrorCode(15001);
                $this->addError($attribute, ApiErrorMsgText::ERROR_15001);
            }
            $user = User::findIdentity($borrower->user_id);
            if (empty($user)) {
                $this->setErrorCode(15001);
                $this->addError($attribute, ApiErrorMsgText::ERROR_15001);
            }
            $scenario = $this->getScenario();
            if ($scenario != self::SUBMIT_ORDER_LOCAL && empty($user->accountId)) {
                $this->setErrorCode(15007);
                $this->addError($attribute, ApiErrorMsgText::ERROR_15007);
            }
            if (empty($borrower->plb_id)) {
                $this->setErrorCode(15008);
                $this->addError($attribute, ApiErrorMsgText::ERROR_15008);
            }
        }
    }
    public function validateAccount($attribute, $params = null) {
        if (!$this->hasErrors()) {
            //先判断整体总额度
            $merchant = $this->getMerchant();
            if ($merchant->merchant_credit < $merchant->merchant_used_credit + $this->amount) {
                $this->setErrorCode(17001);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17001);
            }
            $borrower = $this->getBorrower();
            //判断用户个人在当前平台总额度
            if ($borrower->dm_credit < $borrower->dm_used_credit + $this->amount) {
                $this->setErrorCode(17002);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17002);
            }
            //判断用户个人在当前平台总额度
            if ($borrower->credit < $borrower->used_credit + $this->amount) {
                $this->setErrorCode(17003);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17003);
            }
            //判断金额是不是整百
            if ($this->amount % 100 > 0) {
                $this->setErrorCode(17009);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17009);
            }
        }
    }
    
    public function validateDurationAndUnit($attribute, $params = null) {
        if (!$this->hasErrors()) {
            //期限类型
            $merchant = $this->getMerchant();
            if ($this->unit != $merchant->unit) {
                $this->setErrorCode(17004);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17004);
            }
            //商户对应的期限表，如果没有设置不让过
            $rate_map = Yii::$app->params['rate_map'];
            $rate_arr = isset($rate_map[$this->merchantId]) ? $rate_map[$this->merchantId] : [];
            if (empty($rate_arr)) {
                $this->setErrorCode(17011);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17011);
            }
            //判断用户个人总额度
            if (!$this->duration > $merchant->max_limit || !isset($rate_arr[$this->duration])) {
                $this->setErrorCode(17005);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17005);
            }
            if (isset($rate_arr[$this->duration]['min_money']) && $rate_arr[$this->duration]['min_money'] > $this->amount) {
                $this->setErrorCode(17012);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17012);
            }
        }
    }
    
    public function validateRepayStyle($attribute, $params = null) {
        if (!$this->hasErrors()) {
            $merchant = $this->getMerchant();
            if ($this->repayStyle != $merchant->repay_style) {
                $this->setErrorCode(17006);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17006);
            }
        }
    }
    
    public function validateOrderId($attribute, $params = null) {
        if (!$this->hasErrors()) {
            $orderObj = $this->getOrder();
            if (empty($orderObj)) {
                $this->setErrorCode(17007);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17007);
                return false;
            }
            //判断用户是否还有未确认的订单
            if (LoanOrder::existWaitConfirm($this->$attribute, $orderObj->borrower_id) > 0) {
                $this->setErrorCode(17010);
                $this->addError($attribute, ApiErrorMsgText::ERROR_17010);
                return false;
            }
        }
    }
    
    //创建订单
    public function create() {
        if ($this->validate()) {
            //新增借款订单
            $rate_map = Yii::$app->params['rate_map'][$this->merchantId][$this->duration];
            $rate = $rate_map['rate'];
            $borrow_rate = isset($rate_map['real_rate']) ? $rate_map['real_rate'] : $this->_merchant->borrow_rate;
            $data = [
                'borrower_id' => $this->_borrower->borrower_id, 
                'amount' => $this->amount, 
                'duration' => $this->duration, 
                'unit' => $this->unit, 
                'repay_style' => $this->repayStyle, 
                'rate' => $rate,
                'borrow_rate' => $borrow_rate,
                'merchant_order_num' => $this->merchantOrderNum,
            ];
            
            $order = new \common\models\LoanOrder();
            if (!($order->addOneLog($data))) {
                $this->setErrorCode(11001);
                $this->addError('sys', ApiErrorMsgText::ERROR_11001);
                return false;
            }
            $this->orderId = $order->order_id;
            return true;
        }
        return false;
    }

    //订单确定
    public function confirmOrder() {
        if ($this->validate()) {
            
            //将借款订单推送给发标系统
            $borrow_data = [
                'account' => $this->_order->amount,
                'limit_time' => $this->_order->duration,
                'if_attorn' => $this->_order->unit,
                'apr' => $this->_order->rate,
                'repay_style' => $this->_order->repay_style,
                'plb_id' => $this->_borrower->plb_id,
                'to_userid' => $this->_borrower->user_id,
                'merchantId' => $this->merchantId,
                'borrow_name_type' => \common\models\Borrow::XFD,
                'borrow_rate' => $this->_order->borrow_rate,
            ];
            if (empty($this->_order->borrow_id)) {
                $borrowForm = new BorrowForm(['scenario' => BorrowForm::BORROW_MERCHANT]);
                if (!$borrowForm->load($borrow_data, '') || !$borrowForm->merchantPush()) {
                    $this->setErrorCode($borrowForm->getErrorCode());
                    $this->addError('sys', '借款失败。' . $borrowForm->getFirstError());
                    return false;
                }
                if (!$this->_order->updateStatus($this->orderId, $borrowForm->bid, 1)) {
                    $this->setErrorCode(11001);
                    $this->addError('sys', ApiErrorMsgText::ERROR_11001);
                    //发标成功，更新订单状态
                    return false;
                }
                $this->bid = $borrowForm->bid;
            } else {
                $this->bid = $this->_order->borrow_id;
            }
            //更新额度
            $borrowerObj = new LoanBorrower();
            if (!$borrowerObj->updateCredit($this->dmId, $this->_borrower->user_id, $this->merchantId, $this->_order->amount)) {
                $this->setErrorCode(11001);
                $this->addError('sys', ApiErrorMsgText::ERROR_11001);
                //更新额度失败
                return false;
            }
            return true;
        }
        return false;
    }
    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getBorrower() {
        if ($this->_borrower === null) {
            if (isset($this->dmId)) {
                $dmId = substr($this->dmId, -8);
                $this->_borrower = LoanBorrower::findIdentity($dmId);
            }
        }
        return $this->_borrower;
    }
    
    protected function getMerchant() {
        if ($this->_merchant === null) {
            if (isset($this->merchantId)) {
                $this->_merchant = LoanMerchant::findIdentity($this->merchantId);
            }
        }
        return $this->_merchant;
    }
    
    protected function getOrder() {
        if ($this->_order === null) {
            if (isset($this->orderId) && isset($this->dmId)) {
                $order = new \common\models\LoanOrder();
                $this->_order = $order->findByIdAndBorrowerId($this->orderId, $this->dmId);
            }
        }
        return $this->_order;
    }
    
    protected function loadAttributes(User $user) {
        $user->setAttributes($this->attributes, false);
    }
}
