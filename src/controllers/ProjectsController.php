<?php
namespace osim\craft\focus\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Queue;
use craft\web\Controller;
use osim\craft\focus\helpers\OsimFocusProjectApi;
use osim\craft\focus\jobs\OsimFocusTest;
use osim\craft\focus\models\Project as ProjectModel;
use osim\craft\focus\models\ProjectViewport as ProjectViewportModel;
use osim\craft\focus\models\OsimFocusProject as OsimFocusProjectModel;
use osim\craft\focus\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ProjectsController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();
        $this->requireAdmin(true);
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/projects/_index',
            [
                'items' => $plugin->getProjects()->getAllProjects(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?ProjectModel $item = null): Response
    {
        if ($id) {
            $plugin = Plugin::getInstance();

            $item = $plugin->getProjects()->getProjectById($id);

            if (!$item) {
                throw new NotFoundHttpException('Project not found.');
            }
        } elseif ($item === null) {
            $item = new ProjectModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/projects/_item',
            [
                'id' => $id,
                'item' => $item,
            ]
        );
    }

    public function actionSave(): ?Response
    {
        $plugin = Plugin::getInstance();

    	$this->requirePostRequest();

        $data = Craft::$app->getRequest()->getParam('data');
        $newOsimFocusProjectId = $data['newOsimFocusProjectId'];
        $data = $plugin->getProjects()->typecastData($data);

        if (!Craft::$app->getIsMultiSite()) {
            $data['siteId'] = Craft::$app->getSites()->getPrimarySite()->id;
        }

        if ($data['id'] ?? null) {
            $item = $plugin->getProjects()->getProjectById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Project not found.');
            }

            $previousViewports = $plugin->getProjects()->getProjectViewports($data['id']);
            $previousViewports = ArrayHelper::index($previousViewports, 'viewportId');
        } else {
            $item = new ProjectModel();
            $previousViewports = [];
        }

        $item->setAttributes($data, false);

        $newViewports = [];
        foreach ($data['viewportIds'] as $viewportId) {
            if (array_key_exists($viewportId, $previousViewports)) {
                $newViewports[] = $previousViewports[$viewportId];
            } else {
                $viewport = new ProjectViewportModel();
                $viewport->viewportId = $viewportId;
                $newViewports[] = $viewport;
            }
        }

        $item->setViewports($newViewports);

        if ($newOsimFocusProjectId) {
            $osimFocusProjectId = $this->getNewOsimFocusProjectId($item);

            if ($osimFocusProjectId === null) {
                Craft::$app->getSession()->setError(Plugin::t('New OSiM Focus project ID was not created.'));
            } else {
                $item->osimFocusProjectId = $osimFocusProjectId;
            }
        }

        if (!$plugin->getProjects()->saveProject($item)) {
            Craft::$app->getSession()->setError(Plugin::t('Project not saved.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Plugin::t('Project saved.'));

        return $this->redirectToPostedUrl();
    }
    private function getNewOsimFocusProjectId(ProjectModel $projectModel): ?string
    {
        $plugin = Plugin::getInstance();
        $accountModel = $plugin->getAccounts()->getAccountById($projectModel->accountId);

        if (!$accountModel) {
            return null;
        }

        $osimFocusProjectApi = new OsimFocusProjectApi($accountModel->osimFocusApiKey);

        $osimFocusProjectModel = new OsimFocusProjectModel();
        $osimFocusProjectModel->name = $projectModel->name;

        $site = Craft::$app->getSites()->getSiteById($projectModel->siteId, true);
        $osimFocusProjectModel->description = $site->baseUrl;

        $osimFocusProjectModel = $osimFocusProjectApi->postProject($osimFocusProjectModel);

        if (!$osimFocusProjectModel) {
            return null;
        }

        return $osimFocusProjectModel->uid;
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getProjects()->deleteProjectById($id);

        return $this->asSuccess();
    }

    public function actionTest(): ?Response
    {
        $plugin = Plugin::getInstance();
        if (!$plugin->getAccounts()->hasAccounts()) {
            Craft::$app->getSession()->setError(Plugin::t('Add a OSiM Focus account and at least one project before running tests.'));
            return $this->redirect(Plugin::HANDLE . '/settings/accounts');
        } elseif (!$plugin->getProjects()->hasProjects()) {
            Craft::$app->getSession()->setError(Plugin::t('Add at least one project before running tests.'));
            return $this->redirect(Plugin::HANDLE . '/settings/projects');
        }

        Queue::push(new OsimFocusTest());

        Craft::$app->getSession()->setNotice(Plugin::t('Test job queued.'));

        return null;
    }
}
