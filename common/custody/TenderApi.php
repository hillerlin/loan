<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\custody;

use Yii;
use common\lib\custody\CustodyApi;

/**
 * Description of TenderApi
 *
 * @author Administrator
 * @datetime 2017-3-29 16:11:41
 */
class TenderApi extends CustodyApi {
    
    //签约自动投资接口
    public function bidAutoApply($params) {
        $keys = ['accountId', 'orderId', 'txAmount', 'productId', 'bonusFlag', 'contOrderId'];
        $data = $this->map($keys, $params);
        $data['txCode'] = 'bidAutoApply';
        $data['frzFlag'] = '1';
        return $this->submitApi($data);
    }
}
