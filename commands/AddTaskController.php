<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Followings;
use app\models\helpers\CheckpointException;
use app\models\Scheduler;
use app\models\Settings;
use app\models\Users;
use InstagramAPI\Instagram;
use yii\console\Controller;

/**
 * Class StartController
 *
 * @package app\commands
 */
class AddTaskController extends Controller
{
    public function actionIndex()
    {
        $tasks = Scheduler::find()->where("date<=now() and status=0")->all();
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                $task->status = 1;
                $task->update();
                
                print_r($task);
                $model = Users::findOne($task->user);
                $model->task = $task->task;
                $model->update();
            }
        }
    }
}
