<?php
namespace osim\craft\focus\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\Queue;
use yii\console\ExitCode;

use osim\craft\focus\jobs\OsimFocusTest;

class JobsController extends Controller
{
    public ?int $siteId = null;
    public ?int $projectId = null;
    public ?int $viewportId = null;

    public $defaultAction = 'test';

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'test') {
            $options[] = 'siteId';
            $options[] = 'projectId';
            $options[] = 'viewportId';
        }

        return $options;
    }

    public function actionTest()
    {
        Queue::push(new OsimFocusTest([
            'siteId' => $this->siteId,
            'projectId' => $this->projectId,
            'viewportId' => $this->viewportId,
        ]));

        return ExitCode::OK;
    }
}
