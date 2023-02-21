<?php
namespace osim\craft\focus\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;

use osim\craft\focus\Plugin;

class Account extends Model
{
    public ?int $id = null;
    public ?string $name = 'Main';
    public ?string $osimFocusApiKey= null;
    public ?int $certainty = null;
    public ?int $priority = null;
    public ?bool $wcag = null;
    public ?string $wcagLevel = null;
    public ?bool $bestPractice = null;
    public ?bool $store = null;
    public ?string $userAgent = null;
    public ?int $delay = null;
    public ?string $uid = null;

    public function getOptionName(): string
    {
        return $this->name;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'osimFocusApiKey'], 'required'];
        $rules[] = [['name', 'osimFocusApiKey', 'userAgent', 'wcagLevel'], 'trim'];
        $rules[] = [['name', 'osimFocusApiKey', 'userAgent'], 'string', 'max' => 250];
        $rules[] = [['wcagLevel'], 'string', 'max' => 3];
        $rules[] = [['certainty', 'priority'], 'number', 'integerOnly' => true, 'min' => 0, 'max' => 100];
        $rules[] = [['delay'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['wcag', 'bestPractice', 'store'], 'boolean'];

        return $rules;
    }

    public function attributeLabels(): array
    {
        $plugin = Plugin::getInstance();

        return [
            'name' => Plugin::t('Name'),
            'osimFocusApiKey' => Plugin::t('OSiM Focus API Key'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'wcag' => Plugin::t('WCAG'),
            'wcagLevel' => Plugin::t('WCAG Level'),
            'bestPractice' => Plugin::t('Best Practice'),
            'store' => Plugin::t('Store Results'),
            'userAgent' => Plugin::t('User-Agent String'),
            'delay' => Plugin::t('Delay'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'osimFocusApiKey' => $this->osimFocusApiKey,
            'certainty' => $this->certainty,
            'priority' => $this->priority,
            'wcag' => $this->wcag,
            'wcagLevel' => $this->wcagLevel,
            'bestPractice' => $this->bestPractice,
            'store' => $this->store,
            'userAgent' => $this->userAgent,
            'delay' => $this->delay,
        ];
    }
}
