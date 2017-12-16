<?php
namespace common\lib\digitalSealBase;

/**
 * Created by PhpStorm.
 * User: Daisy
 * Date: 2017/11/12
 * Time: 20:24
 */
class DigitalSealBase
{
    /**
     * 接口链接
     *
     * @var string
     */
    protected $url = 'http://120.24.68.184:9017/elecsign/';

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

    /**
     * @param string $errorNo
     * @return DigitalSealBase
     */
    public function setErrorNo($errorNo)
    {
        $this->errorNo = $errorNo;
        return ($this);
    }

    /**
     * @param string $errorMsg
     */
    public function setErrorMsg($errorMsg)
    {
        $this->errorMsg = $errorMsg;
    }


}