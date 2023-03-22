<?php
namespace osim\craft\focus\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Edit as EditAction;
use craft\elements\actions\Restore as RestoreAction;
use craft\elements\actions\SetStatus as CraftSetStatusAction;
use craft\elements\User;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use osim\craft\focus\Plugin;
use osim\craft\focus\elements\actions\SetStatus as SetStatusAction;
use osim\craft\focus\elements\actions\View as ViewAction;
use osim\craft\focus\elements\db\IssueQuery;
use osim\craft\focus\models\Project as ProjectModel;
use osim\craft\focus\records\Issue as IssueRecord;
use osim\craft\focus\records\Page as PageRecord;
use osim\craft\focus\records\Project as ProjectRecord;
use yii\base\InvalidConfigException;

class Issue extends Element
{
    const STATUS_RESOLVED = 'resolved';
    const STATUS_UNRESOLVED = 'unresolved';

    public ?int $projectId = null;

    public ?int $pageId = null;
    public ?string $pageTitle = null;
    public ?string $pageUrl = null;

    public ?int $viewportId = null;
    public ?string $viewportName = null;
    public ?int $viewportWidth = null;
    public ?int $viewportHeight = null;

    public ?int $certainty = null;
    public ?int $priority = null;
    public ?int $ruleId = null;
    public ?string $ruleName = null;
    public ?string $ruleDescription = null;
    public ?string $snippet = null;
    public ?string $xpath = null;
    public ?string $selector = null;
    public ?bool $wcag = null;
    public ?string $wcagLevel = null;
    public ?bool $bestPractice = null;
    public ?string $summary = null;
    public ?bool $resolved = null;

    public function init(): void
    {
        parent::init();

        $this->title = $this->pageTitle;
        $this->uri = $this->pageUrl;
        if ($this->uri) {
            $this->uri = UrlHelper::urlWithParams($this->uri, [
                'osim-focus-xpath' => $this->xpath
            ]);
        }
        $this->setUiLabel(Plugin::t('Details'));
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'pageId', 'viewportId', 'certainty', 'priority',
                'ruleId', 'ruleName',
                'ruleDescription', 'snippet', 'xpath', 'selector',
            ],
            'required'
        ];
        $rules[] = [['certainty', 'priority'], 'number', 'integerOnly' => true, 'min' => 0, 'max' => 100];
        $rules[] = [['ruleId'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['ruleName'], 'string', 'max' => 250];
        $rules[] = [['wcag', 'bestPractice', 'resolved'], 'boolean'];
        $rules[] = [['wcagLevel'], 'string', 'max' => 3];

        return $rules;
    }

    public function attributeLabels(): array
    {
        return [
            'pageId' => Plugin::t('Page'),
            'viewportId' => Plugin::t('Viewport'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'ruleId' => Plugin::t('Rule ID'),
            'ruleName' => Plugin::t('Rule Name'),
            'ruleDescription' => Plugin::t('Rule Description'),
            'snippet' => Plugin::t('Snippet'),
            'xpath' => Plugin::t('XPath'),
            'selector' => Plugin::t('Selector'),
            'wcag' => Plugin::t('WCAG'),
            'wcagLevel' => Plugin::t('WCAG Level'),
            'bestPractice' => Plugin::t('Best Practice'),
            'summary' => Plugin::t('Summary'),
            'resolved' => Plugin::t('Resolved'),
        ];
    }

    public static function displayName(): string
    {
        return 'Issue';
    }

    public static function pluralDisplayName(): string
    {
        return 'Issues';
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
        return true;
    }

    public static function statuses(): array
    {
        return [
            'resolved' => ['label' => Plugin::t('Resolved'), 'color' => 'green'],
            'unresolved' => ['label' => Plugin::t('Unresolved'), 'color' => 'red'],
        ];
    }

    public static function find(): IssueQuery
    {
        return new IssueQuery(static::class);
    }

    public function canView(User $user): bool
    {
        return true;
    }
    public function canSave(User $user): bool
    {
        return false;
    }

    public function getFieldLayout(): ?\craft\models\FieldLayout
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return null;
        }

        // Only show preview on desktop
        $preview = '';
        if (!Craft::$app->getRequest()->isMobileBrowser()) {
            $preview = '<div class="osim-focus-preview">' . "\n" .
                '<div class="osim-focus-frame" style="width: ' . $this->viewportWidth . 'px; height: ' . $this->viewportHeight . 'px;">' . "\n" .
                    '<iframe src="' . Html::encode($this->uri) . '"/>' . "\n" .
                '</div>' . "\n" .
            '</div>' . "\n";
        }

        $layoutElements = [
            new \craft\fieldlayoutelements\Html(
                $this->makeFieldLayoutHtml() .
                $preview
            )
        ];

        $fieldLayout = new \craft\models\FieldLayout();

        $tab = new \craft\models\FieldLayoutTab();
        $tab->name = 'Content';
        $tab->setLayout($fieldLayout);
        $tab->setElements($layoutElements);

        $fieldLayout->setTabs([ $tab ]);

        return $fieldLayout;
    }

    private function makeFieldLayoutHtml(): string
    {
        if ($this->wcag) {
            $standard = $this->wcagLevel;
        } else if ($this->bestPractice) {
            $standard = 'BP';
        } else {
            $standard = null;
        }

        $html = '<h2>' . Plugin::t('Issue') . '</h2>' . "\n" .
            '<p><b>' . Plugin::t('Rule') . ': (' . $this->ruleId . ')</b><br>' .
            Html::encode($this->ruleName) . '</p>' . "\n";

        if ($standard) {
            $html .= '<p><b>' . Plugin::t('Standard') . ': </b>' . $standard . '</p>' . "\n";
        }

        $html .= '<p><b>' . Plugin::t('Certainty') . ': </b>' . $this->certainty . '</p>' . "\n" .
            '<p><b>' . Plugin::t('Priority') . ': </b>' . $this->priority . '</p>' . "\n" .
            '<p>' . "\n" .
                '<a class="go" href="' . Html::encode($this->uri) . '" rel="noopener" target="_blank">' . "\n" .
                    '<span dir="ltr">View Issue</span>' . "\n" .
                '</a>' . "\n" .
            '</p>' . "\n" .

            '<h2>' . Plugin::t('Description') . '</h2>' . "\n" .
            '<p>' . Html::encode($this->ruleDescription) . '</p>' . "\n";

        if ($this->summary) {
            $html .= '<h2>' . Plugin::t('What To Fix') . '</h2>' . "\n" .
                '<p>' . Html::encode($this->summary) . '</p>' . "\n";
        }

        $html .= '<h2>' . Plugin::t('Snippet') . '</h2>' . "\n" .
            '<p>' . Html::encode($this->snippet) . '</p>' . "\n";

        return $html;
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

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_DELETE_ISSUES)) {
            $actions[] = $elementsService->createAction([
                'type' => RestoreAction::class,
                'successMessage' => Craft::t('app', 'Entries restored.'),
                'partialSuccessMessage' => Craft::t('app', 'Some entries restored.'),
                'failMessage' => Craft::t('app', 'Entries not restored.'),
            ]);
        }

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_RESOLVE_ISSUES)) {
            $actions[] = $elementsService->createAction([
                'type' => SetStatusAction::class,
            ]);
        }

        return $actions;
    }
    public static function actions(string $source): array
    {
        $actions = parent::actions($source);

        // Remove edit and default set status option
        foreach ($actions as $key => $value) {
            if (is_array($value) && $value['type'] === EditAction::class) {
                unset($actions[$key]);
            } elseif ($value === CraftSetStatusAction::class) {
                unset($actions[$key]);
            }
        }

        $actions = array_values($actions);

        return $actions;
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('osim-focus/issues/view/' . $this->id);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = IssueRecord::findOne($this->id);

            if (!$record) {
                throw new InvalidConfigException('Invalid issue ID: ' . $this->id);
            }
        } else {
            $record = new IssueRecord();
            $record->id = intval($this->id);
        }

        $record->pageId = $this->pageId;
        $record->viewportId = $this->viewportId;
        $record->certainty = $this->certainty;
        $record->priority = $this->priority;
        $record->ruleId = $this->ruleId;
        $record->ruleName = $this->ruleName;
        $record->ruleDescription = $this->ruleDescription;
        $record->snippet = $this->snippet;
        $record->xpath = $this->xpath;
        $record->selector = $this->selector;
        $record->wcag = $this->wcag;
        $record->wcagLevel = $this->wcagLevel;
        $record->bestPractice = $this->bestPractice;
        $record->summary = $this->summary;
        $record->resolved = $this->resolved;

        $record->save(false);

        parent::afterSave($isNew);
    }

    public function getSupportedSites(): array
    {
        if ($this->pageId) {
            $projectId = (new Query())
                ->select(['projectId'])
                ->from([PageRecord::TABLE])
                ->where(['id' => $this->pageId])
                ->scalar();

            if ($projectId) {
                $siteId = (new Query())
                    ->select(['siteId'])
                    ->from([ProjectRecord::TABLE])
                    ->where(['id' => $projectId])
                    ->scalar();

                if ($siteId) {
                    return [$siteId];
                }
            }
        }

        if ($this->siteId) {
            return [$this->siteId];
        }

        return [Craft::$app->getSites()->getPrimarySite()->id];
    }

    public function getStatus(): ?string
    {
        if ($this->resolved) {
            return self::STATUS_RESOLVED;
        }

        return self::STATUS_UNRESOLVED;
    }

    protected static function defineSources(string $context = null): array
    {
        $plugin = Plugin::getInstance();

        $pageId = static::getPageIdFromRequest();

        if ($pageId) {
            $projectId = (new Query())
                ->select(['projectId'])
                ->from([PageRecord::TABLE])
                ->where(['id' => $pageId])
                ->scalar();

            $project = $plugin->getProjects()->getProjectById($projectId);

            $sources = [
                [
                    'key' => 'page:' . $pageId,
                    'label' => Plugin::t('Page Issues'),
                    'criteria' => [
                        'pageId' => $pageId,
                        'projectId' => $projectId
                    ],
                    'data' => [
                        'pageId' => $pageId,
                        'projectId' => $projectId
                    ],
                    'nested' => static::defineViewportSources($project, $pageId)
                ],
            ];

            return $sources;
        }

        $sources = [
            [
                'key' => '*',
                'label' => Plugin::t('All Issues'),
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
                'sites' => [$project->siteId],
                'nested' => static::defineViewportSources($project)
            ];
        }

        foreach ($siteProjects as $projects) {
            // if (count($projects) > 1) {
                $sources = array_merge($sources, $projects);
            // }
        }
        return $sources;
    }

    protected static function getPageIdFromRequest(): ?int
    {
        // Get-elements action request
        $source = Craft::$app->getRequest()->getParam('source');
        if ($source && substr($source, 0, 5) === 'page:') {
            $source = explode('page:', $source, 2);
            return intval($source[1]);
        }

        // Initial cp page request
        $pageUrl = Craft::$app->getRequest()->getParam('p');
        if (strpos($pageUrl, '/pages/') !== false) {
            $pageUrl = explode('/pages/', $pageUrl, 2);
            return intval($pageUrl[1]);
        }

        return null;
    }

    protected static function defineViewportSources(ProjectModel $project, int $pageId = null): array
    {
        $plugin = Plugin::getInstance();

        $viewports = $plugin->getViewports()->getViewportsByProjectId($project->id);

        $sources = [];

        foreach ($viewports as $viewport) {
            if ($pageId) {
                $key = 'page:' . $pageId . ':' . $viewport->id;
            } else {
                $key = 'project:' . $project->id . ':' . $viewport->id;
            }
            $sources[] = [
                'key' => $key,
                'label' => $viewport->getOptionName(),
                'criteria' => [
                    'pageId' => $pageId,
                    'projectId' => $project->id,
                    'viewportId' => $viewport->id
                ],
                'data' => [
                    'pageId' => $pageId,
                    'projectId' => $project->id,
                    'viewportId' => $viewport->id
                ],
            ];
        }

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        $settings = Plugin::getInstance()->getSettings();

        $attributes = [
            'pageTitle' => Plugin::t('Page Title'),
            'pageUrl' => [
                'label' => Plugin::t('View'),
                'icon' => 'world',
            ],
            'viewport' => Plugin::t('Viewport'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'ruleId' => Plugin::t('Test'),
            'standard' => Plugin::t('Standard'),
        ];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'ruleId',
            'standard',
            'pageTitle',
            'viewport',
            'pageUrl',
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'pageTitle' => Plugin::t('Page Title'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'ruleId' => Plugin::t('Test'),
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        $settings = Plugin::getInstance()->getSettings();

        if ($attribute === 'pageUrl') {
            return parent::tableAttributeHtml('link');
        }

        if ($attribute === 'viewport') {
            if ($settings->displayViewport === 'name') {
                return $this->viewportName;
            } elseif ($settings->displayViewport === 'size') {
                return $this->viewportWidth . ' × ' . $this->viewportHeight;
            } else {
                return $this->viewportName . ' [' . $this->viewportWidth . ' × ' . $this->viewportHeight . ']';
            }
        }

        if ($attribute === 'viewportSize') {
            return $this->viewportWidth . ' × ' . $this->viewportHeight;
        }

        if ($attribute === 'ruleId') {
            return $this->ruleId . ': ' . $this->ruleName;
        }

        if ($attribute === 'standard') {
            if ($this->wcag) {
                return $this->wcagLevel;
            }

            if ($this->bestPractice) {
                return 'BP';
            }

            return '';
        }

        return parent::tableAttributeHtml($attribute);
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            // 'pageTitle',
            // 'pageUrl',
            'ruleName',
        ];
    }
}
