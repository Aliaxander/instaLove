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
 * Class FollowOldController
 *
 * @package app\commands
 */
class FollowOldController extends Controller
{
    protected $countError = 0;
    protected $user;
    
    public function actionIndex()
    {
        $settingsTmp = Settings::find()->all();
        $settings = [];
        foreach ($settingsTmp as $row) {
            $settings[$row->id] = $row->value;
        }
        
        $user = Users::find()->where(['task' => 10])->one();
        $this->user = $user;
        if (!empty($user->timeoutMin)) {
            $settings[1] = $user->timeoutMin;
            $settings[2] = $user->timeoutMax;
        }
        if (count($user) === 1) {
            $user->task = 11;
            $user->update();
            
            
            //InstagramLogic:
            $instaApi = new Instagram(true, false, [
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
            if (!$instaApi->isLoggedIn) {
                try {
                    $instaApi->login(true);
                    $user->status = 1;
                    $user->update();
                } catch (\Exception $error) {
                    throw new CheckpointException($user, $error->getMessage());
                }
            } else {
                $user->status = 1;
                $user->update();
            }
            $followings = Followings::find()->where(['userId' => $user->id, 'status' => 1, 'isComplete' => 0])->all();
            foreach ($followings as $row) {
                $row->isComplete = 1;
                $row->update();
                echo "\nFollow {$row->followId}";
                $this->followUnfollow($instaApi, $row->followId);
                sleep(random_int($settings[1], $settings[2]));
            }
            
            Followings::updateAll(['isComplete' => 0], ['userId' => $user->id]);
            $calendar = Scheduler::find()->where([
                'user' => $user->id,
                'task' => 2,
                'status' => 1
            ])->orderBy(['date' => 'desc'])->one();
            if (count($calendar) === 1) {
                if ($calendar->status !== 2) {
                    $calendar->status = 3;
                    $calendar->update();
                }
            }
            $user->task = 1;
            $user->update();
        }
    }
    
    /**
     * @param $instaApi \InstagramAPI\Instagram
     * @param $accountId
     */
    protected function followUnfollow($instaApi, $accountId)
    {
        try {
            $instaApi->follow($accountId);
        } catch (\Exception $error) {
            echo $error->getMessage();
            if ($error->getMessage() === 'InstagramAPI\Response\FollowerAndFollowingResponse: login_required.') {
                try {
                    $instaApi->login(true);
                } catch (\Exception $error) {
                    throw new CheckpointException($this->user, $error->getMessage());
                }
            }
            
            $this->countError++;
            if ($this->countError <= 5) {
                echo "\nSleep for error";
                sleep(60);
                $this->followUnfollow($instaApi, $accountId);
                $instaApi->follow($accountId);
            } else {
                $instaApi->follow($accountId);
                $message = $error->getMessage();
                \Yii::$app->mailer->compose()
                    ->setFrom('insta@allsoft.com')
                    ->setTo('megroup@iinet.net.au')
                    ->setSubject('Insta ERROR')
                    ->setTextBody('Connection error | ' . $this->user->userName . ' | ' . $message)
                    ->send();
                
                $calendar = Scheduler::find()->where([
                    'user' => $this->user->id,
                    'task' => 2,
                    'status' => 1
                ])->orderBy(['date' => 'desc'])->one();
                $calendar->status = 2;
                $calendar->update();
                
                throw new CheckpointException($this->user, $message);
            }
        }
    }
}
