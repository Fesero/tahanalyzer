<?php

namespace Fesero\Tahanalyzer;

use Symfony\Component\HttpClient\HttpClient;

class ApiClient
{
    private $endPoint;
    private $token;

    public function __construct(string $endPoint, string $token)
    {
        $this->endPoint = $endPoint;
        $this->token = $token;
    }

    public function sendResults(array $data): bool
    {
        $client = HttpClient::create();
        $response = $client->request('POST', $this->endPoint, [
            'headers' => ['Authorization' => "Bearer {$this->token}"],
            'json' => $data,
        ]);

        return $response->getStatusCode() === 200;
    }
}