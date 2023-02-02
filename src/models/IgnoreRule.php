<?php
namespace osim\craft\focus\models;

use craft\base\Model;
use osim\craft\focus\Plugin;
use osim\craft\focus\validators\ComparatorValidator;

class IgnoreRule extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?int $accountId = null;
    public ?int $projectId = null;
    public ?int $viewportId = null;
    public ?string $pageUrlComparator = null;
    public ?string $pageUrlValue = null;
    public ?int $ruleId = null;
    public ?string $xpathComparator = null;
    public ?string $xpathValue = null;
    public ?string $selectorComparator = null;
    public ?string $selectorValue = null;
    public ?string $uid = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name'], 'required'];
        $rules[] = [['name', 'pageUrlValue', 'xpathValue', 'selectorValue'], 'string', 'max' => 250];
        $rules[] = [['ruleId'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['pageUrlComparator', 'xpathComparator', 'selectorComparator'], ComparatorValidator::class];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'name' => Plugin::t('Name'),
            'accountId' => Plugin::t('Account'),
            'projectId' => Plugin::t('Project'),
            'viewportId' => Plugin::t('Viewport'),
            'pageUrlComparator' => Plugin::t('Page URL Comparator'),
            'pageUrlValue' => Plugin::t('Page URL Value'),
            'ruleId' => Plugin::t('Rule ID'),
            'xpathComparator' => Plugin::t('XPath Comparator'),
            'xpathValue' => Plugin::t('XPath Value'),
            'selectorComparator' => Plugin::t('Selector Comparator'),
            'selectorValue' => Plugin::t('Selector Value'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'accountId' => $this->accountId,
            'projectId' => $this->projectId,
            'viewportId' => $this->viewportId,
            'pageUrlComparator' => $this->pageUrlComparator,
            'pageUrlValue' => $this->pageUrlValue,
            'ruleId' => $this->ruleId,
            'xpathComparator' => $this->xpathComparator,
            'xpathValue' => $this->xpathValue,
            'selectorComparator' => $this->xpathComparator,
            'selectorValue' => $this->xpathValue,
        ];
    }
}
