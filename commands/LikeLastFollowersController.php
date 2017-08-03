<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\ForLikes;
use app\models\helpers\CheckpointException;
use app\models\Scheduler;
use app\models\Settings;
use app\models\Users;
use InstagramAPI\Instagram;
use yii\console\Controller;

/**
 * Class LikeLastFollowersController
 *
 * @package app\commands
 */
class LikeLastFollowersController extends Controller
{
    public $id;
    
    public function actionIndex()
    {
        $settingsTmp = Settings::find()->all();
        $settings = [];
        foreach ($settingsTmp as $row) {
            $settings[$row->id] = $row->value;
        }
        $user = Users::find()->where(['task' => 6])->one();
        if (!empty($user->timeoutMin)) {
            $settings[1] = $user->timeoutMin;
            $settings[2] = $user->timeoutMax;
        }
        if (count($user) === 1) {
            $user->task = 7;
            $user->update();
            $totalLikes = $user->maxLikes - $user->countLikes;
            echo "Set max likes:" . $totalLikes;
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
            $result = $instaApi->people->getSelfFollowers();
            if (!empty($result->users)) {
                $rows = @$result->users;
                
                foreach ($rows as $row) {
                    if (!$row->is_private) {
                        $userId = $row->pk;
                        $countMedia = 0;
                        echo "\nset user" . $userId;
                        try {
                            $photos = $instaApi->timeline->getUserFeed($userId);
                            foreach ($photos->items as $item) {
                                $countMedia++;
                                // print_r($item);
                                $token = $accountId . "_" . $item->pk;
                                $findMedia = ForLikes::find()->where(['token' => $token])->one();
                                if (count($findMedia) === 0) {
                                    echo "\nadd mediaId:" . $item->pk;
                                    $forLike = new ForLikes();
                                    $forLike->userId = $accountId;
                                    $forLike->token = $token;
                                    $forLike->mediaId = $item->pk;
                                    $forLike->scheduler=$user->scheduler;
                                    $forLike->save();
                                } else {
                                    echo "\nSkipping mediaId:" . $item->pk;
                                    echo "\nLast media for likes. Break";
                                    break;
                                }
                                if ($countMedia >= 5) {
                                    echo "\nMax media (5) for user. Break";
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
                }
            }
            
            
            $likesData = ForLikes::find()->where(['status' => 0, 'userId' => $accountId])->all();
            if (count($likesData) > 0) {
                foreach ($likesData as $like) {
                    if ($totalLikes >= 0) {
                        sleep(random_int($settings[1], $settings[2]));
                        $media = $instaApi->media->getInfo($like->mediaId);
                        $like->code = @$media->getItems()[0]->code;
                        $instaApi->media->like($like->mediaId);
                        $like->status = 1;
                        $like->update();
                        $totalLikes--;
                    } else {
                        echo "\n Max likes for day :( break.";
                        break;
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
            $user->task = 1;
            $user->scheduler = 0;
            $user->countLikes = $user->maxLikes - $totalLikes;
            $user->update();
        }
    }
}
