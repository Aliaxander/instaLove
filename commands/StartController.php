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
    public function actionIndex()
    {
        $settingsTmp = Settings::find()->all();
        $settings = [];
        foreach ($settingsTmp as $row) {
            $settings[$row->id] = $row->value;
        }
        $user = Users::find()->where(['task' => 2])->one();
        if (count($user) === 1) {
            $user->task = 3;
            $user->update();
            
            
            //InstagramLogic:
            $instaApi = new Instagram(false, false, [
                'storage' => 'mysql',
                'dbhost' => '164.132.168.121',
                'dbname' => 'instaFollow',
                'dbusername' => 'instaFollow',
                'dbpassword' => 'instaFollow',
            ]);
            // $instaApi->setProxy($user->proxy);
            $instaApi->setUser($user->userName, $user->password);
            if (!$instaApi->isLoggedIn) {
                try {
                    $instaApi->login(true);
                } catch (\Exception $error) {
                    new CheckpointException($user, $error->getMessage());
                }
            }
            
            $followings = Followings::find()->where(['userId' => $user->id, 'status' => 1, 'isComplete' => 0])->all();
            foreach ($followings as $row) {
                $follow = Followings::find()->where(['id' => $row->id])->one();
                $follow->isComplete = 1;
                $follow->save();
                $instaApi->unfollow($row->followId);
                sleep(random_int($settings[1], $settings[2]));
                $instaApi->follow($row->followId);
                sleep(random_int($settings[1], $settings[2]));
            }
            $user->task = 1;
            $user->update();
            Followings::updateAll(['isComplete' => 0], ['userId' => $user->id]);
        }
    }
}
