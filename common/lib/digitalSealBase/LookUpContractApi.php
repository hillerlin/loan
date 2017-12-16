<?php
namespace common\lib\digitalSealBase;

use api\handler\base\ApiErrorMsgText;
use linslin\yii2\curl\Curl;
use Yii;

/**
 * Created by PhpStorm.
 * User: Daisy
 * Date: 2017/11/12
 * Time: 20:24
 */
class LookUpContractApi extends DigitalSealBase
{
    /**
     * 接口链接
     *
     * @var string
     */
    private $api = 'lookupcontract';

    /**
     * 合同编号
     * 签约完成接口返回的合同编号
     *
     * @var string
     */
    private $contractNo;

    /**
     * @param mixed $contractNo
     * @return LookUpContractApi
     */
    public function setContractNo($contractNo)
    {
        $this->contractNo = $contractNo;
        return ($this);
    }

    public function getContractUrl() {
        $curl = new Curl();
        $params = [
            'contractNo' => $this->contractNo,
        ];
        $response = $curl->setPostParams($params)->post($this->url.$this->api);
        //请求出现错误
        if ($response === false) {
            //写日志
            Yii::info('error: connection failed'.$curl->errorCode.'-'.$curl->errorText . '.  data:' . json_encode($params), 'digitalSeal');
            $this->setErrorNo(11001)->setErrorMsg(ApiErrorMsgText::ERROR_11001);
            return false;
        }
        $response = json_decode($response, true);
        if ($response['code'] != 200) {
            Yii::info('error: digitalSeal request failed'.$response['code'].'-'.$response['message'] . '.  data:' . json_encode($params), 'digitalSeal');
            $this->setErrorNo(17101)->setErrorMsg($response['message'] ?: ApiErrorMsgText::ERROR_17101);
            return false;
        }
        return $response['list'];
    }

}