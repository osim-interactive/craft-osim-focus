<?php
namespace osim\craft\focus\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use osim\craft\focus\Plugin;
use osim\craft\focus\models\Account as AccountModel;
use osim\craft\focus\records\Account as AccountRecord;
use yii\base\Component;

class Accounts extends Component
{
    const PROJECT_CONFIG_PATH = 'osim.focus.accounts';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            foreach ($this->createItemsQuery()->all() as $result) {
                $items[] = new AccountModel($result);
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
                'name',
                'osimFocusApiKey',
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
            ->from([AccountRecord::TABLE])
            ->orderBy(['name' => \SORT_ASC]);

        return $query;
    }

    public function hasAccounts(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllAccounts(): array
    {
        return $this->items()->all();
    }

    public function getAccountById(int $id): ?AccountModel
    {
        return $this->items()->firstWhere('id', $id);
    }

    public function deleteAccountById(int $id): bool
    {
        $model = $this->getAccountById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteAccount($model);

    }
    public function deleteAccount(AccountModel $model): bool
    {
        Craft::$app->getProjectConfig()->remove(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid
        );

        return true;
    }

    public function saveAccount(AccountModel $model, bool $runValidation = true): bool
    {
        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Account not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(AccountRecord::TABLE, $model->id);
        }

        Craft::$app->getProjectConfig()->set(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid,
            $model->getConfig()
        );

        if ($isNew) {
            $model->id = Db::idByUid(AccountRecord::TABLE, $model->uid);
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
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;
        $data = $this->typecastData($data);

        $record = $this->getRecord($uid);
        $isNew = $record->getIsNewRecord();

        $record->name = $data['name'];
        $record->osimFocusApiKey = $data['osimFocusApiKey'];
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

        $this->items = null;
    }
    public function getRecord(int|string $criteria): AccountRecord
    {
        $query = AccountRecord::find();

        if (is_numeric($criteria)) {
            $query->andWhere(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->andWhere(['uid' => $criteria]);
        }

        return $query->one() ?? new AccountRecord();
    }

    public function getAccountOptions(?string $emptyOption = null)
    {
        $options = [];

        if ($emptyOption !== null) {
            $options[0] = $emptyOption;
        }

        foreach ($this->getAllAccounts() as $model) {
            $options[$model->id] = $model->getOptionName();
        }

        return $options;
    }

    public function typecastData(array $data): array
    {
        $data['name'] = $data['name'] ?? '';
        $data['osimFocusApiKey'] = $data['osimFocusApiKey'] ?? '';
        $data['certainty'] = (($data['certainty'] ?? '') !== '' ? intval($data['certainty']) : null);
        $data['priority'] = (($data['priority'] ?? '') !== '' ? intval($data['priority']) : null);
        $data['wcag'] = (($data['wcag'] ?? '') !== '' ? intval($data['wcag']) : null);
        $data['wcagLevel'] = (($data['wcagLevel'] ?? '') !== '' ? $data['wcagLevel'] : null);
        $data['bestPractice'] = (($data['bestPractice'] ?? '') !== '' ? intval($data['bestPractice']) : null);
        $data['store'] = (($data['store'] ?? '') !== '' ? intval($data['store']) : null);
        $data['userAgent'] = (($data['userAgent'] ?? '') !== '' ? $data['userAgent'] : null);
        $data['delay'] = (($data['delay'] ?? '') !== '' ? intval($data['delay']) : null);

        return $data;
    }
}
