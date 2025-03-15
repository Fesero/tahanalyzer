<?php

namespace Fesero\Tahanalyzer;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    private $baseUrl;
    private $token;
    private $lastResponse;

    public function __construct(string $baseUrl, string $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }

    public function sendResults(array $data, string $type): bool
    {
        $client = HttpClient::create();

        $endpoint = "{$this->baseUrl}/tests/{$type}";
        
        try {
            $this->lastResponse = $client->request('POST', $endpoint, [
                'headers' => ['Authorization' => "Bearer {$this->token}"],
                'json' => $data,
            ]);

            return $this->lastResponse->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->lastResponse = new \stdClass();
            $this->lastResponse->error = $e->getMessage();
            return false;
        }
    }

    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }
}