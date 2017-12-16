<?php
/**
 * Created by PhpStorm.
 * User: lmj
 * Date: 2017/4/18
 * Time: 10:49
 */
namespace common\lib\Design\EntrustPattern;
use Yii;
class RenderData{
	private $OBJ;
	private $ACTION;
	public function __construct($objName,$action=null)
	{
		$this->OBJ=new $objName;
		$this->ACTION=$action;
	}

	public function entrustRenderData($params='')
	{
	    $list=[];
	    if(!is_array($this->ACTION))
        {
            return call_user_func_array(array($this->OBJ,$this->ACTION),array(Yii::$app->request,$params));
        }else
        {
            foreach ($this->ACTION as $key=>$value)
            {
                $_params='';
                if(isset($params[$key]))
                {
                    $_params=$params[$key];
                }
                $list[$key]=call_user_func_array(array($this->OBJ,$value),array(Yii::$app->request,$_params));
            }
            return $list;
        }

	}

	public function singleRenderData($functionName,$params='')
    {
        return call_user_func_array(array($this->OBJ,$functionName),array(Yii::$app->request,$params));
    }

}