<?php

namespace console\controllers;

use backend\services\ChannelService;
use backend\services\v3\QuoteRuleService;
use common\components\BaiDuMapClient;
use common\components\EliuYunClient;
use common\components\GdMapClient;
use common\components\HllClient;
use common\components\HllPersonalClient;
use common\components\outapi\fastDog\FastDogApi;
use common\components\wechat\WechatOfficial;
use common\services\driver\EmptyCostService;
use common\jobs\MessageJob;
use common\jobs\OrderJob;
use common\jobs\SpikeJob;
use common\models\Bill;
use common\models\Car;
use common\models\DriverBlackList;
use common\models\DriverInfo;
use common\models\DriverInfo1;
use common\models\Functions;
use common\models\GrabDriverSetting;
use common\models\OrderCostAdjustMessage;
use common\models\OrderQuoteRule;
use common\models\PickupOrder;
use common\models\PickupOrderDispatch;
use common\models\PickupOrderSettlement;
use common\models\SmsShortLink;
use common\models\UserLabel;
use common\models\UserLabelRelation;
use common\services\CustomerService;
use common\services\dispatch\DispatchMatchDiver;
use common\services\dispatch\DispatchMatchRule;
use common\services\dispatch\DispatchOperator;
use common\services\dispatch\DispatchTask;
use common\services\DispatchDriverService;
use common\services\DispatchLogService;
use common\services\DispatchService;
use common\services\DriverCarInfoLogService;
use common\services\DriverLocationService;
use common\services\DriverTrackService;
use common\services\dsc\apply\DriverApplyUpdate;
use common\services\dsc\DriverServiceScoreService;
use common\services\dsc\indicators\entity\base\TimelineIntersects;
use common\services\dsc\indicators\entity\DriverScoreUpdate;
use common\services\dsc\indicators\IndicatorsSettingService;
use common\services\EliuyunService;
use common\services\EmailService;
use common\services\exceptions\ServiceCallException;
use common\services\huolala\api\ApiRequest;
use common\services\huolala\notify\NotifyHandler;
use common\services\huolala_personal\entity\cost\ConvertOrderCost;
use common\services\huolala_personal\entity\cost\OrderCostConverter;
use common\services\huolala_personal\HuoLaLaCostService;
use common\services\invoice\InvoiceAmount;
use common\services\invoice\InvoiceAmountCalculate;
use common\services\InvoiceAmountCalculateService;
use common\services\statistics\OperationDailyUpdate;
use common\services\thirdCar\kuaiGou\entity\cost\BPOOrderCostConverter;
use common\services\thirdCar\kuaiGou\KuaiGouCostService;
use common\services\log\externalOrderExec\ExternalOrderExecLogService;
use common\services\MessageService;
use common\services\MiniprogramService;
use common\services\ordermonitor\DriverUpdateDistanceHandler;
use common\services\ordermonitor\OrderMonitorDataProducer;
use common\services\ordermonitor\OrderMonitorService;
use common\services\ordermonitor\OrderStatusHandler;
use common\services\RedisService;
use common\services\settlement\order\cost\OrderAddressSettlementCost;
use common\services\settlement\order\cost\OrderDispatchSettlementCost;
use common\services\settlement\order\cost\OrderSettlementCost;
use common\services\settlement\order\SettlementService;
use common\services\settlement\order\update\DispatchOrderCost;
use common\services\settlement\order\Valuation;
use common\services\statistics\CustomersUpdateService;
use common\services\statistics\OverviewDataService;
use common\services\statistics\OverviewUpdateService;
use common\services\statistics\UserDataUpdateService;
use common\services\thirdCar\kuaiGou\KuaiGouService;
use common\services\thirdCar\monitor\OrderMonitor;
use common\services\UploadService;
use common\services\UserService;
use common\services\v3\dispatch\advanced\entity\Address;
use common\services\v3\dispatch\advanced\entity\Coordinate;
use common\services\v3\dispatch\advanced\entity\Driver;
use common\services\v3\dispatch\advanced\entity\Order;
use common\services\v3\dispatch\advanced\repository\DriverRepository;
use common\services\v3\dispatch\advanced\service\AutoDispatchService;
use common\services\v3\dispatch\auto\AddressChainCalculate;
use common\services\v3\dispatch\auto\AutoDispatch;
use common\services\v3\dispatch\auto\DriverTaskPath;
use common\services\v3\driverPartner\entity\Node;
use common\services\v3\driverPartner\entity\Tree;
use common\services\v3\grabOrder\entity\GrabPushRule;
use common\services\v3\grabOrder\repository\OrderFactory;
use common\services\v3\grabOrder\service\GrabOrderService;
use common\services\v3\monitor\driver\DriverMonitor;
use common\services\v3\monitor\driver\DriverMonitorGenerator;
use common\services\v3\monitor\driver\DriverMonitorPersistence;
use common\services\v3\monitor\fence\DriverFence;
use common\services\v3\monitor\status\DispatchOrderStatusMonitor;
use common\services\v3\monitor\status\DispatchOrderStatusMonitorPersistence;
use common\services\v3\monitor\status\DispatchOrderStatusMonitorReport;
use common\services\v3\order\PickupOrderService;
use common\services\WeixinService;
use common\services\wsmessage\Message;
use console\jobs\FenceJob;
use frontend\models\User;
use frontend\services\v3\order\DispatchOrderService;
use frontend\services\v3\order\OrderOperationService;
use frontend\services\v3\order\OrderRelationService;
use Yii;
use yii\console\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use common\services\MapService;
use common\services\activity\activityTask\ListenOrderService;
use common\services\activity\activityTask\ActivityTianJiangService;
use common\models\Activity;
use common\models\ActivityTask;
use common\models\ActivityTimeRange;
use common\services\activity\activityTask\ActivityGiftPacksService;

class TestController extends Controller
{
    public function actionPreOneSecond()
    {
        \Yii::warning(date('Y-m-d') . 'PreOneSecond', __METHOD__);
        echo '1';
    }

    public function actionPreTenSecond()
    {
        \Yii::warning(date('Y-m-d') . 'PreTenSecond', __METHOD__);
        echo '10';
    }

    public function actionIndex()
    {
        echo Yii::t('app', 'hello1');
    }

    public function actionIndex1()
    {
        echo Yii::t('app', 'Goodbye_flag');
    }
}
