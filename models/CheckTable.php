<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class CheckTable
 *
 * @package app\models
 */
class CheckTable extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'checkTable';
    }
}
