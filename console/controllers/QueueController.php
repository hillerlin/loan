<?php
namespace console\controllers;
 
use common\models\LoanBorrower;
use common\models\LoanOrder;
use common\models\LoanPacket;
use Yii;
use yii\console\Controller;
use common\lib\Queue;



class QueueController extends Controller
{

    //商户回调 每三十分钟执行一次
    public function actionMerchantNotify(){
        set_time_limit(0);
        $queue = 'merchantNotify';
        for($i = 0; $i <= Queue::len($queue); $i++) {
            $params = Queue::pop($queue);
            if (empty($params)) {
                exit();
            }
            $data = json_decode($params, true);
            if($data['bid'] && $data['lendPayTime']){
                $res = $this->lendPay($data['bid'], $data['lendPayTime']);
            }else{
                $res = $data;
            }
            if ($res['url'] && $res['data']) {
                Yii::info('notify data: ' . json_encode($res), 'merNotify');
                if ($this->notify($res['url'], $res['data'])) {
                    echo 'success:' . $params;
                }else{
                    Yii::error('merchant-notify:requestFail,params-' . $params, 'merNotify' );
                    Queue::push($queue, $res);
                }
            }else{
                Yii::error('merchant-notify:paramsError-' . $params, 'merNotify' );
                continue;
            }
        }
    }

    private function notify($url, $data)
    {
        $result = \common\lib\Helps::http( $url, $data, 'POST', 'str' );
        return $result == 'success' ? true : false;
    }

    private function lendPay($bid, $lendPayTime)
    {
        $res = LoanOrder::setRepayingStatusFromBid($bid);
        if ($res) {
            $merchantId = LoanBorrower::findIdentity($res['borrowerId'])->merchant_id;
            $url = Yii::$app->params['notify_url'][$merchantId];
            if ($url) {
                $data = [
                    'service' => 'lendPayCall',
                    'merchantId' => $merchantId,
                    'retCode' => 0,
                    'retMsg' => '',
                    'orderId' => $res['orderId'],
                    'lendPayDate' => date('Ymd', $lendPayTime),
                ];
                $loanPacketModel = new LoanPacket();
                return $loanPacketModel->formatCallData($url, $data);
            }
        }
        return false;
    }

    public function actionGetRedis()
    {
        $queue = 'merchantNotify';
        $queue = 'queue:' . $queue;
        var_dump(Yii::$app->redis->lrange($queue, 0, 10));
    }

    public function actionSetRedis()
    {
        $queueData = [
            'bid' => '4693',
            'lendPayTime' => 1512039018,
        ];
        Queue::push('merchantNotify', $queueData);
        $queueData = [
            'bid' => '4694',
            'lendPayTime' => 1512046202,
        ];
        Queue::push('merchantNotify', $queueData);
        $queueData = [
            'bid' => '4695',
            'lendPayTime' => 1512089402,
        ];
        Queue::push('merchantNotify', $queueData);
    }
}