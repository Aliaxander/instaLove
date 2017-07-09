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
        '2' => 'Running Follow/Unfollow',
        '3' => 'Process Follow/Unfollow'
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
