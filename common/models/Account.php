<?php

namespace common\models;

use Yii;
use common\lib\Helps;
use common\custody\UserApi;
use common\lib\Workerman;

/**
 * This is the model class for table "{{%account}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $total
 * @property string $use_money
 * @property string $no_use_money
 * @property string $collection
 * @property string $user_proceeds
 * @property string $hq
 * @property string $award
 * @property string $redenvelope
 * @property string $virtual_money
 * @property string $base_total
 * @property string $exper_money
 * @property string $use_money_custody
 * @property string $freeze_money_bid
 */
class Account extends DmActiveRecord {

    const CREDIT_IN = '+';
    const CREDIT_OUT = '-';
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%account}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['user_id'], 'integer'],
            [['total', 'use_money', 'no_use_money', 'collection', 'user_proceeds', 'hq', 'award', 'redenvelope', 'virtual_money'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'total' => 'Total',
            'use_money' => 'Use Money',
            'no_use_money' => 'No Use Money',
            'collection' => 'Collection',
            'user_proceeds' => 'User Proceeds',
            'hq' => 'Hq',
            'award' => 'Award',
            'redenvelope' => 'Redenvelope',
            'virtual_money' => 'Virtual Money',
        ];
    }
    
    //添加用户账户信息
    public function addUser($data) {
        $this->load($data, '');
        if (!$this->save(false)) {
            return false;
        }
        return true;
    }

    /**
     * 获取用户资金基本信息
     * @param type $user_id
     * @return type
     */
    public static function getAccountMoney($user_id) {
        $where = 'user_id=' . $user_id;
        $list = self::find()
                ->where($where)
                ->asArray()
                ->one();
        $user = new User();
        $user_info = $user->getByPk('accountId', $user_id);
        $redis_money = UserAccount::custodyBalanceQueryByAcIdRe($user_id, $user_info['accountId']);
        $list['bank_total'] = $redis_money['currBal'];
        $list['use_money_custody'] = $redis_money['availBal'];
        return $list;
    }
    
    /**
     * 从redis拉余额
     * @param type $user_id
     * @return type
     */
    public static function getRedisUseMoney($user_id) {
        $money['availBal'] = Yii::$app->redisUser->HGET(\common\lib\RedisKey::USER_INFO . $user_id, 'availBal');
        $money['currBal'] = Yii::$app->redisUser->HGET(\common\lib\RedisKey::USER_INFO . $user_id, 'currBal');
        return $money;
    }
    /**
     * 体验金还款
     * @param type $user_id
     * @param type $money
     */
    public static function repayVirMoney($user_id,$money){
        $user_account=self::getAccountMoney($user_id);
        $virmoney['exper_money']=$user_account['exper_money']+$money;
        self::updateAll($virmoney,"user_id = {$user_id}");
    }

    /**
     * 账户金额变动
     * @param type $user_id
     * @param type $before_account_info
     * @param type $change_money
     * @param type $type
     * @return boolean
     */
    public static function moneyChange($user_id, $before_account_info, $change_money, $type, $remark = '') {
        $condition = 'user_id=:user_id AND base_total=:base_total';
        $params = [':user_id' => $user_id, ':base_total' => $before_account_info['base_total']];
        $flag = 1;
        //是否立即刷新
        $fresh_flag = false;
        switch ($type) {
            //提现
            case Accountx::TYPE_WITHDRAW_DONE://受理中，冻结资金
                //$after_account_info['base_total'] = $before_account_info['base_total'] - $change_money;
                $after_account_info['use_money_custody'] = $before_account_info['use_money_custody'] - $change_money;
                $type=Accountx::TYPE_WITHWAIT;
                $flag=1;
                $fresh_flag = true;
                $remark='提现冻结';
                break;
            case Accountx::TYPE_WITHSUCCESS:
                $after_account_info['base_total'] = $before_account_info['base_total'] - $change_money;
                if($change_money<=50000)
                {
                    $after_account_info['use_money_custody'] = $before_account_info['use_money_custody'] - $change_money;
                }
                $flag=1;
                $fresh_flag = true;
                $remark='提现成功';
                break;
            //投资，先冻结
            case Accountx::TYPE_BID_FREEZE:
                $after_account_info['freeze_money_bid'] = $before_account_info['freeze_money_bid'] + $change_money;
                $after_account_info['use_money_custody'] = $before_account_info['use_money_custody'] - $change_money;
                break;
            //满标，将冻结资金扣除
            case Accountx::TYPE_BID_PRO:
                $after_account_info['freeze_money_bid'] = $before_account_info['freeze_money_bid'] - $change_money;
                $flag = 1; //不写日志
                break;
            //借款放款
            case Accountx::TYPE_BORROW:
                $after_account_info['base_total'] = $before_account_info['base_total'] + $change_money;
                $after_account_info['use_money_custody'] = $before_account_info['use_money_custody'] + $change_money;
                break;
            //借款还款
            case Accountx::TYPE_REPAYBORROW:
                $after_account_info['base_total'] = $before_account_info['base_total'] - $change_money;
                $after_account_info['use_money_custody'] = $before_account_info['use_money_custody'] - $change_money;
                break;
                //提现失败回滚--把钱加回数据库
            case Accountx::TYPE_WITHROOLBACK:
                //$after_account_info['base_total'] = $before_account_info['base_total'] + $change_money;
                if($change_money>50000)
                {
                    $after_account_info['use_money_custody'] = $before_account_info['use_money_custody'] + $change_money;
                    $flag=1;
                    $remark='提现失败';
                }

                break;

            //其他都是给账户加钱了/
            default :
                //判断如果还未开通存管账号
                $userObj = new User();
                $accountId = $userObj->getByPk('accountId', $user_id);
                //未开通存管，回款资金先回入use_money,开通以后回到use_money_custody
                if (empty($accountId['accountId'])) {
                    $after_account_info['use_money'] = $before_account_info['use_money'] + $change_money;
                } else {
                    $after_account_info['use_money_custody'] = $before_account_info['use_money_custody'] + $change_money;
                }
                $after_account_info['base_total'] = $before_account_info['base_total'] + $change_money;
                if ($type == Accountx::TYPE_RECHARGE) {
                    $fresh_flag = true;
                }
                break;
        }
        //这里不用合并，直接要更新的字段进行更新 下面流水 total 和 use_money 要处理下
        $after_account_info = array_merge($before_account_info, $after_account_info);
        //去掉主键
        unset($after_account_info['id']);
        //去掉银行总额
        unset($after_account_info['bank_total']);
        //为了不嵌套事务，内部不添加事务，放在外部执行
        try {
            $userObj = new User();
            $accountId = $userObj->getByPk('accountId', $user_id);
            if (!empty($accountId['accountId'])) {
                \common\lib\Queue::freshMoney($user_id, $accountId['accountId']);
            }
            if ($fresh_flag) {
                UserAccount::freshMoneyNow($user_id, $accountId['accountId']);
            }
            self::updateAll($after_account_info, $condition, $params);
            if ($flag) {
                $data = ['user_id' => $user_id, 'total' => $after_account_info['base_total'], 'money' => $change_money, 'use_money' => $after_account_info['use_money'] + $after_account_info['use_money_custody'],
                    'remark' => $remark, 'addtime' => time(), 'addip' => '127.0.0.1', 'type' => $type];
                Accountx::addLog($user_id, $data);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    //用户还款本金处理
    public static function userRepay($user_id,$accountId,$money,$type=1,$remark){
        $before_account_info=self::getAccountMoney($user_id);//用户的账户信息

        $user_use_money = UserAccount::custodyBalanceQueryByAcId($accountId);//看下这个人真实有多少钱
        $total = $before_account_info['base_total'];
        $use_money = $before_account_info['use_money_custody']; 
        if(($before_account_info['use_money_custody'] + $money) > $user_use_money){//这一步不可思议，只更新流水，不要再更新资金了

        }else{
            $use_money=$before_account_info['use_money_custody']+$money;

            if($type==0){
                self::updateAll(['use_money_custody'=>$use_money],"user_id= {$user_id} and use_money_custody = {$before_account_info['use_money_custody']}");//更新资金
            }elseif($type==1){
                $total = $before_account_info['base_total']+$money;
                self::updateAll(['use_money_custody'=>$use_money,'base_total'=>$total],"user_id= {$user_id} and use_money_custody = {$before_account_info['use_money_custody']}");//更新资金
            }
        }
        if (!empty($accountId)) {
            \common\lib\Queue::freshMoney($user_id, $accountId);
        }
        $data = ['user_id' => $user_id, 'total' => $total, 'money' => $money, 'use_money' => $use_money,
                    'remark' => $remark, 'addtime' => time(), 'addip' => '127.0.0.1', 'type' => Accountx::TYPE_REPAYMENT];
        Accountx::addLog($user_id, $data);
        return true;

    }
    /**
     * 平台发送红包
     * @param user_id 赠与人的user_id
     * @param accountId 赠与人的accountId
     * @param money 金额
     * @param type 红包类型
     * @param remark 备注
     * 这里指真实的发放红包，使用发钱了
     */
    public static function sendVoucherPay($user_id,$accountId,$money,$type,$remark,$params=array()){
        $cash_type=22;
        //获取用户的资金情况
        $account_info=self::getAccountMoney($user_id);
        if(empty($account_info)){
            return false;
        }
        $update_info=array();
        $total=$account_info['base_total'];
        $use_money=$account_info['use_money_custody'];
        switch ($type) {
            //平台发送红包
            case Ndm_account::TYPE_RED:
                $cash_type = Accountx::TYPE_MONEY_RE;
                break;
            //内部提成
            case Ndm_account::TYPE_RED_AWARD:
                $cash_type = Accountx::TYPE_DM_AWARDREPAY;
                break;
            //资金转入
            case Ndm_account::TYPE_RED_OLDMONEY:
                $update_info['use_money']=$account_info['use_money']-$money;
                $update_info['total']=$account_info['total']-$money;
                if($update_info['total']<0){
                    $update_info['total']=0;
                }
                if($update_info['use_money']<0){
                    $update_info['use_money']=0;
                }
                $cash_type = Accountx::TYPE_SYN_ACCOUNT;
                break;
            //还款本金
            case Ndm_account::TYPE_RED_REPAY:
                $cash_type = Accountx::TYPE_REPAYMENT;
                    //如果是未开通存管把待收从总资金里面扣除,因为开通存管以后会转入存管可用余额，
                    //如果开通了，也减去，因为下面会再加一次
                    $account_info['base_total'] = $account_info['base_total'] - $money;
                break;
            //还老的利息
            case Ndm_account::TYPE_RED_REPAY_INTEREST:
                $cash_type = Accountx::TYPE_REPAYMENT;
                break;
            //投资红包返现
            case Ndm_account::TYPE_RED_TENDER:
                $cash_type = Accountx::TYPE_RED_RE;
                break;
            case Ndm_account::TYPE_RED_TC:
                $cash_type = Accountx::TYPE_IN_TC;
                break;
            case Ndm_account::TYPE_RED_REFERRER:
                $cash_type = Accountx::TYPE_MAGIC_RED;
                break;
            case Ndm_account::TYPE_RED_PARTNER:
                $cash_type = Accountx::TYPE_PARTNER_TC;
                break;
            //单纯的红包加钱
            default:
                $cash_type = Accountx::TYPE_MONEY_RE;
                break;

        }//end switch
        
        //填写红包资金流水
        if(!empty($accountId)){
            $send_params=array('accountId'=>"{$accountId}",'txAmount'=>"{$money}",'desLine'=>"{$cash_type}");
            $user_custdoy = new UserApi();
            $ret=$user_custdoy->sendVoucherPay($send_params);
            if($ret['retCode'] === '00000000'){//发送失败，只要发送失败，全部回去。相反只要发送成功，不管流水有没有成功，这笔还款都已经完成
                $out_id=isset($params['out_id'])?$params['out_id']:0;
                $red_data=['io_type'=>0,'user_id'=>$user_id,'money'=>$money,'type'=>$type,'out_id'=>$out_id,'addtime'=>time(),'remark'=>$remark];
                Ndm_account::addLog($user_id,$red_data);
            }else{
               return false; 
            }
            //进入待刷新余额集合中
            \common\lib\Queue::freshMoney($user_id, $accountId);
            $update_info['base_total']=$account_info['base_total']+$money;
            $update_info['use_money_custody']=$account_info['use_money_custody']+$money;
            $total=$update_info['base_total'];
            $use_money=$update_info['use_money_custody'];
        }else{
            $update_info['total'] = $account_info['total']+$money;
            $update_info['use_money'] = $account_info['use_money']+$money;//老的no_use_money不管了，不做处理，此字段也不在使用
            
            $total=$update_info['total'];
            $use_money=$update_info['use_money'];
        }

        self::updateAll($update_info,"user_id = {$user_id} and use_money_custody = {$account_info['use_money_custody']} and use_money = {$account_info['use_money']}");
        $data = ['user_id' => $user_id, 'total' => $total, 'money' => $money, 'use_money' => $use_money,
                    'remark' => $remark, 'addtime' => time(), 'addip' => '127.0.0.1', 'type' => $cash_type];
        Accountx::addLog($user_id, $data);
        return true;
        


    }
    //获取用户流水信息
    public static function  userAccountInfo($userId,$pageSage=0,$limit=3,$type=0)
    {
        $tableName='xx_account_'.Helps::_mod($userId);
        $type==0?$typeCondition='':$typeCondition=" and type in ($type)";
        $accountInfoNew=array();
        //实时对接银行流水
        $userInfo=Yii::$app->user->getBaseInfo();
        $card_status=$userInfo['card_status'];
        if($card_status>2)
        {
            $_page=$pageSage==0?'1':$pageSage;
            $bankList = Workerman::synAccountFlow($_page, $page * $pageSage, 'all');
            $countBankList = count($bankList);
        }else
        {
            $bankList = [];
            $countBankList = count($bankList);

        }


        $sqlTotal=sprintf("select FROM_UNIXTIME(addtime,'%%Y-%%m') as _total  from $tableName where user_id=%d  $typeCondition GROUP BY FROM_UNIXTIME(addtime,'%%Y-%%m')",$userId);
        $accountTotal=Helps::createNativeSql($sqlTotal)->queryAll();
        $sqlList=sprintf("select * ,FROM_UNIXTIME(addtime,'%%Y-%%m') as formattime,(case when `type` in(1,4,5,15,16,17,18,19,20,22,24,26,27,31) then ' ' else 'negative' end) as classtype  from $tableName where user_id=%d $typeCondition and FROM_UNIXTIME(addtime,'%%Y-%%m') in 
                    (select * from (select FROM_UNIXTIME(addtime,'%%Y-%%m') as addtime  from $tableName where user_id=%d $typeCondition GROUP BY FROM_UNIXTIME(addtime,'%%Y-%%m') ORDER BY addtime DESC limit %d,%d) as temp_tab) order by addtime desc",$userId,$userId,$pageSage,$limit);
        $accountInfo=Helps::createNativeSql($sqlList)->queryAll();
        $operation_type = Yii::$app->params['account_opration_type'];
        foreach ($accountInfo as $key=>$value)
        {
            $value['remark'] = isset($operation_type[$value['type']]) ? $operation_type[$value['type']] : '';
            $accountInfoNew[$value['formattime']][]=$value;
        }
        return array('total'=>count($accountTotal),'list'=>$accountInfoNew);
    }

    /**
     * 体验金收益提现
     * @param $user_id
     *
     * @return array
     */
    public static function extractExperience( $user_id ){
        $where = ['and','user_id = :user_id', 'status > 1'];
        $bind = [ ':user_id' => $user_id ];
        $beenTender = BorrowTender::find()->where($where)->params($bind)->count('id');
        if($beenTender == 0){
            return [
                'stat' => 12,
                'msg' => '首次提取体验金收益，需投资非体验标项目'
            ];
        }
        $experMoney = self::find()->where(['user_id' => $user_id])->select('exper_money')->asArray()->one();
        if( floatval( $experMoney['exper_money'] ) <= 0 ){
            return [
                'stat' => 12,
                'msg' => '当前可提取收益为零'
            ];
        }
        $accountId = Yii::$app->user->getBaseInfo('accountId', false );
        $result = self::sendVoucherPay( $user_id, $accountId['accountId'], $experMoney['exper_money'], Ndm_account::TYPE_RED, '体验金收益提取' );
        if(!$result){
            Yii::error('error: 体验金收益提取失败。  用户Id: ' . $user_id, 'custody_error');
            return [
                'stat' => 12,
                'msg' => '体验金提取失败，请重试！'
            ];
        }
        $sql = "update" . self::tableName() . "set exper_money = 0 where user_id = :user_id";
        $update = Yii::$app->db->createCommand($sql)->bindValues(['user_id'=>$user_id])->execute();
        if( $update ){
            return [
                'stat' => 10,
                'msg' => $update,
            ];
        }else{
            return [
                'stat' => 13,
                'msg' => '数据库繁忙'
            ];
        }
    }

    public static function getVoucherPayType($type='')
    {
        $arr = [
            Ndm_account::TYPE_RED_REPAY => '产品还款',
            Ndm_account::TYPE_RED_REPAY_INTEREST => '产品还息',
            Ndm_account::TYPE_RED_AWARD => '加息券还息',
            Ndm_account::TYPE_RED_OLDMONEY => '资金转入',
            Ndm_account::TYPE_RED_TENDER => '使用红包',
            Ndm_account::TYPE_RED_TC=>'内部提成',
            Ndm_account::TYPE_RED => '平台发送红包',
            Ndm_account::TYPE_RED_REFERRER => '魔法红包',
        ];
        if ($type!=='') {
            return $arr[$type] ?: '';
        }else{
            return $arr;
        }
    }

    /**
     * 更新积分
     * @param type $user_id
     * @param type $change_credist
     * @param type $type
     */
    public static function creditsChange($user_id, $change_credist, $operation, $relatedid, $type = self::CREDIT_IN, $remark = '') {
        if ($type == self::CREDIT_OUT) {
            $change_credist = - $change_credist;
        }
        self::updateAllCounters(['virtual_money' => $change_credist], ['user_id' => $user_id]);
        //给用户增加记录
        $log = ['user_id' => $user_id, 'operation' => $operation, 'relatedid' => $relatedid, 'extcredits' => $change_credist, 'remark' => $remark, 'addtime' => time()];
        CreditLog::addOne($log);
        return true;
    }
}
