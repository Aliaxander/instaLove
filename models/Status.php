<?php

namespace app\models;

/**
 * Class Status
 *
 * @package app\models
 */
class Status extends \yii\base\Object
{
    
    private static $status = [
        '1' => 'Good',
        '2' => 'Login failed',
        '3' => 'Proxy failed',
        '4' => 'checkpoint',
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
