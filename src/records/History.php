<?php
namespace osim\craft\focus\records;

use craft\db\ActiveRecord;

class History extends ActiveRecord
{
    const TABLE = '{{%osim_focus_history}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
