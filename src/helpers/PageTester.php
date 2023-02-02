<?php
namespace osim\craft\focus\helpers;

use Craft;
use craft\db\Query;
use craft\helpers\StringHelper;
use osim\craft\focus\Plugin;
use osim\craft\focus\helpers\OsimFocusTestApi;
use osim\craft\focus\elements\Issue as IssueElement;
use osim\craft\focus\elements\Page as PageElement;
use osim\craft\focus\models\Account as AccountModel;
use osim\craft\focus\models\Project as ProjectModel;
use osim\craft\focus\models\OsimFocusProject as OsimFocusProjectModel;
use osim\craft\focus\models\Viewport as ViewportModel;
use osim\craft\focus\records\IgnoreRule as IgnoreRuleRecord;

class PageTester
{
    private ProjectModel $project;
    private AccountModel $account;
    private OsimFocusProjectModel $osimFocusProjectModel;
    private OsimFocusTestApi $osimFocusTestApi;

    private ?array $ignoreRules = null;
    private array $updatedViewports = [];

    public function __construct(int $projectId)
    {
        $plugin = Plugin::getInstance();
        $this->project = $plugin->getProjects()->getProjectById($projectId);
        $this->account = $plugin->getAccounts()->getAccountById($this->project->accountId);
        $this->osimFocusProjectModel = $this->getOsimFocusProjectModel(
            $this->project,
            $this->account
        );

        $this->osimFocusTestApi = new OsimFocusTestApi($this->account->osimFocusApiKey);
    }

    public function testPageUrl($pageUrl, ViewportModel $viewportModel): int
    {
        $plugin = Plugin::getInstance();

        if ($this->isIgnorablePage($pageUrl, $viewportModel->id)) {
            return 422;
        }

        // If the viewport is the default account viewport
        if (!$viewportModel->accountId) {
            $this->osimFocusProjectModel->viewportWidth = $viewportModel->width;
            $this->osimFocusProjectModel->viewportHeight = $viewportModel->height;
        }

        $result = $this->osimFocusTestApi->testUrl($pageUrl, $this->osimFocusProjectModel);

        $status = $result['status'] ?? 500;

        if ($status !== 200) {
            return $status;
        }

        $pageId = $this->savePage($result['title'], $result['url']);

        if ($pageId === null) {
            return 500;
        }

        $width = $result['viewport']['width'];
        $height = $result['viewport']['height'];

        $this->updateDefaultViewport($viewportModel, $width, $height);

        $this->saveIssues(
            $this->project->siteId,
            $pageId,
            $viewportModel->id,
            $result['issues']
        );

        $plugin->getPages()->updateIssueCount($pageId);

        return $status;
    }

    private function getOsimFocusProjectModel(ProjectModel $project, AccountModel $account): OsimFocusProjectModel
    {
        $settings = Plugin::getInstance()->getSettings();

        $model = new OsimFocusProjectModel();

        $model->uid = $project->osimFocusProjectId;
        $model->certainty = $project->certainty ?? $account->certainty ?? $settings->certainty;
        $model->priority = $project->priority ?? $account->priority ?? $settings->priority;
        $model->wcag = $project->wcag ?? $account->wcag ?? $settings->wcag;
        $model->wcagLevel = $project->wcagLevel ?? $account->wcagLevel ?? $settings->wcagLevel;
        $model->bestPractice = $project->bestPractice ?? $account->bestPractice ?? $settings->bestPractice;
        $model->store = $project->store ?? $account->store ?? $settings->store;
        $model->userAgent = $project->uString ?? $account->uString ?? $settings->userAgent;
        $model->delay = $project->delay ?? $account->delay ?? $settings->delay;

        return $model;
    }

    private function isIgnorablePage(string $pageUrl, int $viewportId): bool
    {
        foreach ($this->getIgnoreRules() as $rule) {
            if ($rule['accountId'] !== null && $rule['accountId'] !== $this->account->id) {
                continue;
            }

            if ($rule['projectId'] !== null && $rule['projectId'] !== $this->project->id) {
                continue;
            }

            if ($rule['viewportId'] !== null && $rule['viewportId'] !== $viewportId) {
                continue;
            }

            if ($rule['pageUrlValue'] !== null &&
                !ComparatorHelper::matchAgainst(
                    $rule['pageUrlComparator'],
                    $rule['pageUrlValue'],
                    $pageUrl
                )
            ) {
                continue;
            }

            return true;
        }

        return false;
    }
    private function isIgnorableIssue(array $issue, int $viewportId): bool
    {
        foreach ($this->getIgnoreRules() as $rule) {
            if ($rule['accountId'] !== null && $rule['accountId'] !== $this->account->id) {
                continue;
            }

            if ($rule['projectId'] !== null && $rule['projectId'] !== $this->project->id) {
                continue;
            }

            if ($rule['viewportId'] !== null && $rule['viewportId'] !== $viewportId) {
                continue;
            }

            if ($rule['ruleId'] !== null && $rule['ruleId'] !== $issue['rule_id']) {
                continue;
            }

            if ($rule['xpathValue'] !== null &&
                !ComparatorHelper::matchAgainst(
                    $rule['xpathComparator'],
                    $rule['xpathValue'],
                    $issue['xpath']
                )
            ) {
                continue;
            }

            if ($rule['selectorValue'] !== null &&
                !ComparatorHelper::matchAgainst(
                    $rule['selectorComparator'],
                    $rule['selectorValue'],
                    $issue['selector']
                )
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function getIgnoreRules(): array
    {
        if ($this->ignoreRules === null) {
            $this->ignoreRules = (new Query())
                ->select([
                    'id',
                    'name',
                    'accountId',
                    'projectId',
                    'viewportId',
                    'pageUrlComparator',
                    'pageUrlValue',
                    'ruleId',
                    'xpathComparator',
                    'xpathValue',
                    'selectorComparator',
                    'selectorValue',
                ])
                ->from([IgnoreRuleRecord::TABLE])
                ->all();
        }

        return $this->ignoreRules;
    }

    // If querying the default viewport, then update internal
    // width and height
    private function updateDefaultViewport(
        ViewportModel $viewportModel,
        int $width,
        int $height
    ) {
        if (!$viewportModel->accountId ||
            array_key_exists($viewportModel->accountId, $this->updatedViewports)
        ) {
            return;
        }

        if ($viewportModel->width !== $width || $viewportModel->height !== $height) {
            $viewportModel->width = $width;
            $viewportModel->height = $height;

            $plugin = Plugin::getInstance();
            $plugin->getViewports()->saveViewport($viewportModel);
        }

        $this->updatedViewports[$viewportModel->accountId] = true;
    }

    private function savePage(
        string $pageTitle,
        string $pageUrl
    ) {
        $plugin = Plugin::getInstance();

        $pageElement = PageElement::find()
            ->projectId($this->project->id)
            ->pageUrl($pageUrl)
            ->one();

        if (!$pageElement) {
            $pageElement = new PageElement();
            $pageElement->projectId = $this->project->id;
        }

        $pageElement->pageTitle = $this->cleanPageTitle($pageTitle);
        $pageElement->pageUrl = $pageUrl;

        $pageElement->siteId = $this->project->siteId;

        $pageElement->wcagAIssues = 0;
        $pageElement->wcagAaIssues = 0;
        $pageElement->wcagAaaIssues = 0;
        $pageElement->bestPracticeIssues = 0;
        $pageElement->totalIssues = 0;

        if (!$plugin->getPages()->savePage($pageElement)) {
            return null;
        }

        return $pageElement->id;
    }

    private function cleanPageTitle($title)
    {
        // Remove trailing ' - Site Name'
        $site = Craft::$app->getSites()->getSiteById($this->project->siteId, true);
        $name = $site->getName();
        if (StringHelper::endsWith($title, $name, false)) {
            $newTitle = substr($title, 0, -strlen($name));
            $newTitle = trim($newTitle, ' -');
            if ($newTitle !== '') {
                $title = $newTitle;
            }
        }

        return $title;
    }

    private function saveIssues(
        int $siteId,
        int $pageId,
        int $viewportId,
        array $issues
    ) {
        $plugin = Plugin::getInstance();

        $plugin->getIssues()->resolveIssuesByPageId($pageId);

        foreach ($issues as $issue) {
            if ($this->isIgnorableIssue($issue, $viewportId)) {
                continue;
            }

            $issueElement = $plugin->getIssues()->getIssueByRule(
                $pageId,
                $viewportId,
                $issue['rule_id'],
                $issue['xpath']
            );

            if (!$issueElement) {
                $issueElement = new IssueElement();
                $issueElement->pageId = $pageId;
                $issueElement->viewportId = $viewportId;
            }

            $issueElement->siteId = $siteId;

            $issueElement->certainty = $issue['certainty'];
            $issueElement->priority = $issue['priority'];
            $issueElement->ruleId = $issue['rule_id'];
            $issueElement->ruleName = $issue['rule_name'];
            $issueElement->ruleDescription = $issue['rule_description'];
            $issueElement->snippet = $issue['snippet'];
            $issueElement->xpath = $issue['xpath'];
            $issueElement->selector = $issue['selector'];
            $issueElement->wcag = $issue['wcag'];
            $issueElement->wcagLevel = $issue['wcag_level'];
            $issueElement->bestPractice = $issue['best_practice'];
            $issueElement->summary = $issue['summary'];
            $issueElement->resolved = false;

            $plugin->getIssues()->saveIssue($issueElement);
        }
    }
}
