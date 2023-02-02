<?php
namespace osim\craft\focus\controllers;

use Craft;
use craft\web\Controller;
use osim\craft\focus\helpers\OsimFocusProjectApi;
use osim\craft\focus\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class OsimFocusController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireAcceptsJson();
        $this->requireAdmin(true);
    }

    public function actionProjectOptions(int $accountId): Response
    {
        $plugin = Plugin::getInstance();

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
}
