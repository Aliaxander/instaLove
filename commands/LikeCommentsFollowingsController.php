<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Followings;
use app\models\ForLikes;
use app\models\helpers\CheckpointException;
use app\models\Scheduler;
use app\models\Settings;
use app\models\Users;
use InstagramAPI\Instagram;
use yii\console\Controller;

/**
 * Class LikeCommentsFollowingsController
 * php yii like-comments-followings
 *
 * @package app\commands
 */
class LikeCommentsFollowingsController extends Controller
{
    public $id;
    public $user;
    
    public function actionIndex()
    {
        $settingsTmp = Settings::find()->all();
        $settings = [];
        foreach ($settingsTmp as $row) {
            $settings[$row->id] = $row->value;
        }
        $user = Users::find()->where(['task' => 12])->one();
        $this->user = $user;
        if (!empty($user->timeoutMin)) {
            $settings[1] = $user->likeTimeoutMin;
            $settings[2] = $user->likeTimeoutMax;
        }
        if (count($user) === 1) {
            $user->task = 13;
            $user->update();
            $accountId = $user->id;
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
                    $user->update();
                } catch (\Exception $error) {
                    throw new CheckpointException($user, $error->getMessage());
                }
            }
            $followings = Followings::findAll(['userId' => $user->id, ['>', 'followers', 500000]]);
            
            foreach ($followings as $follow) {
                $countMedia = 0;
                $result = $instaApi->timeline->getUserFeed($follow->followId);
                if (!empty($result->getItems())) {
                    $rows = @$result->getItems();
                    try {
                        foreach ($rows as $item) {
                            $countMedia++;
                            if ($countMedia > 1) {
                                $this->getComments($instaApi, $item->pk);
                            }
                            if ($countMedia > 6) {
                                break;
                            }
                        }
                    } catch (\Exception $error) {
                        $calendar = Scheduler::find()->where([
                            'id' => $user->scheduler
                        ])->one();
                        if (count($calendar) === 1) {
                            $calendar->status = 2;
                            $calendar->update();
                        }
                    }
                }
                
                
                $counts = 0;
                $likesData = ForLikes::find()->where([
                    'status' => 0,
                    'userId' => $accountId,
                    'scheduler' => $user->scheduler
                ])->all();
                if (count($likesData) > 0) {
                    foreach ($likesData as $like) {
                        sleep(random_int($settings[1], $settings[2]));
                        $instaApi->media->likeComment($like->mediaId);
                        $like->status = 1;
                        $like->update();
                        if ($counts > 50) {
                            $counts = 0;
                            sleep(300);
                        }
                    }
                } else {
                    echo "\n No tasks. break";
                }
                
                ForLikes::updateAll(['status' => 2], ['status' => 1, 'scheduler' => $user->scheduler]);
                
                $calendar = Scheduler::find()->where([
                    'id' => $user->scheduler
                ])->one();
                if (count($calendar) === 1) {
                    if ($calendar->status !== 2) {
                        $calendar->status = 3;
                        $calendar->update();
                    }
                }
                $user->scheduler = 0;
                $user->task = 1;
                $user->update();
            }
        }
    }
    
    /**
     * @param      $instaApi \InstagramAPI\Instagram
     * @param      $mediaId
     * @param null $page
     */
    public function getComments($instaApi, $mediaId, $page = null)
    {
        $comments = $instaApi->media->getComments($mediaId, $page);
        foreach ($comments->getComments() as $item) {
            $token = $this->user->id . "_" . $item->pk;
            $findMedia = ForLikes::find()->where(['token' => $token])->one();
            if (count($findMedia) === 0) {
                echo "\nadd mediaId:" . $item->pk;
                $forLike = new ForLikes();
                $forLike->userId = $this->user->id;
                $forLike->token = $token;
                $forLike->mediaId = $item->pk;
                $forLike->scheduler = $this->user->scheduler;
                $forLike->save();
            } else {
                echo "\nSkipping mediaId:" . $item->pk;
                echo "\nLast media for likes. Break";
                break;
            }
        }
        if (!empty($comments->getNextMaxId())) {
            $this->getComments($instaApi, $mediaId, $comments->getNextMaxId());
        }
    }
}
