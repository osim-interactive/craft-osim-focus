<?php
namespace osim\craft\focus\jobs;

use Craft;
use craft\db\Query;
use craft\queue\BaseJob;
use DateTime;
use osim\craft\focus\Plugin;
use osim\craft\focus\helpers\PageTester;
use osim\craft\focus\models\Viewport as ViewportModel;
use osim\craft\focus\records\Account as AccountRecord;
use osim\craft\focus\records\History as HistoryRecord;
use osim\craft\focus\records\Project as ProjectRecord;
use Xlient\Xml\Sitemap\SitemapIterator;

class OsimFocusTest extends BaseJob
{
    const FAIL_THRESHOLD = 10;

    public ?int $siteId = null;
    public ?int $projectId = null;
    public ?int $pageId = null;
    public ?int $viewportId = null;

    private $dateTimeNow;

    public function execute($queue): void
    {
        $this->dateTimeNow = new DateTime();

        $projects = $this->getOsimFocusProjects(
            $this->siteId,
            $this->projectId,
            $this->pageId,
            $this->viewportId
        );

        foreach ($projects as $project) {
            foreach ($project['viewports'] as $viewportModel) {
                if ($project['type'] === 'sitemap') {
                    $this->executeSitemapProject(
                        $project['projectId'],
                        $viewportModel,
                        $project['sitemapUrl'],
                    );
                } else {
                    $this->executePageProject(
                        $project['projectId'],
                        $viewportModel,
                        $project['pageUrl'],
                    );
                }
            }
        }
    }
    private function executeSitemapProject(
        int $projectId,
        ViewportModel $viewportModel,
        string $sitemapUrl
    ): void
    {
        $historyRecord = $this->getProjectHistory($projectId, $viewportModel->id);

        $pageTester = new PageTester($projectId);

        $sitemapIterator = new SitemapIterator();

        $sitemapIterator->open(
            $sitemapUrl,
            [
                'modified_date_time' => $historyRecord->dateJob
            ]
        );

        $fails = 0;
        foreach ($sitemapIterator as $pageUrl => $data) {
            $status = $pageTester->testPageUrl($pageUrl, $viewportModel);

            if ($status === 0) {
                continue;
            } elseif ($status === 500) {
                $historyRecord->status = 500;
                break;
            } elseif (in_array($status, [401, 402])) {
                $historyRecord->status = $status;
                break;
            } elseif ($status !== 200) {
                $fails++;

                if ($fails > self::FAIL_THRESHOLD) {
                    $historyRecord->status = 500;
                    break;
                }

                continue;
            }
        }

        $sitemapIterator->close();

        if ($historyRecord->status == null) {
            $historyRecord->dateJob = $this->dateTimeNow;
            $historyRecord->status = 200;
        }

        $historyRecord->save();
    }

    private function executePageProject(
        int $projectId,
        ViewportModel $viewportModel,
        string $pageUrl
    ): void
    {
        $pageTester = new PageTester($projectId);

        $pageTester->testPageUrl($pageUrl, $viewportModel);
    }

    private function getProjectHistory(int $projectId, int $viewportId): HistoryRecord
    {
        $historyRecord = HistoryRecord::findOne([
            'projectId' => $projectId,
            'viewportId' => $viewportId,
        ]);

        if (!$historyRecord) {
            $historyRecord = new HistoryRecord();
            $historyRecord->projectId = $projectId;
            $historyRecord->viewportId = $viewportId;
        }

        $historyRecord->dateLast = $this->dateTimeNow;
        $historyRecord->status = null;

        return $historyRecord;
    }

    private function getOsimFocusProjects(
        ?int $siteId,
        ?int $projectId,
        ?int $pageId,
        ?int $viewportId
    ): array
    {
        $plugin = Plugin::getInstance();

        $page = null;
        if ($pageId !== null && $projectId === null) {
            $page = $plugin->getPages()->getPageById($pageId);
            if ($page) {
                $projectId = $page->projectId;
            }
        }

        $projects = [];

        $where = [];

        if ($siteId) {
            $where['siteId'] = $siteId;
        }

        if ($projectId) {
            $where['id'] = $projectId;
        }

        $query = (new Query())
            ->select([
                'id',
                'siteId',
                'accountId',
                'osimFocusProjectId',
                'sitemapUrl',
                'certainty',
                'priority',
                'wcag',
                'wcagLevel',
                'bestPractice',
                'store',
                'userAgent',
                'delay',
            ])
            ->from([ProjectRecord::TABLE]);

        if ($where) {
            $query->where($where);
        }

        foreach ($query->all() as $row) {
            $account = (new Query())
                ->select([
                    'osimFocusApiKey',
                    'certainty',
                    'priority',
                    'wcag',
                    'wcagLevel',
                    'bestPractice',
                    'store',
                    'userAgent',
                    'delay',
                ])
                ->from([AccountRecord::TABLE])
                ->where([
                    'id' => $row['accountId']
                ])
                ->one();

            if ($page) {
                $result = [
                    'type' => 'page',
                    'pageUrl' => $page->pageUrl,
                    'projectId' => $row['id'],
                    'viewports' => null,
                ];
            } else {
                $result = [
                    'type' => 'sitemap',
                    'sitemapUrl' => $row['sitemapUrl'],
                    'projectId' => $row['id'],
                    'viewports' => null,
                ];
            }

            $viewports = $plugin->getViewports()->getViewportsByProjectId($row['id']);

            foreach ($viewports as $key => $viewport) {
                if ($viewportId && $viewport->id !== $viewportId) {
                    unset($viewports[$key]);
                }
            }

            $result['viewports'] = array_values($viewports);

            $projects[] = $result;
        }

        return $projects;
    }

    protected function defaultDescription(): string
    {
        return Plugin::t('Processing site pages through OSiM Focus.');
    }
}
