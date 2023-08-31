<?php
namespace osim\craft\focus\controllers;

use Craft;
use craft\web\Controller;
use osim\craft\focus\Plugin;
use osim\craft\elements\Issue as IssueElement;
use yii\web\Response;

class IssuesController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();
        // $this->requireAdmin(false);
        $this->requirePermission(Plugin::PERMISSION_VIEW_ISSUES);
    }

    public function actionIndex(?int $projectId = null, ?int $viewportId = null): Response
    {
        return $this->renderTemplate(
            Plugin::HANDLE . '/issues/_index',
            [
                'pageId' => null,
                'projectId' => $projectId,
                'viewportId' => $viewportId,
            ]
        );
    }

    public function actionItem(int $id): Response
    {
        $plugin = Plugin::getInstance();

        $item = $plugin->getIssues()->getIssueById($id);

        if (!$item) {
            throw new NotFoundHttpException('Issue not found.');
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/issues/_item',
            [
                'id' => $id,
                'item' => $item,
            ]
        );
    }
}
