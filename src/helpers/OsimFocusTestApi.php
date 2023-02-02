<?php
namespace osim\craft\focus\helpers;

use osim\craft\focus\models\OsimFocusProject as OsimFocusProjectModel;

class OsimFocusTestApi
{
    use OsimFocusApiTrait;

    public function testUrl(
        string $url,
        OsimFocusProjectModel $osimFocusProjectModel
    ) {
        $data = array_merge(
            [
                'url' => $url,
            ],
            $osimFocusProjectModel->getTestApiData()
        );

        return $this->request('/test/url', 'POST', $data);
    }

    public function testFragment(
        string $fragment,
        OsimFocusProjectModel $osimFocusProjectModel
    ) {
        $data = array_merge(
            [
                'fragment' => $fragment,
            ],
            $osimFocusProjectModel->getTestApiData()
        );

        return $this->request('/test/fragment', 'POST', $data);
    }
}
