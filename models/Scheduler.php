<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Scheduler
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
