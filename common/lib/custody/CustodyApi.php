<?php
namespace common\lib\custody;

use Yii;
use linslin\yii2\curl;
use common\models\Batch_records;
/** 
 *
 * @author edgar
 */
class CustodyApi {

    public $send_type =0;
    protected $errMsg;
    protected $errNo;
    protected $config;
    protected $source_channel;
    protected $isSuccess;
    const CHANNEL_PC = '000002';
    const CHANNEL_APP = '000001';
    const CHANNEL_H5 = '000003';
    const SUCCESS = '00000000';     //单接口成功标示
    const BATCH_SUCCESS = 'success';     //批量接口返回的成功标示

    public function __construct($channel = ''){
        $this->config = Yii::$app->params['custody'];
        if(empty($channel)){
            $this->source_channel = self::CHANNEL_PC;
        }else{
            $this->source_channel = $channel;
        }
    }
    public function getSeqNo(){
        $f_r=(int)$this->source_channel;
        $t=microtime(true);
        $t = explode('.', $t);
        $f_2=end($t);
        if (strlen($f_2) == 2) {
            $f_2 = '0' . $f_2;
        }elseif(strlen($f_2) == 1) {
            $f_2 = '00' . $f_2;
        }
        $f_3=mt_rand(100,999);//生成三维随机数
        if(mt_rand()%2 == 0){
            $str=$f_r.$f_2.$f_3;
        }else{
            $str=$f_r.$f_3.$f_2;
        }
        return substr($str,0,6);
    }
    //得到批次号,一月之内不会重复
    public function getBatchNo(){
        $f_r=(int)date('dHi',time());
        $f_r=$f_r+mt_rand(100000,400000);
        $s=(int)date('s',time());
        $t=microtime(true);
        $t = explode('.', $t);
        $f_r=$f_r+end($t)+$s+mt_rand(1000,300000);
        return (string)$f_r;
        
    }
    public function setSendType($send_type){
        $this->send_type=$send_type;
    }

    /**
     * 通用的信息
     * @param  [type] $data_req [description]
     * @return [type]      [description]
     */
    public function getHeaderReq($data_req){
        $req['version'] = $this->config['sys']['version'];
        $req['instCode'] = $this->config['sys']['instcode'];
        $req['bankCode'] = $this->config['sys']['bankcode'];
        if(!array_key_exists('channel',$data_req)){
            $req['channel'] = $this->source_channel;
        }
        if(!array_key_exists('txDate',$data_req)) {
            $req['txDate'] = $this->getTxDate();
        }
        if(!array_key_exists('txTime',$data_req)) {
            $req['txTime'] = $this->getTxTime();
        }
        if(!array_key_exists('seqNo',$data_req)) {
            $req['seqNo'] = self::getSeqNo();
        }
        if(isset($data_req['fileName'])) {
            $req['txDate'] = $data_req['txDate'];
            unset($req['version']);
            unset($req['seqNo']);
            unset($req['txTime']);
            unset($req['channel']);
            unset($data_req['txDate']);
        }

        return array_merge($req,$data_req);

    }
    
    public function getTxDate() {
        return date('Ymd',time());
    }
    
    public function getTxTime() {
        return date('His',time());
    }
    /**
     * 根据$data获取加密的sign值
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getSign($data){
        if(empty($data)){
            Yii::error("CustodyApi::getSign data empty.", 'custody_error');
            return null;
        }
        $private_key = $this->config['key']['keys'];
        if(!file_exists($private_key)) {
            Yii::error("CustodyApi::getSign file_exists error,private_key:".$private_key, 'custody_error');
            return null;
        }
        $pkcs12 = file_get_contents($private_key);
        if (openssl_pkcs12_read($pkcs12, $certs,$this->config['key']['pass'])) {
            $privateKey = $certs['pkey'];
            $publicKey = $certs['cert'];
            /**
             * hash算法
             * OPENSSL_ALGO_SHA1
             * OPENSSL_ALGO_MD5
             * OPENSSL_ALGO_MD4
             * OPENSSL_ALGO_MD2
             * java中RSA SHA1withRSA
             */
            if (openssl_sign($data, $binarySignature, $privateKey, OPENSSL_ALGO_SHA1)) {
                return base64_encode($binarySignature);
            } else {
                Yii::error("CustodyApi::getSign openssl_sign error.", 'custody_error');
            }
        } else {
            Yii::error("CustodyApi::getSign openssl_pkcs12_read error.", 'custody_error');
        }
    }
    public function verifySignStr($data,$sign){
        if(empty($sign)){
            return null;
        }

        $public_key = $this->config['key']['crt'];
        if(!file_exists($public_key)){
            Yii::error("CustodyApi::verifySignStr file_exists error,public_key:".$public_key, 'custody_error');
            return null;
        }
        $cer_key= file_get_contents($public_key);
        $cer = openssl_pkey_get_public($cer_key);
        $result = (bool)openssl_verify($data,base64_decode($sign),$cer, OPENSSL_ALGO_DSS1);
        var_dump($result);
        /*
        $decryptData = '';
        if (openssl_public_decrypt($unSignMsg, $decryptData, $cer)) {
            return $decryptData;
        }*/
        return null;
    }
    /**
     * 根据数据的值得到str
     * @param  [type] $req [description]
     * @return [type]      [description]
     */
    public function getSignStr($req){
        ksort($req);
        $v='';
        foreach ($req as $key => $val) {
            $v.=$val;
        }
        return $v;
    }
    /**
     * 根据业务数组得到要发送的json
     * @param  [type] $req [description]
     * @return [type]      [description]
     */
    public function getJson($req){
        if (YII_DEBUG) {
            isset($req['mobile']) ? $req['mobile'] = '1110' . substr($req['mobile'], 4) : '';
            isset($req['name']) ? $req['name'] = mb_substr($req['name'], 0, 1) . '测试账户' . mb_substr($req['name'], 1)  : '';
//            isset($req['mobile']) ? $req['acqRes'] = substr($req['mobile'], 0, 4) : '';
        }
        $req = self::getHeaderReq($req);
        $v = self::getSignStr($req);
        $sign = self::getSign($v);
        if(isset($req['fileName']))
        {
            $req['SIGN']=$sign;
        }else
        {
            $req['sign'] = $sign;
        }

        return $req;
    }
    /**
     * 把请求数据放到数据库中
     */
    public function addLog($params){
        if($params['txCode'] == 'balanceQuery' || $params['txCode']=='batchQuery'){//这2个不写库了
            return;
        }
        $batch_no = $txAmount = $txCounts = $borrow_id = 0;
        if (array_key_exists('batchNo', $params)) {
            $batch_no = $params['batchNo'];
        }
        if (array_key_exists('txAmount', $params)) {
            $txAmount = $params['txAmount'];
        }
        if (array_key_exists('txCounts', $params)) {
            $txCounts = $params['txCounts'];
        }
        if (array_key_exists('productId', $params)) {
            $borrow_id = $params['productId'];
        }

        $data=['batch_no'=>$batch_no,'status'=>2,'txDate'=>$params['txDate'],'txTime'=>$params['txTime'],'seqNo'=>$params['seqNo'],'txAmount'=>$txAmount,'txCounts'=>$txCounts,'txCode'=>$params['txCode'],'send_type'=>$this->send_type,'addtime'=>time(),'remark'=>json_encode($params)];
        Batch_records::addLog(0,$data);
    }


    public function getJsonTest($req){
//        $str='62124612700000047013005000000000200770001264338balanceQuery2017030709294410';
//        return self::getSign($str);
//        $sign='EOJI+sTm3MOVOI92CiH+Cp4QpsbHd1/ISdgiX8Tmx/tWpHLi6Avpcap5Nz/L4/1REkte57WOdFRCBsWHX74CG9rUFQXfv5QwLE6unPWMbZcoJ4Gt2ovuW87ThqcVIrMkTpXVVLcJEfADemrWWmmQPZX6W7eQBEtOXl2t+KdKBj09qBM6Ds83xvIBMX0ed0Sn7prrLM6/QlpdqLXsQyc6CfH/0kmdguqo26McFyXTrL/IM3jOI6KcFUkgtW3KtxpzrfpsTkn6keI+ZA3LHUaqzHOn7bV5A0QChvjlVggg/e16vv8DbIlPsCrwmINpHIK0h8WVJ8RR9Sio4iHgPI8h5w==';
        /*
        $r='{"version":"10","instCode":"00770001","bankCode":"30050000","txDate":"20170307","txTime":"092944","channel":"000002","seqNo":"264338","txCode":"balanceQuery","accountId":"6212461270000004701"}';


        $req = self::getHeaderReq($req);
        print_r($req);
        echo "<br/>";echo "<br/>";
        $v = self::getSignStr($req);
        echo $v;echo "<br/>";echo "<br/>";
        $sign = self::getSign($v);
        echo $sign;echo "<br/>";echo "<br/>";
        $req['sign'] = $sign;
        return json_encode($req);*/
       
//       $str='30050000230251094447000002JX90001510FES系统验签失败621246127000000470100770001balanceQuery20170306';
        var_dump($this->getJson($req));
        $str = $this->getSignStr($req);
       $sign='DrykgRqKsbsJRfdTSp5YkrttLN8/nCM3JuC5am0abWr6GCPM1+b4sThpBTPUXkrmWg5tCOgO5Q67tSv0XgC59v0oxpJdgdx2mWSx5CCUfDavqDnXikqy+HXnuZr7stHOyt6EbXRzSNVwLBDowEVqSKraqmH/p8hP34N214dzBPWeLYCdixGncm7udCRBEmREQNfWw0WgrUrfBXLxdCFVapAFid3l3ebaBpIgi3c4k0VJqYV0OqkYOUa0+FvdiupbeTf7941dFVU3CghdozxODivGY3k2/T3tSQ570/O78PFsHTwYLfhZ1PqeTuQraHc7tHMiZAkK4GOk9L+kCe2egg==';
       //$sign='BGZ5Y0ddEMf3GoSEsHZYlLKEXfIwe782mJx7qQ9T2+mKirvqx4+gJHjrQAFMG2d1UYEMWFFj8ZhbNttHyPh69SNMMElQTMnGwcB0VgKJYbervf12tIWhfdOWG3sbzVUT6xoYUcHjcqjc4OFPoeNa4qmQPAAPTVshpMKL2kwrFFYqqoY=';
       var_dump($sign);
       return self::verifySignStr($str,$sign);
       
    }
    
    //验证接收信息
    public function validateSign($result) {
        $this->validateSuccess($result);
        return true;
    }
    /**
     * 使用api接口提交信息
     * @param type $param
     * @return type
     */
    public function submitApi($param) {
        $params=self::getJson($param);
        if(!isset($param['fileName'])) {
            Batch_records::addOneLog($params);
            $postUrl=$this->config['sys']['uri'];
        } else {
            $postUrl= $this->config['sys']['fileDownload'];
        }

        $data_string = json_encode($params);
        //var_dump($data_string);die;
        $curl = new curl\Curl();
        $options = [CURLOPT_HTTPHEADER => ['Content-Type: application/json']];
        $responce = $curl->setOptions($options)->setRequestBody($data_string)->post($postUrl);
        //请求出现错误
        if ($responce === false) {
            //写日志
            Yii::info('error: connection failed'.$curl->errorCode.'-'.$curl->errorText . '.  data:' . $data_string, 'custody_error');
            return false;
        }
        $result = json_decode($responce, true);
        
        if (self::validateSign($result) === false) {
            Yii::info('error: validate failed.  send: ' . $data_string . "\r\nreceive: " . $responce, 'custody_error');
            return false;
        }
        Yii::info('send: ' . $data_string . "\r\nreceive: " . $responce, 'cusdoy_success');
        if ((isset($result['returnCode']) && $result['returnCode'] === '0000') || (isset($result['retCode']) && $result['retCode'] === self::SUCCESS) || (isset($result['received']) && $result['received'] === self::BATCH_SUCCESS)) {
            return $result;
        } else {
            $this->errMsg = $this->switchErr($result['retCode'], $result['retMsg']);
            return false;
        }
    }
    
    //组装post表单
    public function submitForm($params, $type=null) {
        $params['notifyUrl'] = $this->config['sys']['notifyUrl'];
        if(! isset( $params['retUrl'] ) ){
            $retUrl = $this->config['retUrl'][$this->source_channel];
            $params['retUrl'] = \yii\helpers\Url::to($retUrl, true);
            $params['retUrl'] .= '?type=' . $params['txCode'];
        }
        $form_url = $this->config['formUrl'];
        $form = self::getJson($params);
        Batch_records::addOneLog($form);
        $url = isset($params['url']) ? $params['url'] : $form_url[$params['txCode']];
        return ['form' => $form, 'url' => $url];
    }
    
    //字段映射
    public function map($keys ,$params) {
        $map = Yii::$app->params['custody_map'];
        foreach ($keys as $key) {
            if (!isset($map[$key])) {
                throw new \yii\base\InvalidValueException($key . '未做映射');
            }
            //存管字段对应的内部字段或者数组
            $map_val = $map[$key];
            if (is_array($map_val)) {
                foreach ($map_val as $val) {
                    if (isset($params[$val])) {
                        $map_val = $val;
                    }
                }
            }
            if (!is_string($map_val) || !isset($params[$map_val])) {
                throw new \yii\base\InvalidValueException($key . '未赋值');
            }
            $data[$key] = $params[$map_val];
        }
        return $data;
    }
    
    //返回错误信息
    public function getError() {
        return $this->errMsg ?: '';
    }
    
    //返回错误信息
    public function getErrorNo() {
        return $this->errNo ?: 11001;
    }
    
    //转换成前台可以展示的错误信息
    public function switchErr($retCode, $retMsg) {
        $this->errNo = $retCode;
        if (empty($retMsg)) {
            $retMsg = \common\custody\ResponeCode::getMsg($retCode);
        }
        return $retMsg;
    }


    public function submitPost($param) {
        $data_string = self::getJson($param);
        $curl = new curl\Curl();
        $options = [CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']];
        $url = 'https://test.credit2go.cn/escrow/p2p/page/mobile';
        $responce = $curl->setOptions($options)->setPostParams($data_string)->post($url);
        return $responce;
    }
    
    /**
     * 验证是是否成功
     * @param type $result
     */
    public function validateSuccess($result) {
        if ((isset($result['retCode']) && $result['retCode'] === self::SUCCESS) || (isset($result['received']) && $result['received'] === self::BATCH_SUCCESS)) {
            $this->isSuccess = true;
        } else {
            $this->isSuccess = false;
        }
    }
    
    //获取是否成功
    public function isSuccess() {
        return $this->isSuccess;
    }

}
