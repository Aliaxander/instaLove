<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\CheckTable;
use app\models\Followings;
use app\models\helpers\CheckpointException;
use app\models\Scheduler;
use app\models\Settings;
use app\models\Users;
use InstagramAPI\Instagram;
use yii\console\Controller;

/**
 * Class ParseController
 *
 * @package app\commands
 */
class ParseController extends Controller
{
    public $id;
    
    public function actionIndex()
    {
        $tasks = CheckTable::find()->where("status=0")->all();
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                $task->status = 1;
                $task->update();
                $this->id = $task->user;
                $user = Users::findOne($this->id);
                
                $instaApi = new Instagram(false, false, [
                    'storage' => 'mysql',
                    'dbhost' => 'localhost',
                    'dbname' => 'insta',
                    'dbusername' => 'insta',
                    'dbpassword' => 'mPF4F6n6lAyor3KZ',
                ]);
                if (!empty($user->proxy)) {
                    $instaApi->setProxy($user->proxy);
                }
                $instaApi->setUser($user->userName, $user->password);
                if (!$instaApi->isLoggedIn) {
                    try {
                        $instaApi->login(true);
                    } catch (\Exception $error) {
                        new CheckpointException($user, $error->getMessage());
                    }
                }
                $this->parse($instaApi);
            }
        }
    }
    
    /**
     * @param $instaApi
     * @param $page
     */
    protected function parse($instaApi, $page = null)
    {
        $result = $instaApi->getSelfUsersFollowing($page);
        
        //print_r($result->users);
        foreach ($result->users as $user) {
            $model = new Followings();
            $model->token = $this->id . '_' . $user->pk;
            $model->userId = $this->id;
            $model->followId = $user->pk;
            $model->profile_pic_url = $user->profile_pic_url;
            $model->username = $user->username;
            $model->full_name = $user->full_name;
            try {
                $model->save();
            } catch (\Exception $e) {
                $model->isNewRecord = false;
                $model->save();
            }
        }
        if (!empty($result->next_max_id)) {
            $this->parse($instaApi, $result->next_max_id);
        }
    }
}
