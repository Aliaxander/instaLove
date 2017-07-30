<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Scheduler;
use app\models\Users;
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
                print_r($task);
                $model = Users::findOne($task->user);
                $model->task = $task->task;
                if ($model->day != date('Y-m-d')) {
                    $model->countLikes = 0;
                }
                $model->day = date('Y-m-d');
                $model->scheduler = $task->id;
                $model->update();
    
                $task->status = 1;
                $task->update();
            }
        }
    }
}
