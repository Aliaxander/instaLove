<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Scheduler;
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
        $tasks = Scheduler::find()->where("date<=now() and (dateTo>=now() or dateTo='0000-00-00 00:00:00') and (status=0 or status=3)")->all();//
        print_r($tasks);
//        die;
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                //print_r($task);
                if ($task->task == 2) {
                    if ($task->count == 0) {
                        $start = true;
                    } else {
                        $d1 = strtotime($task->dateTo);
                        $d2 = strtotime($task->date);
                        $diff = $d2 - $d1;
                        $diff = $diff / 15;
                        $hours = floor($diff);
                        echo "Hours:" . $hours;
                        $result = round($hours / 15);
                        if ($task->count > $result) {
                            $start = true;
                        } else {
                            $d1 = strtotime(date("Y-m-d H:i:s"));
                            $d2 = strtotime($task->dateUpdate);
                            $diff = $d1 - $d2;
                            $diff = $diff / 15;
                            $hours = floor($diff);
                            if ($hours >= 15) {
                                $start = true;
                            } else {
                                $start = false;
                            }
                            echo "\nlastUpdate:" . $hours;
                            echo "\nNow:" . date("Y-m-d H:i:s");
                        }
                    }
                } else {
                    $start = true;
                }
                
                
                if ($start === true) {
                    $model = Users::findOne($task->user);
                    $model->task = $task->task;
                    if ($model->day != date('Y-m-d')) {
                        $model->countLikes = 0;
                    }
                    $model->day = date('Y-m-d');
                    $model->scheduler = $task->id;
                    $model->update();
                    
                    $task->status = 1;
                    $task->count += 1;
                    $task->update();
                }
            }
        }
    }
}
