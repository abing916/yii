<?php
namespace console\components;

use yii\base\Component;
use Swoole\Process;
use Swoole\Timer;

/**
 * 秒级调度
 * @package console\components
 */
class AdvanceSchedule extends Component
{
    public $runUser = 'root'; //运行账户
    public $cliScriptName = 'yii';
    public $daemon = false;
    public $pidfile = '';
    public $taskConfigFile = '';

    private $_runningTasks = [];
    private $tasks; //任务列表

    /**
     * 退出状态
     * @var bool
     */
    private $_flgExit = false;

    public function init()
    {
        //注册子进程回收信号处理
        Process::signal(SIGCHLD, [$this, 'doSignal']);
        Process::signal(SIGTERM, [$this, 'doSignal']);
        if (empty($this->pidfile)) {
            $this->pidfile = \Yii::getAlias("@runtime/advanceSchedule.pid");
            $this->taskConfigFile = \Yii::getAlias("@console/config/advance-schedule.php");
        }
    }

    public function start()
    {
        $this->_log("start cron server...");
        if ($this->daemon) {
            //需要check是否已存在了
            if (is_file($this->pidfile)) {
                throw new \Exception('已在运行了...');
            }
            Process::daemon(true, false);
            file_put_contents($this->pidfile, posix_getpid());
        }
        $this->loadTasks();

        Timer::set([
            'enable_coroutine' => false,
        ]);
        Timer::tick(1000, [$this, 'doTask']);
        //10s 加载一次配置
        Timer::tick(10000, function () {
            $this->loadTasks();
        });
    }

    /**
     * 加载任务
     */
    public function loadTasks()
    {
        $this->tasks = [];
        if (is_file($this->taskConfigFile)) {
            $this->tasks = require $this->taskConfigFile;
        }
    }

    /**
     * 停止运行 守护进程方式
     */
    public function stop()
    {
        if (!is_file($this->pidfile)) {
            throw new \Exception('该进程没有运行...');
        }
        $pid = file_get_contents($this->pidfile);
        Process::kill($pid);
    }

    /**
     * 设置Daemon
     * @param $bool
     */
    public function setDaemon($bool)
    {
        $this->daemon = $bool;
    }

    /**
     * 定时器每秒回调函数
     * @param mixed $params
     */
    public function doTask($params = null)
    {
        //开始任务
        $currentTime = time();
        $cronVer = 0;

        if (isset($this->tasks) && !empty($this->tasks)) {
            foreach ($this->tasks as $jobId => $job) {
                if (!isset($job['title']) || !isset($job['cron']) || !isset($job['command']) || !isset($job['id'])) {
                    $this->_log("crontab job config error");
                    continue;
                }

                if ($this->_isTimeByCron($currentTime, $job['cron'])) {
                    if (isset($this->_runningTasks[$job['id']])) {
                        $this->_log("last cron worker not exit. job id={$job['id']}");
                        continue;
                    }

                    //启动任务
                    $cronWorker =  new Process(function (Process $worker) use($job) {
                        $this->doCronTask($worker, $job);
                    });

                    $pid = $cronWorker->start();
                    if ($pid === false) {
                        $this->_log("start cron worker failure.");
                        continue;
                    }
                    $this->_runningTasks[$job['id']] = $pid;
                    $cronWorker->write(json_encode($job));
                }
            }
        }
    }

    /**
     * do cron worker
     */
    public function doCronTask($worker, $job)
    {

//        //设置用户组
//        $userInfo = posix_getpwnam($this->runUser);
//        if (empty($userName)) {
//            $this->_log("start crontab failure, get userinfo failure. user={$this->runUser}");
//            return;
//        }
//        posix_setuid($userInfo['uid']);
//        posix_setgid($userInfo['gid']);
        //clear log
        chdir(dirname(\Yii::$app->request->getScriptFile()));
        \Yii::getLogger()->flush();
        $this->_log("cron worker running task={$job['title']}, jobId={$job['id']}");
        set_time_limit(0);
        $worker->exec(PHP_BINARY, [$this->cliScriptName, $job['command']]);
    }

    /**
     * 根据定时任务时间配置，检测当前时间是否在指定时间内
     * @param int $time     - 当前时间
     * @param string $cron  - 定时任务配置
     * @return bool 不在指定时间内返回false, 否则返回true
     */
    private function _isTimeByCron($time, $cron)
    {
        $cronParts = explode(' ', $cron);
        if (count($cronParts) != 6) {
            return false;
        }

        list($sec, $min, $hour, $day, $mon, $week) = $cronParts;

        $checks = array('sec' => 's', 'min' => 'i', 'hour' => 'G', 'day' => 'j', 'mon' => 'n', 'week' => 'w');

        $ranges = array(
            'sec' => '0-59',
            'min' => '0-59',
            'hour' => '0-23',
            'day' => '1-31',
            'mon' => '1-12',
            'week' => '0-6',
        );

        foreach ($checks as $part => $c) {
            $val = $$part;
            $values = array();

            /*
                For patters like 0-23/2
            */
            if (strpos($val, '/') !== false) {
                //Get the range and step
                list($range, $steps) = explode('/', $val);

                //Now get the start and stop
                if ($range == '*') {
                    $range = $ranges[$part];
                }
                list($start, $stop) = explode('-', $range);

                for ($i = $start; $i <= $stop; $i = $i + $steps) {
                    $values[] = $i;
                }
            } /*
                For patters like :
                2
                2,5,8
                2-23
            */
            else {
                $k = explode(',', $val);

                foreach ($k as $v) {
                    if (strpos($v, '-') !== false) {
                        list($start, $stop) = explode('-', $v);

                        for ($i = $start; $i <= $stop; $i++) {
                            $values[] = $i;
                        }
                    } else {
                        $values[] = $v;
                    }
                }
            }

            if (!in_array(date($c, $time), $values) and (strval($val) != '*')) {
                return false;
            }
        }

        return true;
    }

    /**
     * 处理进程信号
     * @param int $sig  - 信号类型
     */
    public function doSignal($sig) {
        $pidToJobId = array_flip($this->_runningTasks);
        switch ($sig) {
            case SIGCHLD:
                //必须为false，非阻塞模式
                while($ret =  Process::wait(false)) {
//                    echo "recycle child process PID={$ret['pid']}\n";
                    $exitPid = $ret['pid'];
                    if (isset($pidToJobId[$exitPid])) {
                        $jobId = $pidToJobId[$exitPid];
                        unset($this->_runningTasks[$jobId]);
                    }
                }
                //当子进程都退出后，结束masker进程
                if (empty($this->_runningTasks) && $this->_flgExit) {
                    @unlink($this->pidfile);
                    exit(0);
                }

                break;
            case SIGTERM:
                $this->_log("recv terminate signal, exit crond.");
                $this->_flgExit = true;
                break;
        }
    }

    /**
     * 输出日志
     * @param $msg
     */
    private function _log($msg)
    {
        $dateStr = date("Y-m-d H:i:s");
        echo "[{$dateStr}] {$msg}\n";
    }
}
