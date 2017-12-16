<?php

namespace api\handler\api;

use api\handler\base\ApiBase;
use api\handler\base\ApiErrorMsgText;
use common\lib\digitalSealBase\LookUpContractApi;
use common\models\LoanOrder;

/**
 * contract
 * 2.5.1订单合同链接
 */
class Contract extends ApiBase
{
    public function getResult()
    {
        return $this->get();
    }

    private function get()
    {
        $rules = [
            ['orderId', true,  'number'],
        ];

        if($this->verify($rules)){
            if (!($orderInfo = LoanOrder::checkOrderInMerchant($this->getParameters('orderId'), $this->getPost('merchantId')))) {
                $this->setErrorCode(15101)->setErrorMsg(ApiErrorMsgText::ERROR_15101);
                return false;
            };
            $contractApi = new LookUpContractApi();
            $contractNo = \common\models\Borrow::selectOne(['bid' => $orderInfo->borrow_id], 'contract_no');
            if (!($contractUrl = $contractApi->setContractNo($contractNo['contract_no'])->getContractUrl())) {
                $this->setErrorCode($contractApi->getErrorNo())->setErrorMsg($contractApi->getErrorMsg());
                return false;
            }
            $this->setData([
                'orderId' => $this->getParameters('orderId'),
                'contractUrl' => $contractUrl
            ]);
            return true;
        }
        return false;
    }

}