<?php
namespace console\controllers;

use console\components\AdvanceSchedule;
use yii\console\Controller;
use yii\di\Instance;

/**
 * 秒级别的计划调度
 * @package console\controlle
 */
class AdvanceScheduleController extends Controller
{
    /**
     * @var AdvanceSchedule
     */
    public $schedule = 'advanceSchedule';

    public function init()
    {
        if (\Yii::$app->has($this->schedule)) {
            $this->schedule = Instance::ensure($this->schedule, AdvanceSchedule::className());
        } else {
            $this->schedule = \Yii::createObject(AdvanceSchedule::className());
        }
        parent::init();
    }

    /**
     * 运行
     */
    public function actionRun()
    {
        try {
            $this->schedule->setDaemon(false);
            $this->schedule->start();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 以守护进程方式运行
     */
    public function actionServe()
    {
        try {
            $this->schedule->setDaemon(true);
            $this->schedule->start();
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * 停止运行(守护进程方式)
     */
    public function actionStop()
    {
        try {
            $this->schedule->stop();
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }
}
