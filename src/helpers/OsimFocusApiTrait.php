<?php
namespace osim\craft\focus\helpers;

use Craft;

trait OsimFocusApiTrait
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = Craft::parseEnv($apiKey);
    }

    private function request(string $path, string $method = 'GET', array $data = []): ?array
    {
        // Test API 404's unless trailing slash (Others are fine.)
        $url = 'https://api.focus.osim.digital/' . trim($path, '/');
        $method = strtoupper($method);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey
        ];

        if ($method === 'POST' || $method === 'PUT') {
            $headers[] = 'Content-Type: application/json';

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        curl_close($ch);

        if (!$response) {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->logStatus($httpStatusCode);

            return null;
        }

        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->logStatus($httpStatusCode);

        $response = json_decode($response, true);

        $response['status'] = $httpStatusCode;

        return $response;
    }

    private function logStatus(int $httpStatusCode)
    {
        if ($httpStatusCode === 401 || $httpStatusCode === 403) {
            Craft::error('OSiM Focus API Key is invalid.', __METHOD__);
        } elseif ($httpStatusCode === 402) {
            Craft::warning('OSiM Focus payment required.', __METHOD__);
        }
    }
}
