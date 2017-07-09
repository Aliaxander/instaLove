<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Settings
 *
 * @package app\models
 */
class Settings extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'settings';
    }
}
