<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Followings;
use app\models\helpers\CheckpointException;
use app\models\Settings;
use app\models\Users;
use InstagramAPI\Instagram;
use yii\console\Controller;

/**
 * Class StartController
 *
 * @package app\commands
 */
class StartController extends Controller
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
        $user = Users::find()->where(['task' => 2])->one();
        $this->user = $user;
        if (count($user) === 1) {
            $user->task = 3;
            $user->update();
            
            
            //InstagramLogic:
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
                    $user->status = 1;
                    $user->update();
                } catch (\Exception $error) {
                    throw new CheckpointException($user, $error->getMessage());
                }
            }
            
            $followings = Followings::find()->where(['userId' => $user->id, 'status' => 1, 'isComplete' => 0])->all();
            foreach ($followings as $row) {
                $follow = Followings::find()->where(['id' => $row->id])->one();
                $follow->isComplete = 1;
                $follow->save();
                $this->followUnfollow($instaApi, $row->followId, 0);
                sleep(random_int($settings[1], $settings[2]));
                $this->followUnfollow($instaApi, $row->followId, 1);
                sleep(random_int($settings[1], $settings[2]));
            }
            $user->task = 1;
            $user->update();
            Followings::updateAll(['isComplete' => 0], ['userId' => $user->id]);
        }
    }
    
    /**
     * @param $instaApi \InstagramAPI\Instagram
     * @param $accountId
     * @param $isFollow
     */
    protected function followUnfollow($instaApi, $accountId, $isFollow)
    {
        try {
            if ($isFollow) {
                $instaApi->follow($accountId);
            } else {
                $instaApi->unfollow($accountId);
            }
        } catch (\Exception $error) {
            echo $error->getMessage();
            print_r($error);
            if ($error->getMessage() === 'InstagramAPI\Response\FollowerAndFollowingResponse: login_required.') {
                try {
                    $instaApi->login(true);
                } catch (\Exception $error) {
                    throw new CheckpointException($this->user, $error->getMessage());
                }
            }
            
            $this->countError++;
            if ($this->countError <= 5) {
                sleep(60);
                $this->followUnfollow($instaApi, $accountId, $isFollow);
            } else {
                $message = $error->getMessage();
                \Yii::$app->mailer->compose()
                    ->setFrom('insta@allsoft.com')
                    ->setTo('megroup@iinet.net.au')
                    ->setSubject('Insta ERROR')
                    ->setTextBody('Connection error | ' . $this->user->userName . ' | ' . $message)
                    ->send();
                
                
                throw new CheckpointException($this->user, $message);
            }
        }
    }
}
