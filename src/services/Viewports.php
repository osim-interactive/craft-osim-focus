<?php
namespace osim\craft\focus\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use osim\craft\focus\Plugin;
use osim\craft\focus\models\Viewport as ViewportModel;
use osim\craft\focus\records\Account as AccountRecord;
use osim\craft\focus\records\Viewport as ViewportRecord;
use osim\craft\focus\records\Project as ProjectRecord;
use osim\craft\focus\services\Accounts;
use yii\base\Component;

class Viewports extends Component
{
    const PROJECT_CONFIG_PATH = 'osim.focus.viewports';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            foreach ($this->createItemsQuery()->all() as $result) {
                $items[] = new ViewportModel($result);
            }

            $this->items = new MemoizableArray($items);
        }

        return $this->items;
    }
    private function createItemsQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'accountId',
                'name',
                'width',
                'height',
                'uid',
            ])
            ->from([ViewportRecord::TABLE])
            ->where(['accountId' => null])
            ->orderBy([
                'width' => \SORT_ASC,
                'height' => \SORT_ASC
            ]);

        return $query;
    }

    public function hasViewports(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllViewports(): array
    {
        return $this->items()->all();
    }

    public function getViewportById(int $id): ?ViewportModel
    {
        return $this->items()->firstWhere('id', $id);
    }

    public function getViewportsByProjectId(int $projectId): array
    {
        $plugin = Plugin::getInstance();

        $projectViewports = $plugin->getProjects()->getProjectViewports($projectId);

        $items = [];

        foreach ($projectViewports as $projectViewport) {
            $items[] = $this->getViewportById($projectViewport->viewportId);
        }

        if (!$items) {
            $accountId = (new Query())
                ->select(['accountId'])
                ->from([ProjectRecord::TABLE])
                ->where(['id' => $projectId])
                ->scalar();

            $items[] = $this->getViewportByAccountId($accountId);
        }

        usort($items, function($a, $b) {
            if ($a['width'] == $b['width']) {
                return $a['height'] <=> $b['height'];
            }

            return $a['width'] <=> $b['width'];
        });

        return $items;
    }

    public function getViewportByAccountId(
        int $accountId,
        ?int $width = null,
        ?int $height = null
    ): ViewportModel
    {
        $width = $width ?? 1024;
        $height = $height ?? 768;

        $result = (new Query())
            ->select([
                'id',
                'accountId',
                'name',
                'width',
                'height',
                'uid',
            ])
            ->from([ViewportRecord::TABLE])
            ->where(['accountId' => $accountId])
            ->one();

        if ($result) {
            $model = new ViewportModel($result);

            if ($model->width !== $width && $model->height !== $height) {
                $model->name = $this->getViewportName($width, $height);
                if ($width) {
                    $model->width = $width;
                }

                if ($height) {
                    $model->height = $height;
                }

                $this->saveViewport($model);
            }

            return $model;
        }

        $model = new ViewportModel();
        $model->accountId = $accountId;
        $model->name = $this->getViewportName($width, $height);
        $model->width = $width;
        $model->height = $height;

        $this->saveViewport($model);

        return $model;
    }

    private function getViewportName(int $width, int $height)
    {
        // Matches entry preview sizes
        if ($width <= 375) {
            return Craft::t('app', 'Phone');
        }

        if ($width <= 768) {
            return Craft::t('app', 'Tablet');
        }

        return Craft::t('app', 'Desktop');
    }

    public function deleteViewportById(int $id): bool
    {
        $model = $this->getViewportById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteViewport($model);

    }
    public function deleteViewport(ViewportModel $model): bool
    {
        Craft::$app->getProjectConfig()->remove(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid
        );

        return true;
    }

    public function saveViewport(ViewportModel $model, bool $runValidation = true): bool
    {
        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Ignore rule not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(ViewportRecord::TABLE, $model->id);
        }

        Craft::$app->getProjectConfig()->set(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid,
            $model->getConfig()
        );

        if ($isNew) {
            $model->id = Db::idByUid(ViewportRecord::TABLE, $model->uid);
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

        $record->delete();

        $this->items = null;
    }
    public function handleChanged(ConfigEvent $event): void
    {
        $plugin = Plugin::getInstance();

        $data = $event->newValue;

        $accountUid = $data['account'];

        // Ensure the account is in place first
        if ($accountUid) {
            $projectConfig = Craft::$app->getProjectConfig();
            $projectConfig->processConfigChanges(
                Accounts::PROJECT_CONFIG_PATH . '.' . $accountUid
            );

            $accountRecord = $plugin->getAccounts()->getRecord($accountUid);
            $data['accountId'] = $accountRecord->id;
        }

        $data = $this->typecastData($data);

        $uid = $event->tokenMatches[0];
        $record = $this->getRecord($uid);

        $record->accountId = $data['accountId'];
        $record->name = $data['name'];
        $record->width = $data['width'];
        $record->height = $data['height'];
        $record->uid = $uid;

        $record->save(false);

        // Clear caches
        $this->items = null;
    }
    public function getRecord(int|string $criteria): ViewportRecord
    {
        $query = ViewportRecord::find();

        if (is_numeric($criteria)) {
            $query->andWhere(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->andWhere(['uid' => $criteria]);
        }

        return $query->one() ?? new ViewportRecord();
    }

    public function getViewportOptions(?string $emptyOption = null): array
    {
        $options = [];

        if ($emptyOption !== null) {
            $options[0] = $emptyOption;
        }

        foreach ($this->getAllViewports() as $model) {
            $options[$model->id] = $model->getOptionName();
        }

        return $options;
    }

    public function typecastData(array $data): array
    {
        $data['accountId'] = intval($data['accountId'] ?? 0);
        $data['accountId'] = ($data['accountId'] ? $data['accountId'] : null);
        $data['name'] = $data['name'] ?? '';
        $data['width'] = $data['width'] ?? 0;
        $data['height'] = $data['height'] ?? 0;

        return $data;
    }
}
