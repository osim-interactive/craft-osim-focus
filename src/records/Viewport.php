<?php
namespace osim\craft\focus\records;

use craft\db\ActiveRecord;

class Viewport extends ActiveRecord
{
    const TABLE = '{{%osim_focus_viewports}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
