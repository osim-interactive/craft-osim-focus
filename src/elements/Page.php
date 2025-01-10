<?php
namespace osim\craft\focus\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Edit as EditAction;
use craft\elements\actions\Restore as RestoreAction;
use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\validators\UrlValidator;
use osim\craft\focus\Plugin;
use osim\craft\focus\elements\actions\View as ViewAction;
use osim\craft\focus\elements\actions\Test as TestAction;
use osim\craft\focus\elements\db\PageQuery;
use osim\craft\focus\elements\LinkTableAttributeHtmlTrait;
use osim\craft\focus\elements\Issue as IssueElement;
use osim\craft\focus\records\Project as ProjectRecord;
use osim\craft\focus\records\Page as PageRecord;
use yii\base\InvalidConfigException;

class Page extends Element
{
    use LinkTableAttributeHtmlTrait;

    public ?int $projectId = null;
    public ?string $pageTitle = null;
    public ?string $pageUrl = null;
    public ?int $wcagAIssues = null;
    public ?int $wcagAaIssues = null;
    public ?int $wcagAaaIssues = null;
    public ?int $bestPracticeIssues = null;
    public ?int $totalIssues = null;

    protected ?string $linkUrl = null;

    public function init(): void
    {
        parent::init();

        $this->title = $this->pageTitle;
        $this->linkUrl = $this->pageUrl;
        $this->setUiLabel(Plugin::t('Issues'));
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['projectId', 'pageTitle', 'pageUrl'], 'required'];
        $rules[] = [['pageTitle', 'pageUrl'], 'string', 'max' => 250];
        $rules[] = [['pageUrl'], UrlValidator::class, 'defaultScheme' => 'https'];
        $rules[] = [['wcagAIssues', 'wcagAaaIssues', 'wcagAaaIssues', 'bestPracticeIssues', 'totalIssues'], 'number', 'integerOnly' => true, 'min' => 0];

        return $rules;
    }

    public function attributeLabels(): array
    {
        return [
            'projectId' => Plugin::t('Project'),
            'viewportId' => Plugin::t('Viewport'),
            'pageTitle' => Plugin::t('Page Title'),
            'pageUrl' => Plugin::t('Page URL'),
            'wcagAIssues' => Plugin::t('WCAG Level A Issues'),
            'wcagAaIssues' => Plugin::t('WCAG Level AA Issues'),
            'wcagAaaIssues' => Plugin::t('WCAG Level AAA Issues'),
            'bestPracticeIssues' => Plugin::t('Best Practice Issues'),
            'totalIssues' => Plugin::t('Total Issues'),
        ];
    }

    public static function displayName(): string
    {
        return Plugin::t('Page');
    }

    public static function pluralDisplayName(): string
    {
        return Plugin::t('Pages');
    }

    public static function hasTitles(): bool
    {
        return false;
    }

    public static function hasContent(): bool
    {
        return false;
    }

    public static function hasUris(): bool
    {
        return false;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return false;
    }

    public static function find(): PageQuery
    {
        return new PageQuery(static::class);
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can(Plugin::PERMISSION_VIEW_ISSUES);
    }
    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return $user->can(Plugin::PERMISSION_DELETE_PAGES);
    }
    protected static function defineActions(string $source): array
    {
        $actions = [];
        $elementsService = Craft::$app->getElements();

        $actions[] = $elementsService->createAction([
            'type' => ViewAction::class,
            'label' => Craft::t('app', 'View {type}', [
                'type' => static::lowerDisplayName(),
            ]),
        ]);

        $actions[] = $elementsService->createAction([
            'type' => TestAction::class,
        ]);

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_DELETE_PAGES)) {
            $actions[] = $elementsService->createAction([
                'type' => RestoreAction::class,
                'successMessage' => Plugin::t('Pages restored.'),
                'partialSuccessMessage' => Plugin::t('Some pages restored.'),
                'failMessage' => Plugin::t('Pages not restored.'),
            ]);
        }

        return $actions;
    }
    public static function actions(string $source): array
    {
        $actions = parent::actions($source);

        // Remove edit option
        foreach ($actions as $key => $value) {
            if (is_array($value) && $value['type'] === EditAction::class) {
                unset($actions[$key]);
                $actions = array_values($actions);
                break;
            }
        }

        return $actions;
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('osim-focus/pages/' . $this->id . '/issues');
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = PageRecord::findOne($this->id);

            if (!$record) {
                throw new InvalidConfigException('Invalid page ID: ' . $this->id);
            }
        } else {
            $record = new PageRecord();
            $record->id = intval($this->id);
        }

        $record->projectId = $this->projectId;
        $record->pageTitle = $this->pageTitle;
        $record->pageUrl = $this->pageUrl;
        $record->wcagAIssues = $this->wcagAIssues;
        $record->wcagAaIssues = $this->wcagAaIssues;
        $record->wcagAaaIssues = $this->wcagAaaIssues;
        $record->bestPracticeIssues = $this->bestPracticeIssues;
        $record->totalIssues = $this->totalIssues;

        $record->save(false);

        parent::afterSave($isNew);
    }

    public function getSupportedSites(): array
    {
        if ($this->projectId) {
            $siteId = (new Query())
                ->select(['siteId'])
                ->from([ProjectRecord::TABLE])
                ->where(['id' => $this->projectId])
                ->scalar();

            if ($siteId) {
                return [$siteId];
            }
        }

        if ($this->siteId) {
            return [$this->siteId];
        }

        return [Craft::$app->getSites()->getPrimarySite()->id];
    }

    protected static function defineSources(string $context = null): array
    {
        $plugin = Plugin::getInstance();

        $sources = [
            [
                'key' => '*',
                'label' => Plugin::t('All Pages'),
                'criteria' => []
            ],
        ];

        $projects = $plugin->getProjects()->getAllProjects();

        $siteProjects = [];

        foreach ($projects as $project) {
            $siteProjects[$project->siteId][] = [
                'key' => 'project:' . $project->id,
                'label' => $project->getOptionName(),
                'criteria' => [
                    'projectId' => $project->id
                ],
                'data' => [
                    'projectId' => $project->id
                ],
                'sites' => [$project->siteId]
            ];
        }

        foreach ($siteProjects as $projects) {
            // if (count($projects) > 1) {
                $sources = array_merge($sources, $projects);
            // }
        }

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'pageTitle' => Plugin::t('Title'),
            'pageUrl' => Plugin::t('URL'),
            'wcagAIssues' => Plugin::t('A'),
            'wcagAaIssues' => Plugin::t('AA'),
            'wcagAaaIssues' => Plugin::t('AAA'),
            'bestPracticeIssues' => Plugin::t('BP'),
            'totalIssues' => Plugin::t('Issues'),
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'pageTitle',
            'pageUrl',
            'wcagAIssues',
            'wcagAaIssues',
            'wcagAaaIssues',
            'bestPracticeIssues',
            'totalIssues',
        ];
    }

    protected function attributeHtml(string $attribute): string
    {
        if ($attribute === 'pageUrl') {
            $sites = Craft::$app->getSites();

            return $this->linkTableAttributeHtml(
                $this->linkUrl,
                ($sites->getTotalSites() !== 1)
            );
        }

        return parent::attributeHtml($attribute);
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['pageTitle', 'pageUrl'];
    }

    public function afterDelete(): void
    {
        parent::afterDelete();

        if ($this->hardDelete) {
            // Hard delete trashed issues for this page
            while (true) {
                 $elements = IssueElement::find()
                    ->pageId($this->id)
                    ->trashed(true)
                    ->limit(10)
                    ->all();

                 if (!$elements) {
                    break;
                 }

                 foreach ($elements as $element) {
                    Craft::$app->getElements()->deleteElement($element, true);
                 }
            }
        }

        // Delete issues for this page
        while (true) {
             $elements = IssueElement::find()
                ->pageId($this->id)
                ->limit(10)
                ->all();

             if (!$elements) {
                break;
             }

             foreach ($elements as $element) {
                Craft::$app->getElements()->deleteElement($element, $this->hardDelete);
             }
        }
    }

    public function afterRestore(): void
    {
        parent::afterRestore();

        // Restore any deleted issues
        while (true) {
             $elements = IssueElement::find()
                ->pageId($this->id)
                ->trashed(true)
                ->limit(10)
                ->all();

             if (!$elements) {
                break;
             }

             foreach ($elements as $element) {
                Craft::$app->getElements()->restoreElement($element);
             }
        }
    }
}
