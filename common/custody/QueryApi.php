<?php

/*
 * To change this license header] =  choose License Headers in Project Properties.
 * To change this template file] =  choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\custody;

use Yii;
use common\lib\custody\CustodyApi;

/**
 * Description of UserApi
 * 用户处理类
 * 只做数据封装，不做逻辑处理
 * @author Administrator
 * @datetime 2017-3-9 16:26:20
 */
class QueryApi extends CustodyApi {

    /**
     * 获取上一次发送验证码的服务码
     * @param type $user_id
     * @return type
     */
    public static function getLastSrvAuthCode($user_id) {
        return Yii::$app->redis->GET($user_id . ':srvAuthCode');
    }

    //2.6交易明细全流水文件
    public function getAleveFile($date) {
        return $this->getFile('ALEVE', $date);
    }
    
    //获取文件
    public function getFile($file_type, $date) {
        $data['fileName'] = $this->config['sys']['bankinstcode'] . '-' . $file_type . $this->config['sys']['product'] . '-' . $date;
        $data['txDate'] = $date;
        return $this->submitGetFile($data);
    }
    
    //用于查询单笔充值、提现、红包发放、红包发放撤销（本接口查询的时间为近两天单笔交易业务）
    public function fundTransQuery($account, $txDate, $txTime, $seqNo) {
        $data['accountId'] = $account;
        $data['orgTxDate'] = $txDate; //0为查询
        $data['orgTxTime'] = $txTime;
        $data['orgSeqNo'] = $seqNo;
        $data['txCode'] = 'fundTransQuery';
        return $this->submitApi($data);
    }

}
