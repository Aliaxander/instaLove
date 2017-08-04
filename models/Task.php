<?php

namespace app\models;

/**
 * Class Status
 *
 * @package app\models
 */
class Task extends \yii\base\Object
{
    
    private static $status = [
        '1' => 'No task',
        '2' => '...Follow/Unfollow',
        '3' => 'Process Follow/Unfollow',
        '4' => '...ML no follow',
        '5' => 'Process ML no follow',
        '6' => '...ML last photo followers',
        '7' => 'Process ML last photo followers',
        '8' => '...ML last photo account followers',
        '9' => 'Process ML last photo account followers',
        '10' => '...Follow old followings',
        '11' => 'Process Follow old followings',
        '12' => '...Like comments',
        '13' => 'Process Like comments'
    ];
    
    
    /**
     * @param int|string $id
     *
     * @return null|static
     */
    public static function findIdentity($id)
    {
        return isset(self::$status[$id]) ? self::$status[$id] : null;
    }
    
    /**
     * @return array
     */
    public static function getAll()
    {
        return self::$status;
    }
}
