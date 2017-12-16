<?php
/**
 * Created by PhpStorm.
 * User: Neo
 * Date: 2017/11/12
 * Time: 14:16
 */

namespace common\models\forms;


use yii\base\Model;

class BaseModel extends Model
{
    private $errorCode;

    /**
     * @return mixed
     */
    public function getErrorCode()
    {
        return $this->errorCode?:13000;
    }

    /**
     * @param mixed $errorCode
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
    }


}