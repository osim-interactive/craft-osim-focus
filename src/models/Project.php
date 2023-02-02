<?php
namespace osim\craft\focus\models;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use craft\validators\UrlValidator;
use osim\craft\focus\Plugin;
use osim\craft\focus\records\ProjectViewport as ProjectViewportRecord;

class Project extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?int $siteId = null;
    public ?int $accountId = null;
    public ?string $osimFocusProjectId = null;
    public ?string $sitemapUrl = null;
    public ?int $certainty = null;
    public ?int $priority = null;
    public ?bool $wcag = null;
    public ?string $wcagLevel = null;
    public ?bool $bestPractice = null;
    public ?bool $store = null;
    public ?string $userAgent = null;
    public ?int $delay = null;
    private ?array $viewports = null;
    public ?string $uid = null;

    public function getViewports(): array
    {
        if ($this->viewports !== null) {
            return $this->viewports;
        }

        if (!$this->id) {
            return [];
        }

        $plugin = Plugin::getInstance();
        $this->setViewports($plugin->getProjects()->getProjectViewports($this->id));

        return $this->viewports;
    }
    public function setViewports(array $viewports)
    {
        $this->viewports = $viewports;
    }

    public function getViewportIds(): array
    {
        $viewportIds = [];

        foreach ($this->getViewports() as $viewport) {
            $viewportIds[] = $viewport['viewportId'];
        }

        return $viewportIds;
    }

    public function getOptionName(): string
    {
        return $this->name;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'siteId', 'sitemapUrl', 'accountId'], 'required'];
        $rules[] = [['name', 'sitemapUrl', 'osimFocusProjectId', 'userAgent', 'wcagLevel'], 'trim'];
        $rules[] = [['name', 'sitemapUrl', 'osimFocusProjectId', 'userAgent'], 'string', 'max' => 250];
        $rules[] = [['sitemapUrl'], UrlValidator::class, 'defaultScheme' => 'https'];
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
            'name' => Plugin::t('Name'),
            'siteId' => Plugin::t('Site'),
            'accountId' => Plugin::t('Account'),
            'osimFocusProjectId' => Plugin::t('OSiM Focus Project ID'),
            'sitemapUrl' => Plugin::t('Sitemap URL'),
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
        $viewports = [];

        if ($this->viewports) {
            foreach ($this->viewports as $model) {
                if (!$model->uid) {
                    if ($model->id) {
                        $model->uid = Db::uidById(ProjectViewportRecord::TABLE, $model->id);
                    } else {
                        $model->uid = StringHelper::UUID();
                    }

                }
                $viewports[$model->uid] = $model->getConfig();
            }
        }

        return [
            'name' => $this->name,
            'siteId' => $this->siteId,
            'accountId' => $this->accountId,
            'osimFocusProjectId' => $this->osimFocusProjectId,
            'sitemapUrl' => $this->sitemapUrl,
            'certainty' => $this->certainty,
            'priority' => $this->priority,
            'wcag' => $this->wcag,
            'wcagLevel' => $this->wcagLevel,
            'bestPractice' => $this->bestPractice,
            'store' => $this->store,
            'userAgent' => $this->userAgent,
            'viewports' => $viewports,
            'delay' => $this->delay,
        ];
    }
}
