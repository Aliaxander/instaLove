<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Users
 *
 * @package app\models
 */
class Scheduler extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'scheduler';
    }
}
