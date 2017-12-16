<?php

namespace common\models\forms;

use Yii;
use yii\base\Model;
use common\models\User;
use common\models\Borrow;
use common\models\LoanMerchant;
use common\custody\BorrowApi;
use api\handler\base\ApiErrorMsgText;

/**
 * Login form
 */
class BorrowForm extends BaseModel {

    public $merchantId;
    public $borrow_name_type;
    public $account;
    public $apr;
    public $limit_time;
    public $if_attorn;
    public $repay_style;
    public $plb_id = 0;
    public $is_entrusted = 1;
    public $wheat_con;
    public $bid;
    public $to_userid;
    public $cardBankCnaps;
    public $acqRes;
    public $channel;
    public $borrow_rate;
    private $_borrower;
    private $_merchant;
    private $_user;

    const BORROW_MERCHANT = 'borrow_merchant';

    //['version', 'service', 'parterId', 'txDate', 'txTime', 'seqNo', 'sign', 'channel']
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::BORROW_MERCHANT] = ['merchantId', 'borrow_name_type', 'account', 'apr', 'limit_time', 'if_attorn', 'repay_style', 'plb_id', 'to_userid', 'borrow_rate'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            //必填属性
            [['dmId', 'amount', 'duration', 'unit', 'repayStyle'], 'required', 'message' => '参数不能为空'],
//            ['gtuser', 'validateGtuser', 'skipOnEmpty' => false],
        ];
    }

    //用户注册
    public function merchantPush() {
        if ($this->validate()) {
            $plBorrow = new \common\models\PlBorrow();
            if (!($new_plb_id = $plBorrow->copyPlBorrow($this->plb_id))) {
                $this->setErrorCode(11001);
                $this->addError('dq', ApiErrorMsgText::ERROR_11001);
                return false;
            }
            $merchant = $this->getMerchant();
            
            $activity_info = [
                'desc' => '',
            ];
            $tags = [
                'detail' => '期限灵活,小额分散',
                'list' => '',
            ];
            $current = time();
            $borrowInfo = [
                'user_id' => 0,
                'to_userid' => $this->to_userid,
                'name' => '【买断加息】麦分期',
                'title' => 'XFD' . date('Ymd') . sprintf("%'.03d", $this->getCounts()),
                'account' => $this->account,
                'award_apr' => 1,
                'award_type' => 1,
                'limit_time' => $this->limit_time,
                'portion_account' => $this->account,
                'repay_style' => $this->repay_style,
                'lowest_account' => $this->account,
                'most_account' => $this->account,
                'borrow_content' => '',
                'valid_time' => 20,
                'apr' => $this->apr,
                'auto_time' => $current,
                'is_recommend' => 0,
                'borrow_name_type' => $this->borrow_name_type,
                'contract_no' => '',
                'malt_award' => 0,
                'bonus' => 0,
                'bonus_apr' => 0,
                'bonus_money' => 0,
                'bonus_detail' => null,
                'wheat_detail' => '',
                'b_name' => '',
                'b_no' => '',
                'danbaoid' => $merchant->enter_user_id,
                'endpro' => '',
                'repay_opt' => 4,
                'pro_site' => 0,
                'if_attorn' => $this->if_attorn,
                'Intermediary_fee' => 0,
                'intermediary_status' => 2,
                'intermediary_rate' => $this->borrow_rate - $this->apr,
                'pro_kind' => 0,
                'plbid' => $new_plb_id,
                'is_entrusted' => 1,
                'receipt_id' => $merchant->enter_user_id,
                'organize' => '',
                'is_payee_repayment' => 1,
                'is_party' => 0,
                'activity_info' => $activity_info,
                'tags' => $tags,
            ];
            $borrow = new Borrow();
            $otherParams = ['contractType' => 1];
            //新增标的记录
            //增加或者修改表的详情
            $borrowInfoB = [
                'id' => 0,
                'basis_one' => $merchant->purpose,
                'basis_two' => $merchant->describe,
                'risk_one' => $merchant->risk,
            ];

            if ($borrow->saveProject($borrowInfo, $borrowInfoB, 0, 0, $otherParams) <= 0) {
                $this->addError('save', $borrow->errorMsg);
                return false;
            }
            //需要使用电子签章
            $this->wheat_con = $this->electronicSign($borrow->bid);
            if (empty($this->wheat_con)) {
                $this->setErrorCode(17008);
                $this->addError('dq', ApiErrorMsgText::ERROR_17008);
                return false;
            }
            $user = $this->getUser();
            $params = [
                'auto_time' => $current,
                'accountId' => $user->accountId,
                'bid' => $borrow->bid,
                'contract_no' => $borrowInfo['contract_no'],
                'valid_time' => $borrowInfo['valid_time'],
                'repay_style' => $borrowInfo['repay_style'],
                'if_attorn' => $borrowInfo['if_attorn'],
                'limit_time' => $borrowInfo['limit_time'],
                'is_entrusted' => $borrowInfo['is_entrusted'],
                'receipt_id' => $borrowInfo['receipt_id'],
                'account' => $borrowInfo['account'],
                'apr' => $borrowInfo['apr'],
                'Intermediary_fee' => $borrowInfo['Intermediary_fee'],
            ];
            
            $borrow_cusdoy = new BorrowApi();
            $ret = $borrow_cusdoy->debtRegister($params);
            if( empty( $ret ) || $ret['retCode'] !== '00000000' ){
                Yii::error( 'actionDebtregister: failed' . $borrow_cusdoy->getError() . ' data:' . json_encode( $params ) );
                $this->addError('save', $borrow_cusdoy->getError());
                return false;
            }
            //更新登记状态
            //通过校验，开始封装数据
            $wheat_detail = [
                'wheat_man' => '',
                'wheat_no' => $this->_borrower['real_card'],
                'wheat_con' => $this->wheat_con,
                'wheat_money' => $this->account,
                'wheat_things' => '',
                'wheat_price' => '',
                'wheat_scan' => '',
            ];
            $updateInfo['wheat_detail'] = json_encode($wheat_detail);
            $updateInfo['contract_no'] = $this->wheat_con;
            $updateInfo['debt_reg_status'] = 2;
            $updateInfo['debtregister_time'] = $current;
            $updateInfo['status'] = 1;
            $borrowUpdate = Borrow::updateAll($updateInfo, 'bid = ' . $borrow->bid);
            $this->bid = $borrow->bid;
            return true;
        }
        return false;
    }
    
    protected function getCounts() {
        $redis = Yii::$app->redis;
        $key = \common\lib\RedisKey::XFD_TODAY_COUNT;
        if ($redis->EXISTS($key)) {
            $incr = $redis->INCR($key);
        } else {
            $current = time();
            $time = strtotime('+1 day midnight', $current) - $current;
            $incr = 1;
            $redis->SETEX($key, $time, $incr);
        }
        return $incr;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getBorrower() {
        if ($this->_borrower === null) {
            if (isset($this->dmId)) {
                $borrower_id = substr($this->dmId, -8);
                $this->_borrower = Borrower::findIdentity($borrower_id);
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
    
    protected function getUser() {
        if ($this->_user === null) {
            if (isset($this->to_userid)) {
                $this->_user = User::findIdentity($this->to_userid);
            }
        }
        return $this->_user;
    }
    
    /**
     * 
     * @param type $money 借款金额
     * @param type $borrow_name_type 标的类型
     * @param type $borrowUserPhone 借款人手机号
     * @param type $organCode 担保公司组织代码 
     * @param type $companyName 担保公司名
     * @return boolean
     */
    protected function electronicSign($bid) {
        $url = Yii::$app->params['dmUrl'] . "/elecsign/intelligenceelecsignapi";
        $curl = new \linslin\yii2\curl\Curl();
        $params = [
            'bid' => $bid,
        ];
        $responce = $curl->setPostParams($params)->post($url);
//        var_dump($params,$responce );exit;
        //请求出现错误
        if ($responce === false) {
            //写日志
            Yii::info('error: connection failed'.$curl->errorCode.'-'.$curl->errorText . '.  send data:' . json_encode($params));
            return false;
        }
        $result = json_decode($responce, true);
        if ($result['code'] != 200) {
            Yii::info('error: return failed'.$curl->errorCode.'-'.$curl->errorText . '.  recevie data:' . $responce);
            return false;
        }
        return $result['contractId']['intermediaryApplyNo'];
    }

    protected function loadAttributes(Borrow $borrow) {
        $borrow->setAttributes($this->attributes, false);
    }

}
