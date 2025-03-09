<?php

namespace Fesero\Tahanalyzer;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    private $endPoint;
    private $token;
    private $lastResponse;

    public function __construct(string $endPoint, string $token)
    {
        $this->endPoint = $endPoint;
        $this->token = $token;
    }

    public function sendResults(array $data): bool
    {
        $client = HttpClient::create();
        try {
            $this->lastResponse = $client->request('POST', $this->endPoint, [
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