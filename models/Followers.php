<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Followers
 *
 * @package app\models
 */
class Followers extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'followers';
    }
}
