<?php
namespace osim\craft\focus\records;

use craft\db\ActiveRecord;

class IgnoreRule extends ActiveRecord
{
    const TABLE = '{{%osim_focus_ignore_rules}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
