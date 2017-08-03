<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\CheckTable;
use app\models\Followers;
use app\models\Followings;
use app\models\helpers\CheckpointException;
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
                
                $instaApi = new Instagram(true, true, [
                    'storage' => 'mysql',
                    'dbhost' => '103.250.22.104',
                    'dbname' => 'insta',
                    'dbusername' => 'insta',
                    'dbpassword' => 'mPF4F6n6lAyor3KZ',
                ]);
                if (!empty($user->proxy)) {
                    $instaApi->setProxy($user->proxy);
                }
                $instaApi->setUser($user->userName, $user->password);
                echo "isLogin = ";
                var_dump($instaApi->isLoggedIn);
                if (!$instaApi->isLoggedIn) {
                    try {
                        $instaApi->login(true);
                        $user->status = 1;
                        $user->scheduler = 0;
                        $user->update();
                    } catch (\Exception $error) {
                        throw new CheckpointException($user, $error->getMessage());
                    }
                }
                try {
                    $this->parseFollowings($instaApi);
                    $this->parseFollowers($instaApi);
                } catch (\Exception $error) {
                    if ($error->getMessage() === 'InstagramAPI\Response\FollowerAndFollowingResponse: login_required.') {
                        try {
                            $instaApi->login(true);
                            $user->status = 1;
                            $user->scheduler = 0;
                            $user->update();
                            $this->parseFollowings($instaApi);
                            $this->parseFollowers($instaApi);
                        } catch (\Exception $error) {
                            throw new CheckpointException($user, $error->getMessage());
                        }
                    }
                }
            }
        }
    }
    
    /**
     * @param $instaApi \InstagramAPI\Instagram
     * @param $page
     */
    protected function parseFollowings($instaApi, $page = null)
    {
        $result = $instaApi->people->getSelfFollowing(null, $page);
    
        print_r($result->users);
        foreach ($result->users as $user) {
            $followings = Followings::findOne(['token' => $this->id . '_' . $user->pk]);
            if (count($followings) === 1) {
                $model = $followings;
                $model->profile_pic_url = $user->profile_pic_url;
                $model->username = $user->username;
                $model->full_name = $user->full_name;
                $followCheck = $instaApi->people->getInfoById($user->pk);
                $model->followers = $followCheck->user->follower_count;
                $model->update();
            } else {
                $model = new Followings();
                $model->token = $this->id . '_' . $user->pk;
                $model->userId = $this->id;
                $model->followId = $user->pk;
                $model->profile_pic_url = $user->profile_pic_url;
                $model->username = $user->username;
                $model->full_name = $user->full_name;
                $followCheck = $instaApi->people->getInfoById($user->pk);
                $model->followers = $followCheck->user->follower_count;
                $model->save();
            }
        }
        if (!empty($result->next_max_id)) {
            $this->parseFollowings($instaApi, $result->next_max_id);
        }
    }
    
    /**
     * @param $instaApi \InstagramAPI\Instagram
     * @param $page
     */
    protected function parseFollowers($instaApi, $page = null)
    {
        $result = $instaApi->people->getSelfFollowers(null, $page);
        
        print_r($result);
        foreach ($result->users as $user) {
            $followers = Followers::findOne(['token' => $this->id . '_' . $user->pk]);
            if (count($followers) === 1) {
                $model = $followers;
                $model->profile_pic_url = $user->profile_pic_url;
                $model->username = $user->username;
                $model->full_name = $user->full_name;
                $followCheck = $instaApi->people->getInfoById($user->pk);
                $model->followers = $followCheck->user->follower_count;
                $model->update();
            } else {
                $model = new Followers();
                $model->token = $this->id . '_' . $user->pk;
                $model->userId = $this->id;
                $model->followId = $user->pk;
                $model->profile_pic_url = $user->profile_pic_url;
                $model->username = $user->username;
                $model->full_name = $user->full_name;
                $followCheck = $instaApi->people->getInfoById($user->pk);
                $model->followers = $followCheck->user->follower_count;
                $model->save();
            }
        }
        if (!empty($result->next_max_id)) {
            $this->parseFollowers($instaApi, $result->next_max_id);
        }
    }
}
