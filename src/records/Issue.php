<?php
namespace osim\craft\focus\records;

use craft\db\ActiveRecord;

class Issue extends ActiveRecord
{
    const TABLE = '{{%osim_focus_issues}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
