<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Followings
 *
 * @package app\models
 */
class Followings extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'followings';
    }
}
