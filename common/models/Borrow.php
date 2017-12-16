<?php

namespace common\models;

use common\lib\Helps;
use Yii;

class Borrow extends DmActiveRecord {

    const ZC_ALL = 0;       //全部资产
    const BL = 1;        //保理
    const GYL = 2;        //供应链
    const MTJH_BL = 3;      //麦田计划保理
    const MTJH_GYL = 4;     //麦田计划供应链
    const ZHI_RONG = 5;     //直融
    const ZHI_RONG_BZ = 6;  //直融（保障）
    const GRJD = 7;         //个人借贷
    const QJS = 8;          //青金所
    const CD = 9;           //车贷
    const XFD = 10;           //消费贷
    const STATUS_WAIT_AUDIT = 1;     //待审核
    const STATUS_AUDIT = 2;     //审核完成
    const STATUS_COLLECT_SUCCESS = 4;     //标的投满
    const STATUS_REPAY_SUCCESS = 5;     //还款完成
    const STATUS_COLLECT_FAILED = 6;     //募集失败
    const REG_STATUS_UNDO = 0;
    const REG_STATUS_APPLY = 1;
    const DEBT_REG_STATUS_APPLY_SUCCESS = 2;
    const DEBT_REG_STATUS_CANCEL = 3;
    const DEBT_REG_STATUS_CANCEL_SUCCESS = 4;
    const LIMIT_TYPE_MONTH = 1;            //按月
    const LIMIT_TYPE_DAY = 2;              //按天
    const ENTRUSTED_SUB = 1;                //受托支付提交银行
    const ENTRUSTED_CONFIRM = 2;            //受托支付已确认
    const PAYEE_REPAYMENT_YES = 1;            //受托支付下是否是收款人还款（0 否， 1 是）

    /**
     * @inheritdoc
     */

    public $errorMsg;

    public static function tableName() {
        return '{{%borrow}}';
    }

    public function behaviors() {
        return [
            // 行为，仅直接给出行为的类名称
            'TrusteePay' => \common\behaviros\TrusteePay::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['progress_bar', 'account', 'award', 'award_apr', 'portion_account', 'last_account', 'repayment_account', 'repayment_yesaccount', 'repayment_interest', 'repayment_yesinterest', 'lowest_account', 'most_account', 'apr', 'deviation'], 'number'],
            [['borrow_type', 'user_id', 'to_userid', 'status', 'order', 'type', 'award_type', 'limit_time', 'last_time', 'portion', 'surplus', 'repayment_time', 'end_time', 'repay_style', 'is_false', 'valid_time', 'addtime', 'verify_time', 'verify_user', 'auto_time', 'if_attorn', 'attorn_end_time', 'is_party', 'credit_insurance', 'is_insurance', 'is_recommend', 'is_invest', 'is_orientation', 'plan_uid', 'borrow_name_type', 'malt_award', 'is_app', 'bonus', 'success_time', 'plbid', 'danbaoid', 'endpro', 'repay_opt', 'pro_site', 'repay_confirm', 'if_loan', 'pro_kind', 'intermediary_status'], 'integer'],
            [['borrow_content', 'wheat_detail'], 'string'],
            [['name'], 'string', 'max' => 120],
            [['title', 'litpic', 'litlogo', 'verify_remark', 'b_orientation', 'orientation_pwd', 'contract_no', 'bonus_detail'], 'string', 'max' => 255],
            [['b_name', 'b_no'], 'string', 'max' => 180],
            [['Intermediary_fee'], 'string', 'max' => 20],
        ];
    }

    public static function borrowTypeDesc($borrow_name_type = '') {
        $type = [self::ZC_ALL => '全部资产', self::BL => '保理', self::GYL => '供应链', self::MTJH_BL => '麦田计划保理', self::ZHI_RONG => '直融', self::ZHI_RONG_BZ => '直融（保障）', self::MTJH_GYL => '麦田计划供应链', self::GRJD => '个人借贷', self::QJS => '挂牌资产', self::CD => '车贷资产',];
        if (!array_key_exists($borrow_name_type, $type)) {
            return $type;
        }
        return isset($type[$borrow_name_type]) ? $type[$borrow_name_type] : '';
    }

    public static function getRepayStyle($repay_style = null) {
        $arr = [
            '1' => '按月付息',
            '2' => '到期付息',
            '3' => '按季付息',
            '4' => '等额本息',
        ];
        if (empty($repay_style)) {
            return $arr;
        }
        return isset($arr[$repay_style]) ? $arr[$repay_style] : '';
    }

    public function validBorrowInfo($info) {
        if ($info['borrow_name_type'] == Borrow::QJS && !$info['endpro']) {
            $this->errorMsg = '交易所项目必填项目到期时间';
            return - 1;
        }
        if ($info['borrow_name_type'] == Borrow::QJS && $info['if_attorn'] != 2) {
            $this->errorMsg = '交易所项目必须为天标';
            return - 1;
        }
        if ($info['repay_style'] == 1 && $info['if_attorn'] != 1) {
            $this->errorMsg = '按月付息必须为月标';
            return - 1;
        }
        if ($info['apr'] < 0 || $info['apr'] > 24) {
            $this->errorMsg = '年率必须在0-24之间';
            return - 1;
        }
        if ($info['award_apr'] > 0) {
            if ($info['award_apr'] < 0.1 || $info['award_apr'] > 8) {
                $this->errorMsg = '奖金比率必须在0.1% 到 8%之间';
                return - 1;
            }
            $info['award_type'] = 1;
        }
        if ($info['bonus'] == 1) {
            if ($info['bonus_money'] % 100 !== 0) {
                $this->errorMsg = '投资奖励金额不是100的整数倍';
                return - 1;
            }
            if ($info['bonus_apr'] < 0.1) {
                $this->errorMsg = '投资奖励金额的比率不低于0.1%';
                return - 1;
            }
            $info['bonus_detail'] = json_encode(['bonus_apr' => $info['bonus_apr'], 'bonus_money' => $info['bonus_money']]);
        }
        unset($info['bonus_apr']);
        unset($info['bonus_money']);
        if ($info['portion_account'] == 0) {
            $this->errorMsg = '每份金额不能为0';
            return - 1;
        }
        $portion = $info['account'] / $info['portion_account'];
        if( intval( $portion ) == $portion ){
            $info['portion'] = intval( $portion );//份数
            $info['surplus'] = $info['portion'];
        } else {
            $this->errorMsg = '借款金额和每份金额的比率必须是正整数';
            return - 1;
        }
        if (!is_int($info['surplus'])) {
            $this->errorMsg = '每份';
            return - 1;
        }
        if ($info['is_recommend'] == 2) {
            $info['most_account'] = 20000;
        } elseif ($info['most_account'] > 0) {
            if ($info['most_account'] < $info['lowest_account']) {
                $this->errorMsg = '最高投资金额不能小于最低金额';
                return - 1;
            }
            if ($info['account'] < $info['most_account']) {
                $this->errorMsg = '最高投资金额不能大于借款金额';
                return - 1;
            }
        } else {
            $info['most_account'] = $info['account'];
        }
        if (!($info['borrow_name_type'] == self::GRJD || $info['borrow_name_type'] == self::CD || $info['borrow_name_type'] == self::XFD)) {
            $this->errorMsg = '此类型不支持受托支付';
            return - 1;
        }
        if ($info['intermediary_status'] == 2 && $info['repay_style'] == 4) {
            if (!$info['intermediary_rate']) {
                $this->errorMsg = '居间费率不能为空';
                return - 1;
            }
            $info['Intermediary_fee'] = 0;
        } else {
            if (!$info['Intermediary_fee']) {
                $this->errorMsg = '居间费不能为空';
                return - 1;
            }
            $info['intermediary_rate'] = 0;
        }
        $tags_detail = explode(',', $info['tags']['detail']);
        if (count($tags_detail) != 2) {
            $this->errorMsg = '描述标签至少选择两个';
            return - 1;
        }
        if (mb_strlen($info['tags']['list'], 'UTF-8') > 4) {
            $this->errorMsg = '列表标签（运营向）输入长度超长';
            return - 1;
        }
        if (mb_strlen($info['activity_info']['desc'], 'UTF-8') > 20) {
            $this->errorMsg = '标的活动描述输入长度超长';
            return - 1;
        }
        $info['tags'] = json_encode($info['tags']);
        $info['activity_info'] = json_encode($info['activity_info']);

        return $info;
    }

    public function saveProject($info, $borrowInfoB, $bid, $is_new, $otherParams = []) {
        $infoId = $borrowInfoB['id'];
        $time = time();
        unset($borrowInfoB['id']);
        if (isset($plInfo['id'])) {
            $plbid = $plInfo['id'];
            unset($plInfo['id']);
        }
        $borrowInfo = $this->validBorrowInfo($info);
        if ($borrowInfo === -1) {
            return $borrowInfo;
        }
        if ($bid && !$is_new) {
            $oldInfo = self::getBorrowByBid($bid, 'status, to_userid');
            if ($oldInfo['status'] > 1) {
                $this->errorMsg = '当前标的不处于可编辑状态';
                return - 1;
            }
            if ($borrowInfo['is_entrusted']) {
                if ($this->getBidByDebtor($borrowInfo['to_userid'])) {
                    $this->errorMsg = '借款人还有未确认的受托支付';
                    return - 1;
                }
            }

            if (Yii::$app->db->createCommand()->update(self::tableName(), $borrowInfo, ['bid' => $bid])->execute() === false) {
                $this->errorMsg = '数据库繁忙';
                return - 1;
            }
            //更新居间费统计表
            $checkIntermedirayStatusSql = Helps::createNativeSql("select id from xx_intermediary_fee where bid=$bid")->queryOne();
            if (($borrowInfo['intermediary_status'] !== '0' || $borrowInfo['intermediary_status'] == '0') && $checkIntermedirayStatusSql) {
                $intermediaryUpdate = Helps::createNativeSql("update xx_intermediary_fee set `intermediary_type`={$borrowInfo['intermediary_status']} where bid=$bid")->execute();
                $intermediaryInsert = true;
            } elseif ($borrowInfo['intermediary_status'] !== '0' && !$checkIntermedirayStatusSql) {
                $intermediaryInsert = Helps::createNativeSql("insert into xx_intermediary_fee (`bid`,`bid_name`,`wait_amount`,`already_amount`,`addtime`,`to_userid`,`intermediary_type`) values 
                      ($bid,'{$borrowInfo['title']}',{$borrowInfo['Intermediary_fee']},0,$time,{$borrowInfo['to_userid']},{$borrowInfo['intermediary_status']})")->execute();
                $intermediaryUpdate = true;
            } else {
                $intermediaryInsert = true;
                $intermediaryUpdate = true;
            }

            //生成合同方式
            $elecsignContractUpdateOrInsert = true;
            $elecsignContractSql = Helps::createNativeSql("select status from xx_elecsign_borrow where bid=$bid")->queryOne();
            if ($elecsignContractSql) {
                $elecsignContractUpdateOrInsert = Helps::createNativeSql("update xx_elecsign_borrow set `status`={$otherParams['contractType']} where bid=$bid")->execute();
            } elseif (!$elecsignContractSql && $otherParams['contractType'] > 0) {
                $elecsignContractUpdateOrInsert = Helps::createNativeSql("insert into xx_elecsign_borrow(`bid`,`status`,`addtime`) values ({$bid},{$otherParams['contractType']},$time)")->execute();
            }

            if ($intermediaryUpdate === false || $intermediaryInsert === false || $elecsignContractUpdateOrInsert === false) {
                $this->errorMsg = '数据库繁忙';
                return - 1;
            }

            Yii::$app->db->createCommand()->update('xx_borrow_info_b', $borrowInfoB, [ 'id' => $infoId])->execute();
        } else {
            if ($borrowInfo['is_entrusted']) {
                if ($this->getBidByDebtor($borrowInfo['to_userid'])) {
                    $this->errorMsg = '借款人还有未确认的受托支付';
                    return - 1;
                }
            }
            $borrowInfo['addtime'] = time();
            $borrowInfo['status'] = 0;
            $this->setAttributes($borrowInfo, false);
            if (!$this->save(false)) {
                $this->errorMsg = '数据库繁忙';
                return - 1;
            }
            $bid = $this->bid;
            $borrowInfoB['bid'] = $bid;
            $insertInfoB = Yii::$app->db->createCommand()->insert('xx_borrow_info_b', $borrowInfoB)->execute();
            //插入到居间费统计表
            if ($borrowInfo['intermediary_status'] !== '0') {
                $sql = "insert into xx_intermediary_fee (`bid`,`bid_name`,`wait_amount`,`already_amount`,`addtime`,`to_userid`,`intermediary_type`) values 
                      ($bid,'{$borrowInfo['title']}',{$borrowInfo['Intermediary_fee']},0,$time,{$borrowInfo['to_userid']},{$borrowInfo['intermediary_status']})";
                $insertIntermediary = Helps::createNativeSql($sql)->execute();
            } else {
                $insertIntermediary = true;
            }
            //插入合同
            if ($otherParams['contractType'] > 0) {
                $insertElecsignContract = Helps::createNativeSql("insert into xx_elecsign_borrow(`bid`,`status`,`addtime`) values ({$bid},{$otherParams['contractType']},$time)")->execute();
            } else {
                $insertElecsignContract = true;
            }
            if (!$insertInfoB && $createProcess && $insertIntermediary && $insertElecsignContract) {
                $this->errorMsg = '数据库繁忙';
                return -1;
            }
        }
        return $bid;
    }

    /**
     * 根据项目ID，把项目的内容拿出来
     * @param int $borrow_id
     * @param string $select
     * @return array
     */
    public static function getBorrowByBid($borrow_id, $select = '*') {

        $where = 'bid=' . $borrow_id;
        $list = self::find()
                ->select($select)
                ->where($where)
                ->asArray()
                ->one();
        return $list;
    }


    /**
     * 通过batch_no得到repay_confirm 的项目
     */
    public static function getBorrowByBatchNo($batch_no) {
        $success_time = time() - 24 * 3600 * 10; //不能超过10天
        //$where='batch_no = '.$batch_no.' and repay_confirm in(0,2)';
        $sql = "select b.bid as borrow_id,b.account,b.repay_confirm,b.status,b.debt_reg_status,b.limit_time,b.if_attorn,b.endpro,repay_style,b.success_time,b.to_userid,b.Intermediary_fee,b.lendpay_time,u.accountId,b.batch_no,b.is_entrusted,b.danbaoid,b.receipt_id,apr from xx_borrow as b left join xx_user as u on b.to_userid=u.user_id where b.batch_no ={$batch_no} and b.debt_reg_status=2 and b.repay_confirm in(0,2) and b.status = 4 and b.success_time>={$success_time}";
        $list = self::findBySql($sql)
                ->asArray()
                ->one();
        return $list;
    }

    public static function format($data) {

        foreach ($data as & $value) {
            $value['typeword'] = self::borrowTypeDesc($value['borrow_name_type']);
            if ($value['borrow_name_type'] == 8) {
                if ($value['success_time'] > 0) {
                    $value['limit_time'] = intval((strtotime(date('Ymd', $value['endpro'])) - strtotime(date('Ymd', $value['success_time']))) / 86400);
                } else {
                    $value['limit_time'] = intval((strtotime(date('Ymd', $value['endpro'])) - strtotime(date('Ymd', time()))) / 86400);
                }
            }
            $value['limit_day'] = $value['limit_time'];
            if ($value['if_attorn'] == 2) {
                $value['limit_time'] = $value['limit_time'] . "天";
            } else {
                $value['limit_time'] = $value['limit_time'] . "个月";
            }
            if ($value['auto_time'] > time()) {
                $value['foreshow'] = 1;
            } else {
                $value['foreshow'] = 0;
            }
            $tags = json_decode($value['tags'], true);
            $value['tags'] = isset($tags['list']) ? explode(',', $tags['list']) : [];
            if ($value['repay_style'] == 4) {
                $value['tags'][] = '等额本息';
            }
            $value['borrow_name'] = $value['name'] . '-' . $value['title'];
            $value['repay_style'] = self::getRepayStyle($value['repay_style']);
        }
        return $data;
    }

    /**
     * 根据借款人id查询标的列表
     * @param $user_id
     * @param $page
     * @param $pageSize
     * @param $status = [doing,done,all]
     * @return array
     */
    public static function getBorrowByDebtor($user_id, $page, $pageSize, $status) {
        $condition = 'to_userid=:to_userid';
        $params[':to_userid'] = $user_id;
        if ($status == 'doing') {
            $condition .= ' AND status = ' . self::STATUS_COLLECT_SUCCESS;
        } elseif ($status == 'done') {
            $condition .= ' AND status = ' . self::STATUS_REPAY_SUCCESS;
        }
        $count = self::find()->where($condition)->params($params)->count();
        $select = 'bid,title,name,account,limit_time,if_attorn,paid_period,lendpay_time,repay_style';
        $list = self::listTable($condition, $params, $select, $page, $pageSize, 'lendpay_time DESC');
        return ['total' => $count, 'list' => $list];
    }

    /**
     * 格式化显示借款信息
     * @param array $data
     * @return array
     */
    public static function formatDebtor($data) {
        $lists = [];
        foreach ($data as $val) {
            $all_period = self::getBorrowPeriod($val['bid']);
            $paid_period = BorrowCollection::getUnRepayPeriod($val['bid']);
            $list['bid'] = $val['bid'];
            $list['borrow_name'] = $val['name'] . '-' . $val['title'];
            $list['limit_time'] = $val['limit_time'];
            $list['all_period'] = $all_period;
            $list['account'] = $val['account'];
            $list['if_attorn'] = $val['if_attorn'];
            $list['lendpay_time'] = $val['lendpay_time'];
            $list['paid_period'] = $paid_period;
            if ($list['if_attorn'] == 1) {
                $list['period'] = $val['limit_time'];
            } else {
                if ($val['repay_style'] == 3) {
                    $list['period'] = ceil($val['limit_time'] / 90);
                } else {
                    $list['period'] = 1;
                }
            }
            $lists[] = $list;
        }
        return $lists;
    }

    /**
     * 获取标的期限
     * @param int $bid
     * @return int
     */
    public static function getBorrowPeriod($bid) {
        $all_period = BorrowCollection::find()->where('borrow_id=' . $bid)->max('period');
        if (empty($all_period)) {
            return '0';
        }
        return $all_period;
    }

}
