<?php
namespace osim\craft\focus\models;

use craft\base\Model;
use osim\craft\focus\Plugin;

class Settings extends Model
{
    public ?string $pluginName = 'OSiM Focus';
    public ?string $displayViewport = 'full';
    public ?int $certainty = null;
    public ?int $priority = null;
    public ?bool $wcag = null;
    public ?string $wcagLevel = null;
    public ?bool $bestPractice = null;
    public ?bool $store = null;
    public ?string $userAgent = null;
    public ?int $delay = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName', 'displayViewport', 'userAgent', 'wcagLevel'], 'trim'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];
        $rules[] = [['userAgent'], 'string', 'max' => 250];
        $rules[] = [['wcagLevel'], 'string', 'max' => 3];
        $rules[] = [['certainty', 'priority'], 'number', 'integerOnly' => true, 'min' => 0, 'max' => 100];
        $rules[] = [['delay'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['wcag', 'bestPractice', 'store'], 'boolean'];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'pluginName' => Plugin::t('Custom Plugin Name'),
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
}
