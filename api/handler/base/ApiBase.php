<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 2016/8/2
 */

namespace api\handler\base;
use common\lib\ParameterUtil;
use common\models\LoanMerchant;
use common\lib\VerifyUtil;
use common\models\LoanPacket;
use Yii;


/**
 * API 抽象基类
 *
 * Class ApiBase
 * @package ApiBundle\Handler\Base
 */
abstract class ApiBase
{
    /**
     * 接口参数
     *
     * @var string
     */
    private $parameters;

    /**
     * 错误码
     *
     * @var int
     */
    private $error;


    /**
     * 错误信息
     *
     * @var string
     */
    private $error_msg;

    /**
     * 秘钥
     *
     * @var int
     */
    private $key;

    /**
     * API 接口返回的 DATA 数据
     *
     * @var array
     */
    private $data;

    /**
     * 接口需要直接返回的参数
     *
     * @var array
     */
    private $inherit;

    /**
     * API 接口传入的参数验证规则
     * @var array
     */
    private $validation_rules;

    /**
     * 报文对应id
     * @var int
     */
    private $packet_log_id;

    /**
     * 是否是本地
     * @var int
     */
    private $is_local = false;


    /**
     * 接口执行结果，抽象方法，由各接口实现
     *
     * @return bool
     */
    abstract public function getResult();

    /**
     * 暂存错误码
     *
     * @param $error    : 错误码
     * @return ApiBase
     */
    protected function setErrorCode($error)
    {
        $this->error = $error;

        return ($this);
    }

    /**
     * 返回错误码
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->error?$this->error:0;
    }

    /**
     * 暂存错误信息
     *
     * @param string $error_msg : 错误信息文本内容
     * @return ApiBase
     */
    protected function setErrorMsg($error_msg)
    {
        $this->error_msg = $error_msg;

        return ($this);
    }

    /**
     * 返回错误信息
     *
     * @return string
     */
    public function getErrorMsg()
    {
        $error = $this->getErrorCode();
        $error_msg = $this->error_msg?$this->error_msg:'';

        if($error == 000000 && $error_msg == '') $error_msg = ApiErrorMsgText::SUCCESS_000000;

        //10000~11000之间的值为内部错误码，错误信息不对外显示，统一返回“系统错误”
        if($error > 11000 && $error < 13000)    $error_msg = ApiErrorMsgText::ERROR_11001;
//
//        //数据库错误未指明具体错误信息时统一返回预设错误信息
//        if($error == 12000 && $error_msg == '') $error_msg = ApiErrorMsgText::ERROR_12000;
//        if($error == 12001 && $error_msg == '') $error_msg = ApiErrorMsgText::ERROR_12001;
//        if($error == 12002 && $error_msg == '') $error_msg = ApiErrorMsgText::ERROR_12002;

        return $error_msg;
    }

    /**
     * 暂存返回的 DATA 数据
     *
     * @param array $data
     * @return ApiBase
     */
    protected function setData(array $data)
    {
        $this->data = $data;

        return ($this);
    }

    /**
     * 返回 DATA 数据
     *
     * @return array
     */
    public function getData()
    {
        return $this->data?$this->data:array();
    }

    /**
     * 返回报文对应id
     * @return int
     */
    public function getPacketLogId()
    {
        return $this->packet_log_id;
    }

    /**
     * 返回基础参数
     * @return array
     */
    public function getInherit()
    {
        return $this->inherit;
    }

    /**
     * @param bool $is_local
     * @return ApiBase
     */
    public function setIsLocal($is_local)
    {
        $this->is_local = $is_local;

        return ($this);
    }

    /**
     * @return int
     */
    public function getIsLocal()
    {
        return $this->is_local;
    }

    /**
     * 返回已通过校验的参数
     *
     * @param string $key : 指定需要获取的参数KEY
     * @return array|int|string
     */
    protected function getParameters($key = '')
    {
        return ($key == '')?$this->parameters:(isset($this->parameters[$key])?$this->parameters[$key]:null);
    }

    /**
     * 验证参数校验规则，参数完整性，参数类型是否符合预设
     * @param $rules
     * @return bool
     */
    public function verify($rules='')
    {
        if($this->checkFixedParameters())
            if($this->checkMerchantId())
                if($this->checkRules($rules)->checkParameter())
                    if($this->checkSign()){
                        return true;
                    }

        return false;
    }


    /**
     * 校验传入的验证规则格式正确性
     * @param $rules
     * @return ApiBase|bool
     */
    private function checkRules($rules)
    {
        if(is_array($rules)){
            $rules_checking = array();
            foreach($rules as $rule){
                if(!is_array($rule) || count($rule)==0 || count($rule)>5 || $rule[0] == ''){
                    $this->error = 9000;
                    $this->error_msg = '参数校验规则错误';
                    return false;
                }

                if(!isset($rule[1]))    $rule[1] = false;
                if(!isset($rule[2]))    $rule[2] = NULL;
                if(!isset($rule[3]))    $rule[3] = NULL;

                $rules_checking[] = $rule;
            }

            $this->setValidationRules($rules_checking);
        }

        return ($this);
    }


    /**
     * 校验参数是否必须，校验参数类型是否符合预设
     *
     * @return bool
     */
    private function checkParameter()
    {
        $rules = $this->getValidationRules();

        if(count($rules)>0){
            $result = array();
            foreach($rules as $param_info){
                $value = $this->getPost($param_info[0]);

                //参数未提交
                if(is_null($value)||$value==''){
                    //判断参数是否必须
                    if($param_info[1]){
                        $this->error = 13003;
                        $this->error_msg = str_replace('{name}',$param_info[0],ApiErrorMsgText::ERROR_13003);
                        return false;
                    }

                    //如果有默认值则为参数添加默认值
                    if(!is_null($param_info[3]))    $value = $param_info[3];

                    //参数有提交
                }else{
                    if (!is_null($param_info[2])) {
                        if ($param_info[2] == 'id_no') {
                            $value = strtoupper($value);
                        }
                        if ($param_info[2] == 'enum') {
                            $value = VerifyUtil::getEnumValue($value, $param_info);
                            if ($value === false) {
                                $this->error = 13005;
                                $this->error_msg = str_replace('{name}',$param_info[0],ApiErrorMsgText::ERROR_13005);
                                return false;
                            }
                        } elseif (VerifyUtil::check($value,$param_info[2]) === false) {
                            $this->error = 13004;
                            $this->error_msg = str_replace('{name}',$param_info[0],ApiErrorMsgText::ERROR_13004);
                            return false;
                        }
                    }
                }

                $result[$param_info[0]] = $value;
            }

            $this->parameters = $result;
        }

        return true;
    }

    /**
     * 校验固定参数是否提交
     * @return bool
     */
    private function checkFixedParameters()
    {
        if (!$this->is_local) {
            if(is_null($this->getPost('version'))
                || is_null($this->getPost('service'))
                || is_null($this->getPost('merchantId'))
                || is_null($this->getPost('txDate'))
                || is_null($this->getPost('txTime'))
                || is_null($this->getPost('seqNo'))
                || is_null($this->getPost('sign'))
                || is_null($this->getPost('channel'))
            ){
                $this->error = 13001;
                $this->error_msg = ApiErrorMsgText::ERROR_13001;
                return false;
            }
            $this->packet_log_id = \common\models\LoanPacket::addOneLog($this->getPost());
            if (!$this->validFixedParams()) {
                return false;
            }
            $this->inherit = [
                'version' => $this->getPost('version'),
                'service' => $this->getPost('service'),
                'merchantId' => $this->getPost('merchantId'),
                'txDate' => $this->getPost('txDate'),
                'txTime' => $this->getPost('txTime'),
                'seqNo' => $this->getPost('seqNo'),
                'channel' => $this->getPost('channel'),
                'acqRes' => $this->getPost('acqRes'),
            ];
            if (!$this->validRequestTimes()) {
                return false;
            }
        }
        return true;
    }

    /**
     * 过滤头部参数
     * @return bool
     */
    private function validFixedParams()
    {
        if (!YII_DEBUG || !$this->getPost('dev')) {
            //判断版本号
            if ($this->getPost('version')!=Yii::$app->params['version']) {
                $this->setErrorCode(13007)->setErrorMsg(ApiErrorMsgText::ERROR_13007);
                return false;
            }
            //判断请求时间
            $time = time();
            $requestTime = strtotime($this->getPost('txDate') . $this->getPost('txTime'));
            if ($time < $requestTime) {
                $this->setErrorCode(13010)->setErrorMsg(ApiErrorMsgText::ERROR_13010);
                return false;
            }
            //判断“交易日期txDate”+“交易时间txTime”+“交易流水号seqNo”是否唯一
            if (LoanPacket::selectCount(['txDate' => $this->getPost('txDate'), 'txTime' => $this->getPost('txTime'), 'seqNo' => $this->getPost('seqNo')])>1) {
                $this->setErrorCode(13006)->setErrorMsg(ApiErrorMsgText::ERROR_13006);
                return false;
            }
            //判断交易渠道
            if (!in_array($this->getPost('channel'),['000001', '000002', '000003', '000004'])) {
                $this->setErrorCode(13008)->setErrorMsg(ApiErrorMsgText::ERROR_13008);
                return false;
            }
        }
        return true;
    }

    /**
     * 防止暴力请求
     * @return bool
     */
    private function validRequestTimes()
    {
        $redis = Yii::$app->redis;
        $ip = Yii::$app->getRequest()->getUserIP();
        $key = 'request:'.$this->getPost('service').':'.$ip;
        $requestExist = $redis->GET($key);
        if (!$requestExist) {
            $redis->SETEX($key, 5, time());
            return true;
        }else{
            $this->setErrorCode(13011)->setErrorMsg(ApiErrorMsgText::ERROR_13011);
            return false;
        }
    }

    /**
     * 校验 merchantId 参数是否为指定值
     *
     * @return bool
     */
    private function checkMerchantId()
    {
        if (!$this->is_local) {
            $key = LoanMerchant::getMerchantInfoById($this->getPost('merchantId'), 'key');
            if ($key) {
                $this->key = $key;
                return true;
            }

            $this->setErrorCode(13002)->setErrorMsg(ApiErrorMsgText::ERROR_13002);
            return false;
        }elseif ($this->getPost('merchantId')){
            $session = Yii::$app->session;
            $userInfo = $session->get('userInfo');
            if ($userInfo['merchant_id'] != $this->getPost('merchantId')) {
                $this->setErrorCode(13002)->setErrorMsg(ApiErrorMsgText::ERROR_13002);
            }

        }
        return true;
    }

    /**
     * 校验参数是否接收完整
     * 客户端校验参数加密方式：
     *      1. sign 除外的参数按键值升序排列
     *      2. 使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串
     *      3. MD5方式加密以上步骤得到的字符串
     *      4. 将加密结果放入 sign 提交到服务端供参数校验
     * @return bool
     */
    private function checkSign(){
        if (!$this->is_local) {
            $params = $this->getPost();
            if (!YII_DEBUG || isset($params['dev']) === false) {
                $sign = $params['sign'];
                unset($params['sign']);
                if (ParameterUtil::getSignFromData($params, $this->key) != $sign) {
                    $this->error = 13009;
                    $this->error_msg = ApiErrorMsgText::ERROR_13009;
                    return false;
                }
            }
        }
        return true;
    }

    public function SignData($params)
    {
        if ($this->inherit) {
            $params = array_merge($this->inherit, $params);
            $params['sign'] = ParameterUtil::getSignFromData($params, $this->key);
        }
        Yii::info('return data:' . json_encode($params));
        return $params;
    }

    /**
     * @return array
     */
    public function getValidationRules()
    {
        return $this->validation_rules;
    }


    /**
     * 暂存 API 参数验证规则
     * 参数 $rules 为一维数组，各键值含义：
     *      0   : 参数名称
     *      1   : 是否必须参数(true：是 | false：否)
     *      2   : 参数类型(number,mobile,email,date,datetime,int,array,json)
     *      3   : 参数默认值
     *
     * 例：
     *  $rules=array('phone',true,'mobile');参数'phone'为必须参数，必须符合手机号码格式
     *  $rules=array('email',false,'email','');参数'email'为非必须参数，若有提交必须符合'email'格式，若未提交默认空字符
     *
     * @param array $validation_rules      : 参数校验规则
     */
    public function setValidationRules($validation_rules)
    {
        $this->validation_rules = $validation_rules;
    }

    public function getPost($key = '')
    {
        $request = Yii::$app->request;
        return ($key == '') ? $request->post() : $request->post($key);
    }

}