<?php
namespace osim\craft\focus\models;

use craft\base\Model;
use osim\craft\focus\Plugin;

class ProjectViewport extends Model
{
    public ?int $id = null;
    public ?int $projectId = null;
    public ?int $viewportId = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['projectId', 'viewportId'], 'required'];

        return $rules;
    }

    public function attributeLabels(): array
    {
        $plugin = Plugin::getInstance();

        return [
            'projectId' => Plugin::t('Project'),
            'viewportId' => Plugin::t('Viewport'),
        ];
    }
}
