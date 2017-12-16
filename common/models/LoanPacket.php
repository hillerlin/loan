<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\models;

use common\lib\ParameterUtil;
use common\lib\Queue;
use Yii;
/**
 * LoanPacket model
 *
 * @property integer $id
 * @property integer $version
 * @property string $service
 * @property integer $merchantId
 * @property integer $txDate
 * @property integer $txTime
 * @property integer $seqNo
 * @property integer $channel
 * @property string $packet
 */
class LoanPacket extends DmActiveRecord{
    
    public static function tableName() {
        return '{{%loan_packet}}';
    }

    public static function findIdentity($id) {
        return static::findOne(['id' => $id]);
    }

    public static function addOneLog($params){
        
        $data = [
            'version' => isset($params['version']) ? $params['version'] : '',
            'service' => isset($params['service']) ? $params['service'] : '',
            'merchantId' => isset($params['merchantId']) ? $params['merchantId'] : '',
            'txDate' => isset($params['txDate']) ? $params['txDate'] : '',
            'txTime' => isset($params['txTime']) ? $params['txTime'] : '',
            'seqNo' => isset($params['seqNo']) ? $params['seqNo'] : '',
            'channel' => isset($params['channel']) ? $params['channel'] : '',
            'packet' => json_encode($params)
            ];
        if (Yii::$app->db->createCommand()->insert(self::tableName(), $data)->execute()) {
            return Yii::$app->db->getLastInsertID();
        } else {
            return false;
        }
    }

    public function formatNotifyData($logId, $callBackData, $inherit=[])
    {
        $packet = self::findIdentity($logId)->packet;
        if ($packet) {
            $requestData = json_decode($packet, true);
            $data = [
                'version' => isset($requestData['version']) ? $requestData['version'] : '',
                'service' => isset($requestData['service']) ? $requestData['service'] : '',
                'merchantId' => isset($requestData['merchantId']) ? $requestData['merchantId'] : '',
                'txDate' => isset($requestData['txDate']) ? $requestData['txDate'] : '',
                'txTime' => isset($requestData['txTime']) ? $requestData['txTime'] : '',
                'seqNo' => isset($requestData['seqNo']) ? $requestData['seqNo'] : '',
                'channel' => isset($requestData['channel']) ? $requestData['channel'] : '',
                'acqRes' => isset($requestData['acqRes']) ? $requestData['acqRes'] : '',
            ];
            foreach ($inherit as $v) {
                $data[$v] = isset($requestData[$v]) ? $requestData[$v] : '';
            }
            $data = array_merge($data, $callBackData);
            $key = LoanMerchant::getMerchantInfoById($data['merchantId'], 'key');
            $data['sign'] = ParameterUtil::getSignFromData($data, $key);
            $notifyData = [
                'url' => $requestData['notifyUrl'],
                'data' => $data,
            ];
            return $notifyData;
        } else {
            return false;
        }
    }

    public function notify($data)
    {
        if ($data) {
            Yii::info('notify data: ' . json_encode($data), 'merNotify');
            if ($data['url'] && $data['data']) {
                $params = json_encode($data);
                $result = \common\lib\Helps::http( $data['url'], $data['data'], 'POST', 'str' );
                if ($result != 'success') {
                    Yii::error('merchant-notify:requestFail-' . $result . ',params-' . $params , 'merNotify');
                    Queue::push('merchantNotify', $data);
                }
            }
        }
    }

    /**
     * 拼接主动回调参数
     * @param $url
     * @param $data
     * @return array
     */
    public function formatCallData($url, $data)
    {
        $time = time();
        $param = [
            'version'    => Yii::$app->params['version'],
            'txDate'     => date('Ymd', $time),
            'txTime'     => date('His',$time),
            'seqNo'      => date('His',$time),
            'channel'    => 000001,
            'acqRes'     => '',
        ];
        $data = array_merge($param, $data);
        $key = LoanMerchant::getMerchantInfoById($data['merchantId'], 'key');
        $data['sign'] = ParameterUtil::getSignFromData($data, $key);
        self::addOneLog($data);
        $notifyData = [
            'url' => $url,
            'data' => $data,
        ];
        return $notifyData;
    }
}
