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
    
    public function CopyNewFile($oldId, $newId)
    {
        $count = 12;//количество картинок копировать
        for ($i = 0; $i <= $count; $i++) {
            foreach (['', '_left', '_right', '_mobile', '_left_mobile', '_right_mobile'] as $key) {
                $orig = 'http://newsletter.ru.oriflame.com/img/template16/' . $oldId . '_' . $i . $key . '.jpg';
                $patchTo = 'upload/template16/' . $newId . '_' . $i . $key . '.jpg';
                if (file_exists($orig)) {
                    copy($orig, $patchTo);
                }
            }
        }
    }
}
