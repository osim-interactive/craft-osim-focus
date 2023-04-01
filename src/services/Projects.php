<?php
namespace osim\craft\focus\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\services\ProjectConfig;
use osim\craft\focus\Plugin;
use osim\craft\focus\models\Project as ProjectModel;
use osim\craft\focus\models\ProjectViewport as ProjectViewportModel;
use osim\craft\focus\records\Project as ProjectRecord;
use osim\craft\focus\records\ProjectViewport as ProjectViewportRecord;
use osim\craft\focus\services\Accounts;
use osim\craft\focus\services\Viewports;
use yii\base\Component;

class Projects extends Component
{
    const PROJECT_CONFIG_PATH = 'osim.focus.projects';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            $query = (new Query())
                ->select([
                    'id',
                    'name',
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
                    'uid',
                ])
                ->from([ProjectRecord::TABLE])
                ->orderBy(['name' => \SORT_ASC]);

            foreach ($query->all() as $row) {
                $items[] = new ProjectModel($row);
            }

            $this->items = new MemoizableArray($items);
        }

        return $this->items;
    }

    public function hasProjects(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllProjects(): array
    {
        return $this->items()->all();
    }

    public function getProjectById(int $id): ?ProjectModel
    {
        return $this->items()->firstWhere('id', $id);
    }
    public function getProjectViewports(int $projectId): array
    {
        $items = [];

        $query = (new Query())
            ->select([
                'id',
                'projectId',
                'viewportId',
            ])
            ->from([ProjectViewportRecord::TABLE])
            ->where(['projectId' => $projectId]);

        foreach ($query->all() as $row) {
            $items[] = new ProjectViewportModel($row);
        }

        return $items;
    }
    public function deleteProjectById(int $id): bool
    {
        $model = $this->getProjectById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteProject($model);

    }
    public function deleteProject(ProjectModel $model): bool
    {
        Craft::$app->getProjectConfig()->remove(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid
        );

        return true;
    }

    public function saveProject(ProjectModel $model, bool $runValidation = true): bool
    {
        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Ignore rule not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(ProjectRecord::TABLE, $model->id);
        }

        Craft::$app->getProjectConfig()->set(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid,
            $model->getConfig()
        );

        if ($isNew) {
            $model->id = Db::idByUid(ProjectRecord::TABLE, $model->uid);
        }

        return true;
    }

    public function handleDeleted(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $record = $this->getRecord($uid);

        if ($record->getIsNewRecord()) {
            return;
        }

        $id = $record->id;

        Craft::$app->db->createCommand()
            ->delete(ProjectViewportRecord::TABLE, ['projectId' => $id])
            ->execute();

        $record->delete();

        $this->items = null;
    }
    public function handleChanged(ConfigEvent $event): void
    {
        $plugin = Plugin::getInstance();

        $data = $event->newValue;

        $projectConfig = Craft::$app->getProjectConfig();

        // Ensure the account is in place first
        $projectConfig->processConfigChanges(
            Accounts::PROJECT_CONFIG_PATH . '.' . $data['account']
        );
        $accountRecord = $plugin->getAccounts()->getRecord($data['account']);
        $data['accountId'] = $accountRecord->id;

        // Ensure the site is in place first
        $projectConfig->processConfigChanges(
            ProjectConfig::PATH_SITES . '.' . $data['account']
        );
        $siteModel = Craft::$app->getSites()->getSiteByUid(
            $data['site'],
            true
        );
        $data['siteId'] = $siteModel->id;

        $data['viewports'] ??= [];

        // Ensure the viewports are in place first
        $data['viewportIds'] = [];
        foreach ($data['viewports'] as $viewportUid) {
            $projectConfig->processConfigChanges(
                Viewports::PROJECT_CONFIG_PATH . '.' . $viewportUid
            );
            $viewportRecord = $plugin->getViewports()->getRecord($viewportUid);
            $data['viewportIds'][] = $viewportRecord->id;
        }

        // Clean data
        $data = $this->typecastData($data);

        $uid = $event->tokenMatches[0];
        $record = $this->getRecord($uid);

        $record->name = $data['name'];
        $record->siteId = $data['siteId'];
        $record->accountId = $data['accountId'];
        $record->osimFocusProjectId = $data['osimFocusProjectId'];
        $record->sitemapUrl = $data['sitemapUrl'];
        $record->certainty = $data['certainty'];
        $record->priority = $data['priority'];
        $record->wcag = $data['wcag'];
        $record->wcagLevel = $data['wcagLevel'];
        $record->bestPractice = $data['bestPractice'];
        $record->store = $data['store'];
        $record->userAgent = $data['userAgent'];
        $record->delay = $data['delay'];
        $record->uid = $uid;

        $record->save(false);

        $previousViewportsIds = $this->getProjectViewports($record->id);
        $previousViewportsIds = ArrayHelper::getColumn($previousViewportsIds, 'id');
        $newViewportIds = [];

        foreach ($data['viewportIds'] as $viewportId) {
            $viewportRecord = $this->getProjectViewportRecord(
                $record->id,
                $viewportId
            );

            $viewportRecord->projectId = $record->id;
            $viewportRecord->viewportId = $viewportId;

            $viewportRecord->save(false);

            $newViewportIds[] = $viewportRecord->id;
        }

        $deleteIds = array_diff($previousViewportsIds, $newViewportIds);

        Craft::$app->db->createCommand()
            ->delete(ProjectViewportRecord::TABLE, ['id' => $deleteIds])
            ->execute();

        $this->items = null;
    }
    private function getRecord(int|string $criteria): ProjectRecord
    {
        $query = ProjectRecord::find();

        if (is_numeric($criteria)) {
            $query->andWhere(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->andWhere(['uid' => $criteria]);
        }

        return $query->one() ?? new ProjectRecord();
    }
    private function getProjectViewportRecord(
        int $projectId,
        int $viewportId
    ): ProjectViewportRecord
    {
        $query = ProjectViewportRecord::find()
            ->andWhere(['projectId' => $projectId])
            ->andWhere(['viewportId' => $viewportId]);

        return $query->one() ?? new ProjectViewportRecord();
    }

    public function getProjectOptions(?string $emptyOption = null): array
    {
        $options = [];

        if ($emptyOption !== null) {
            $options[0] = $emptyOption;
        }

        foreach ($this->getAllProjects() as $model) {
            $options[$model->id] = $model->getOptionName();
        }

        return $options;
    }

    public function typecastData(array $data): array
    {
        $data['name'] = $data['name'] ?? '';
        $data['siteId'] = intval($data['siteId'] ?? 0);
        $data['siteId'] = ($data['siteId'] ? $data['siteId'] : null);
        $data['accountId'] = intval($data['accountId'] ?? 0);
        $data['accountId'] = ($data['accountId'] ? $data['accountId'] : null);
        $data['osimFocusProjectId'] = (($data['osimFocusProjectId'] ?? '') !== '' ? $data['osimFocusProjectId'] : null);
        $data['sitemapUrl'] = $data['sitemapUrl'] ?? '';
        $data['certainty'] = (($data['certainty'] ?? '') !== '' ? intval($data['certainty']) : null);
        $data['priority'] = (($data['priority'] ?? '') !== '' ? intval($data['priority']) : null);
        $data['wcag'] = (($data['wcag'] ?? '') !== '' ? intval($data['wcag']) : null);
        $data['wcagLevel'] = (($data['wcagLevel'] ?? '') !== '' ? $data['wcagLevel'] : null);
        $data['bestPractice'] = (($data['bestPractice'] ?? '') !== '' ? intval($data['bestPractice']) : null);
        $data['store'] = (($data['store'] ?? '') !== '' ? intval($data['store']) : null);
        $data['userAgent'] = (($data['userAgent'] ?? '') !== '' ? $data['userAgent'] : null);
        $data['delay'] = (($data['delay'] ?? '') !== '' ? intval($data['delay']) : null);

        $data['viewportIds'] ??= [];
        if (!is_array($data['viewportIds'])) {
            $data['viewportIds'] = [];
        }
        $data['viewportIds'] = array_map('intval', $data['viewportIds']);
        foreach ($data['viewportIds'] as $key => $value) {
            if ($value <= 0) {
                unset($data['viewportIds'][$key]);
            }
        }
        $data['viewportIds'] = array_values($data['viewportIds']);

        return $data;
    }
}
