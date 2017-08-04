<?php
/**
 * Created by PhpStorm.
 * User: aliaxander
 * Date: 07.06.17
 * Time: 13:24
 */

namespace app\models\helpers;

/**
 * Class CheckpointException
 *
 * @package app\modules\helpers
 */
class CheckpointException extends \RuntimeException
{
    /**
     * CheckpointException constructor.
     *
     * @param string $user
     * @param int    $error
     */
    public function __construct($user, $error)
    {
        if (preg_match("/Network: cURL error /", $error)) {
            if (in_array($user->task, [11, 9, 7, 5, 3])) {
                $task = $user->task - 1;
            } else {
                $task = $user->task;
            }
            \Yii::$app->db->createCommand("UPDATE users set status=3, task={$task} where id=" . $user->id . "")->query();
            die($error);
        } elseif (preg_match("/checkpoint_required./", $error)) {
            \Yii::$app->db->createCommand("UPDATE users set status=4, task=1 where id=" . $user->id . "")->query();
            die($error);
        } else {
            switch ($error) {
                case ('The username you entered doesn\'t appear to belong to an account. Please check your username and try again.'):
                    \Yii::$app->db->createCommand("UPDATE users set status=2, task=1 where id=" . $user->id . "")->query();
                    die($error);
                    break;
                case ('Throttled by Instagram because of too many API requests.'):
                    sleep(mt_rand(300, 500));
                    break;
                case ('Please wait a few minutes before you try again.'):
                    sleep(mt_rand(300, 500));
                    break;
                case ('InstagramAPI\Response\LoginResponse: Your account has been disabled for violating our terms. Learn how you may be able to restore your account.'):
                    \Yii::$app->db->createCommand("UPDATE users set status=2, task=1 where id=" . $user->id . "")->query();
                    die($error);
                    break;
                case ('Your account has been disabled for violating our terms. Learn how you may be able to restore your account.'):
                    \Yii::$app->db->createCommand("UPDATE users set status=2, task=1 where id=" . $user->id . "")->query();
                    die($error);
                    break;
                
                case ('InstagramAPI\Response\LoginResponse: The password you entered is incorrect. Please try again.'):
                    \Yii::$app->db->createCommand("UPDATE users set status=2, task=1 where id=" . $user->id . "")->query();
                    die("The password you entered is incorrect. Please try again.");
                    break;
            }
        }
    }
}
