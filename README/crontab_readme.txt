//放款crontab 见详细的文件说明
//还款crontab 见详细的文件说明
//结束债权crontab
执行脚本：php yii "borrow/batchcreditend"
执行频次：建议两小时一次 8.30 到 23:30
//体验金还款
执行脚本: php yii "borrow/exprepay"
执行批次： 每天一次 建议6:20

[建议做成后台功能，去除crontab的形式
//项目登记
执行脚本：php yii "borrow/debtregister"
执行频次：每分钟5次
//登记取消
执行脚本：php yii "borrow/debtregisterch"
执行频次：每分钟5次
]