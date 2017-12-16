<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/8 0008
 * Time: 下午 4:43
 */
abstract  class  base
{
     abstract function sendDate();
}

class API extends base
{
    public function __construct()
    {

    }
    function sendDate()
    {
        // TODO: Implement sendDate() method.
        return array();
    }

}

class adapterBase{

    public $obj;
    public function __construct(API $API)
    {
        $this->obj=$API;

    }
}
class josonAdapt extends adapterBase
{

    public function sendJosonData()
    {
      return  $this->obj->sendData();
    }
}
class xmlAdapt extends adapterBase
{
    public function sendXmlData()
    {
        //1111
        $xml= simplexml_load_string($this->obj->sendDate());
        return $this->obj->sendDate();
    }
}



