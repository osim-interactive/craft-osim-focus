<?php
namespace osim\craft\focus\records;

use craft\db\ActiveRecord;

class Page extends ActiveRecord
{
    const TABLE = '{{%osim_focus_pages}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
