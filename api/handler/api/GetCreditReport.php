<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\lib\jf\JfApi;
use common\models\forms\RegisterForm;
use common\models\LoanBorrower;
use common\models\LoanCreditReport;
use common\models\User;
use Yii;
use yii\base\Exception;

/**
 * getCreditReport
 * 2.2.1 个人注册
 */
class GetCreditReport extends ApiBase
{
    public function getResult()
    {
        return $this->get();
    }

    private function get()
    {
        $rules = [
            ['mobile',true, 'mobile'],
            ['idNo', true, 'id_no'],
            ['realName', true],
        ];

        if($this->verify($rules)){
            //获取征信
            $creditReport = $this->getDmCredit();
            if ($creditReport === false) {
                return false;
            }
            $data = [
                'mobile' => $this->getParameters('mobile'),
                'real_name' => $this->getParameters('realName'),
                'id_no' => $this->getParameters('idNo'),
                'report' => $creditReport
            ];
            $this->setData($data);
            return true;
        }
        return false;
    }

    private function getDmCredit()
    {
        if (YII_DEBUG) {
            $this->setErrorCode(18003)->setErrorMsg(ApiErrorMsgText::ERROR_18003);
            return false;
        }
        $param = [
            'merchantId' => $this->getPost('merchantId'),
            'mobile' => $this->getParameters('mobile'),
            'real_name' => $this->getParameters('realName'),
            'id_no' => $this->getParameters('idNo'),
        ];
        $report = LoanCreditReport::findByBorrowerInfo($param);
        if (!$report) {
            $this->setErrorCode(18003)->setErrorMsg(ApiErrorMsgText::ERROR_18003);
            return false;
        }else{
            return $report->report;
        }
    }

}