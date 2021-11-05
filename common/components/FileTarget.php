<?php
namespace common\components;

use yii\helpers\VarDumper;

class FileTarget extends \yii\log\FileTarget
{
    /**
     * 增加 traceid
     * @param array $message
     * @return string
     */
    public function getMessagePrefix($message)
    {
        $parentMessgePrefix = parent::getMessagePrefix($message);
        $traceId = $this->getTraceId();
        $router = '';
        if (isset(\Yii::$app->controller)) {
            $router = \Yii::$app->controller->getRoute();
        }
        return $parentMessgePrefix . "[$traceId][$router]";
    }

    /**
     * 上下文信息新增post json提交时候可以查看
     * @return string
     */
    protected function getContextMessage()
    {
        $parentContextMessage = parent::getContextMessage();
        if ( isset(\Yii::$app->request->isPost) && \Yii::$app->request->isPost) {
            $parentContextMessage .= "\n\npost = " . VarDumper::dumpAsString(\Yii::$app->request->post());
        }
        return $parentContextMessage;
    }

    public function getTraceId()
    {
        static $traceId;
        if (empty($traceId)) {
            $traceId = \Yii::$app->security->generateRandomString();
        }
        return $traceId;
    }
}
