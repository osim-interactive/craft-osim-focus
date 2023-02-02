<?php
namespace osim\craft\focus\records;

use craft\db\ActiveRecord;

class Project extends ActiveRecord
{
    const TABLE = '{{%osim_focus_projects}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
