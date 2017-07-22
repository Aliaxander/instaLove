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
 * Class LikeNoFollowersController
 *
 * @package app\commands
 */
class LikeNoFollowersController extends Controller
{
    public $id;
    
    public function actionIndex()
    {
        $settingsTmp = Settings::find()->all();
        $settings = [];
        foreach ($settingsTmp as $row) {
            $settings[$row->id] = $row->value;
        }
        $user = Users::find()->where(['task' => 4])->one();
        $this->user = $user;
        if (!empty($user->timeoutMin)) {
            $settings[1] = $user->timeoutMin;
            $settings[2] = $user->timeoutMax;
        }
        if (count($user) === 1) {
            $user->task = 5;
            $user->update();
            $totalLikes = $user->maxLikes - $user->countLikes;
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
            $result = $instaApi->getRecentActivity();
            if (!empty($result->new_stories)) {//new_stories
                $rows = @$result->new_stories;
                if (count($rows) < 10) {
                    $count = count($rows);
                } else {
                    $count = 10;
                }
                $i = 0;
                foreach ($rows as $row) {
                    if ($row->type === 1) {
                        $i++;
                        $userId = $row->args->profile_id;
                        
                        $findFollow = Followings::find()->where(['followId' => $userId, 'userId' => $accountId])->one();
                        if (count($findFollow) === 0) {
                            $findFollowers = Followings::find()->where([
                                'followId' => $userId,
                                'userId' => $accountId
                            ])->one();
                            if (count($findFollowers) === 0) {
                                $countMedia = 0;
                                echo "\nset user" . $userId;
                                try {
                                    $photos = $instaApi->getUserFeed($userId);
                                    foreach ($photos->items as $item) {
                                        //print_r($item);
                                        $token = $accountId . "_" . $item->pk;
                                        $findMedia = ForLikes::find()->where(['token' => $token])->one();
                                        if (count($findMedia) === 0) {
                                            $countMedia++;
                                            $forLike = new ForLikes();
                                            $forLike->userId = $accountId;
                                            $forLike->token = $token;
                                            $forLike->mediaId = $item->pk;
                                            $forLike->save();
                                        }
                                        if ($countMedia >= 10) {
                                            break;
                                        }
                                    }
                                } catch (\Exception $error) {
                                    $calendar = Scheduler::find()->where([
                                        'user' => $user->id,
                                        'task' => 4,
                                        'status' => 1
                                    ])->orderBy(['date' => 'desc'])->one();
                                    $calendar->status = 2;
                                    $calendar->update();
                                }
                            }
                        }
                    }
                    
                    if ($i > $count) {
                        break;
                    }
                }
            }
    
    
            $likesData = ForLikes::find()->where(['status' => 0, 'userId' => $accountId])->all();
            if (count($likesData) > 0) {
                foreach ($likesData as $like) {
                    if ($totalLikes <= 0) {
                        sleep(random_int($settings[1], $settings[2]));
                        $instaApi->like($like->mediaId);
                        $like->status = 1;
                        $totalLikes--;
                    } else {
                        break;
                    }
                }
            }
    
            ForLikes::updateAll(['status' => 2], ['status' => 1, 'userId' => $accountId]);
    
            $user->task = 1;
            $user->countLikes = $user->maxLikes - $totalLikes;
            $user->update();
        }
    }
}
