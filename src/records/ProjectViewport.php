<?php
namespace osim\craft\focus\records;

use craft\db\ActiveRecord;

class ProjectViewport extends ActiveRecord
{
    const TABLE = '{{%osim_focus_projects_viewports}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
