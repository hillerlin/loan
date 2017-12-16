<?php
namespace common\lib\jf;

use api\handler\base\ApiErrorMsgText;
use common\models\LoanCreditReport;
use Yii;

/**
 * Created by PhpStorm.
 * User: Daisy
 * Date: 2017/11/12
 * Time: 20:24
 */
class JfApi
{
    /**
     * 查询数据需要的口令
     *
     * @var string
     */
    private $token;

    /**
     * 账号
     *
     * @var string
     */
    private $appId = '15816855337';

    /**
     * 密码
     *
     * @var string
     */
    private $appSign = '5337a9cb759cf434';

    /**
     * 秘钥
     *
     * @var string
     */
    private $appSecret = 'b736dca190a8';

    /**
     * 接口链接
     *
     * @var string
     */
    private $url = 'http://jifeng.damailicai.com/data';

    /**
     * 错误码
     *
     * @var string
     */
    private $errorNo;

    /**
     * 错误信息
     *
     * @var string
     */
    private $errorMsg;

    public function getToken()
    {
        $redis= Yii::$app->redis;
        $key = "jf";
        $date = date('Y-m-d');
        $getDate = $redis->HGET($key, 'date');
        if ($getDate == $date) {
            $this->token = $redis->HGET($key, 'token');
        }else{
            if ($this->requestToken()) {
                $redis->HSET($key,'date',$date);
                $redis->HSET($key,'token',$this->token);
            }
        }
        return ($this);
    }

    /**
     * 获取token
     * @return bool
     */
    public function requestToken()
    {
        $requestArr = [
            'appId' => $this->appId,
            'appSign' => $this->appSign,
        ];
        $requestStr = '';
        foreach($requestArr as $key => $value){
            $requestStr .= $key.'='.$value.'&';
        }
        $requestStr = substr($requestStr,0,-1);
        $url = $this->url . '?' . $requestStr;
        $result = \common\lib\Helps::http( $url, '', 'GET', 'str' );
        if (!$result) {
            $this->errorNo = 16500;
            $this->errorMsg = ApiErrorMsgText::ERROR_16500;
            return false;
        }
        $result = json_decode($result, true);
        if (isset($result['code']) && $result['code'] != 200) {
            $this->errorNo = '16' . $result['code'];
            $this->errorMsg = '征信系统-' . $result['body'];
            return false;
        } else {
            $this->token = $result['body']['token'];
            return true;
        }
    }

    /**
     * 获取征信数据
     * @param $param
     * @return bool
     */
    public function requestData($param)
    {
        if (!$this->token) {
            return false;
        }
        $requestArr = [
            'token' => $this->token,
            'appSecret' => $this->appSecret,
            'name' => $param['real_name'],
            'idCard' => $param['id_no'],
            'phone' => $param['mobile'],
        ];
        $result = \common\lib\Helps::http( $this->url, $requestArr, 'POST', 'json' );
        Yii::info('jf-request:result-' . json_encode($result) . ',params-' . json_encode($requestArr), 'jf');
        if (!$result) {
            $this->errorNo = 16500;
            $this->errorMsg = ApiErrorMsgText::ERROR_16500;
            return false;
        }
        if (!LoanCreditReport::findByBorrowerInfo($param)) {
            $model = new LoanCreditReport();
            $param['report'] = json_encode($result);
            $model->addOneLog($param);
        }
        if (isset($result['code']) && $result['code'] != 200) {
            $this->errorNo = '16' . $result['code'];
            $this->errorMsg = '征信系统-' . $result['body'];
            return false;
        } else {
            return $result['body']['calculated_result'];
        }
    }

    /**
     * @return string
     */
    public function getErrorNo()
    {
        return $this->errorNo;
    }

    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }


}