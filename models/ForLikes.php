<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Followings
 *
 * @package app\models
 */
class ForLikes extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'forLikes';
    }
}
