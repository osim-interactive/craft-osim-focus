<?php
namespace osim\craft\focus\controllers;

use Craft;
use craft\helpers\Queue;
use craft\web\Controller;
use osim\craft\focus\helpers\OsimFocusProjectApi;
use osim\craft\focus\jobs\OsimFocusTest;
use osim\craft\focus\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class OsimFocusController extends Controller
{
    public function actionProjectOptions(int $accountId): Response
    {
        $plugin = Plugin::getInstance();

        $this->requireCpRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(true);

        $account = $plugin->getAccounts()->getAccountById($accountId);

        if (!$account) {
            throw new BadRequestHttpException('Account ID not set or is invalid.');
        }

        $osimFocusProjectApi = new OsimFocusProjectApi($account->osimFocusApiKey);
        $projects = $osimFocusProjectApi->getProjects();

        if ($projects === null) {
            throw new BadRequestHttpException('OSiM Focus API Key is invalid.');
        }

        $projectOptions = [];

        foreach ($projects as $project) {
            if ($project->default) {
                $priority = 1;
            } else {
                $priority = 0;
            }

            $projectOptions[] = [
                'value' => $project->uid,
                'name' => $project->name,
                'hint' => $project->uid,
                'priority' => $priority
            ];
        }

        return $this->asJson([
            'options' => $projectOptions
        ]);
    }

    public function actionTest(): ?Response
    {
        $this->requireCpRequest();
        $this->requireAdmin(false);

        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        $plugin = Plugin::getInstance();
        if (!$plugin->getAccounts()->hasAccounts()) {
            Craft::$app->getSession()->setError(Plugin::t('Add a OSiM Focus account and at least one project before running tests.'));

            if ($allowAdminChanges) {
                return $this->redirect(Plugin::HANDLE . '/settings/accounts');
            } else {
                return null;
            }
        } elseif (!$plugin->getProjects()->hasProjects()) {
            Craft::$app->getSession()->setError(Plugin::t('Add at least one project before running tests.'));

            if ($allowAdminChanges) {
                return $this->redirect(Plugin::HANDLE . '/settings/projects');
            } else {
                return null;
            }
        }

        Queue::push(new OsimFocusTest());

        Craft::$app->getSession()->setNotice(Plugin::t('Test job queued.'));

        return null;
    }
}
