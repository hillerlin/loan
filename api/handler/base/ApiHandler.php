<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 2016/8/3
 */

namespace api\handler\base;
use Yii;
use yii\helpers\StringHelper;


/**
 * API 接口业务分配类
 * 根据 route 分配不同接口到指定业务接口类
 *
 * Class ApiHandler
 * @package ApiBundle\Handler
 */
class ApiHandler
{

    /**
     * 返回API访问结果
     *
     * @return string
     */
    public function getResponse()
    {
        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");

        $api_class = $this->getApiClass();
        if(is_null($api_class)){
            $result['retCode'] = '10000';
            $result['retMsg'] = ApiErrorMsgText::ERROR_10000;
            return StringHelper::jsonUnicode($result);
        }else{
            if($api_class->getResult()){
                $result['retCode'] = $api_class->getErrorCode();
                $result['retMsg'] = $api_class->getErrorMsg();
                $result = array_merge($result,$api_class->getData());
            }else{
                $result['retCode'] = $api_class->getErrorCode();
                $result['retMsg'] = $api_class->getErrorMsg();
                $result = array_merge($result,$api_class->getData());
            }
            return StringHelper::jsonUnicode($api_class->SignData($result));
        }
    }

    /**
     * 返回访问的接口类
     *
     * @return object|null
     */
    private function getApiClass()
    {
        $class_name = 'api\handler';
        $class_name .= '\api';
        $service = Yii::$app->request->post('service');
        $class_name .= '\\'.ucfirst($service);
        return class_exists($class_name)?(new $class_name()):NULL;
    }
}