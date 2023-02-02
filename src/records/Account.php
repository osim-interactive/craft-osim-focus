<?php
namespace osim\craft\focus\records;

use craft\db\ActiveRecord;

class Account extends ActiveRecord
{
    const TABLE = '{{%osim_focus_accounts}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
