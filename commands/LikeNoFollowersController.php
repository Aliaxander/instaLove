<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Followers;
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
 * like-no-followers
 *
 * @package app\commands
 */
class LikeNoFollowersController extends Controller
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
            $result = $instaApi->people->getRecentActivityInbox();
            if (!empty($result->getNewStories())) {
                $stories = $result->getNewStories();
            } else {
                $stories = $result->getOldStories();
            }
            print_r($stories);
            
            echo "\nStart process:";
            if (count($stories) < 10) {
                $count = count($stories);
            } else {
                $count = 10;
            }
            $i = 0;
            foreach ($stories as $row) {
                if ($row->type === 1) {
                    $userId = $row->args->profile_id;
                    
                    $findFollow = Followings::find()->where(['followId' => $userId, 'userId' => $accountId])->one();
                    if (count($findFollow) === 0) {
                        $findFollowers = Followers::find()->where([
                            'followId' => $userId,
                            'userId' => $accountId
                        ])->one();
                        if (count($findFollowers) === 0) {
                            $i++;
                            $countMedia = 0;
                            echo "\nset user" . $userId;
                            try {
                                $photos = $instaApi->timeline->getUserFeed($userId);
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
                                        $forLike->scheduler = $user->scheduler;
                                        $forLike->save();
                                    }
                                    if ($countMedia >= 10) {
                                        break;
                                    }
                                }
                            } catch (\Exception $error) {
                                $calendar = Scheduler::find()->where([
                                    'id' => $user->scheduler
                                ])->one();
                                $calendar->status = 2;
                                $calendar->update();
                            }
                        } else {
                            echo "\nUser followers" . $userId;
                        }
                    } else {
                        echo "\nUser following" . $userId;
                    }
                } else {
                    echo "type !=1";
                }
                
                if ($i > $count) {
                    echo "count >10";
                    break;
                }
            }
            
            
            $likesData = ForLikes::find()->where(['status' => 0, 'scheduler' => $user->scheduler])->all();
            if (count($likesData) > 0) {
                foreach ($likesData as $like) {
                    echo "\n Total likes for day: " . $totalLikes;
                    if ($totalLikes > 0) {
                        sleep(random_int($settings[1], $settings[2]));
                        $media = $instaApi->media->getInfo($like->mediaId);
                        $like->code = @$media->getItems()[0]->code;
                        try {
                            $instaApi->media->like($like->mediaId);
                        } catch (\Exception $e) {
                            print_r($e->getMessage());
                        }
                        $like->status = 1;
                        $like->update();
                        $totalLikes--;
                    } else {
                        break;
                    }
                }
            }
            echo "\nFinal User data:";
            print_r($user);
            
            echo "\nUpdate status task 2:";
            var_dump(ForLikes::updateAll(['status' => 2], ['status' => 1, 'scheduler' => $user->scheduler]));
            
            echo "\nFind calendar:";
            $calendar = Scheduler::findOne(['id' => $user->scheduler]);
            print_r($calendar);
            if (!empty($calendar) && $calendar->status !== 2) {
                $calendar->status = 3;
                $calendar->update();
            }
            $user->task = 1;
            $user->scheduler = 0;
            $user->countLikes = $user->maxLikes - $totalLikes;
            $user->update();
        }
    }
}
