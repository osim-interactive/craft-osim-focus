<?php
namespace osim\craft\focus\helpers;

use craft\helpers\UrlHelper;
use DateTime;
use DateTimeZone;
use osim\craft\focus\models\OsimFocusProject as OsimFocusProjectModel;
use yii\base\InvalidArgumentException;

class OsimFocusProjectApi
{
    use OsimFocusApiTrait;

    public function getProjects(): ?array
    {
        $response = $this->request(
            '/projects'
        );

        if (!$response) {
            return null;
        }

        $items = [];

        foreach ($response['items'] as $project) {
            $items[] = $this->getOsimFocusProjectModel($project);
        }

        return $items;
    }
    public function getProject(string $osimFocusProjectId): ?OsimFocusProjectModel
    {
        $response = $this->request(
            '/projects/' . rawurlencode($osimFocusProjectId)
        );

        if (!$response) {
            return null;
        }

        return $this->getOsimFocusProjectModel($response);
    }
    public function postProject(OsimFocusProjectModel $osimFocusProjectModel): ?OsimFocusProjectModel
    {
        if (!$osimFocusProjectModel->validate()) {
            throw new InvalidArgumentException('OSiM Focus project model is invalid.');
        }

        $data = $osimFocusProjectModel->getProjectApiData();

        $response = $this->request('/projects', 'POST', $data);

        if (!$response) {
            return null;
        }

        return $this->getOsimFocusProjectModel($response);
    }
    public function patchProject(OsimFocusProjectModel $osimFocusProjectModel): ?OsimFocusProjectModel
    {
        if (!$osimFocusProjectModel->validate() || !$osimFocusProjectModel->uid) {
            throw new InvalidArgumentException('OSiM Focus project model is invalid.');
        }

        $data = $osimFocusProjectModel->getProjectApiData();

        $response = $this->request(
            '/projects/' . rawurlencode($osimFocusProjectModel->uid),
            'PATCH',
            $data
        );

        if (!$response) {
            return null;
        }

        return $this->getOsimFocusProjectModel($response);
    }
    public function deleteProject(string $osimFocusProjectId): bool
    {
        $response = $this->request(
            '/projects/' . rawurlencode($osimFocusProjectId),
            'DELETE'
        );

        if (!$response) {
            return false;
        }

        return true;
    }

    private function getOsimFocusProjectModel(array $project): OsimFocusProjectModel
    {
        $data = [
            'uid' => $project['uid'],
            'name' => $project['name'],
            'description' => $project['description'],
            'default' => $project['default'],
            'certainty' => $project['certainty'],
            'priority' => $project['priority'],
            'wcag' => $project['wcag'],
            'wcagLevel' => $project['wcag_level'],
            'bestPractice' => $project['best_practice'],
            'store' => $project['store'],
            'userAgent' => $project['user_agent'],
            'viewportWidth' => $project['viewport_width'],
            'viewportHeight' => $project['viewport_height'],
            'delay' => $project['delay'],
        ];

        $dateCreated = new DateTime($project['insert_date_time']);
        // $dateCreated->setTimezone(new DateTimeZone('UTC'));
        $data['dateCreated'] = $dateCreated;

        $dateUpdated = new DateTime($project['update_date_time']);
        // $dateUpdated->setTimezone(new DateTimeZone('UTC'));
        $data['dateUpdated'] = $dateUpdated;

        return new OsimFocusProjectModel($data);
    }
}
