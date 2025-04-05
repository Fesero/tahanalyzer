<?php

namespace Fesero\Tahanalyzer;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    private $baseUrl;
    private $token;
    private $lastResponse;
    private $lastError;
    private const CHUNK_SIZE = 3; // Number of files per chunk

    public function __construct(string $baseUrl, string $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }

    private function splitIntoChunks(array $data): array
    {
        if (!isset($data['files']) || empty($data['files'])) {
            return [$data];
        }

        $files = $data['files'];
        $chunks = [];
        $fileEntries = array_chunk($files, self::CHUNK_SIZE, true);

        foreach ($fileEntries as $index => $chunkFiles) {
            $chunkData = $data;
            $chunkData['files'] = $chunkFiles;

            $chunks[] = $chunkData;
        }

        return $chunks;
    }

    public function sendResults(array $data, string $type, string $projectName): bool
    {
        $client = HttpClient::create(['timeout' => 120]);
        $chunks = $this->splitIntoChunks($data);
        $success = true;
        $hasErrors = false;

        foreach ($chunks as $chunk) {
            $endpoint = "{$this->baseUrl}/tests/{$type}";
            
            $chunk['projectName'] = $projectName;

            $this->lastResponse = $client->request('POST', $endpoint, [
                'headers' => ['Authorization' => "Bearer {$this->token}"],
                'json' => $chunk
            ]);
        }

        return $success;
    }

    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastResponseContent(): ?array
    {
        if (!$this->lastResponse) {
            return null;
        }

        try {
            return json_decode($this->lastResponse->getContent(), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getFormattedError(): string
    {
        if ($this->lastError) {
            return "Ошибка соединения: " . $this->lastError;
        }

        if (!$this->lastResponse) {
            return "Нет ответа от сервера";
        }

        $statusCode = $this->lastResponse->getStatusCode();
        $content = null;
        $errorDetails = [];

        try {
            $content = $this->lastResponse->toArray(false); // Throw exception on non-2xx
        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface | \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface $e) {
            // Error status codes (4xx, 5xx)
            try {
                $content = $e->getResponse()->toArray(false);
            } catch (\Exception $decodeError) {
                // Failed to decode JSON body
                $errorDetails[] = "Не удалось декодировать тело ответа: " . $decodeError->getMessage();
                $errorDetails[] = "Тело ответа (Raw): " . $e->getResponse()->getContent(false);
            }
        } catch (\Exception $e) {
            // Other exceptions (network, etc.)
            return "Ошибка получения ответа: " . $e->getMessage();
        }

        $baseMessage = "Ошибка сервера (HTTP {$statusCode})";
        if (isset($content['message']) && is_string($content['message'])) {
            $baseMessage = $content['message'] . " (HTTP {$statusCode})";
        }
        if (isset($content['error']) && is_string($content['error'])) {
            $errorDetails[] = "Детали: " . $content['error'];
        }

        // Process Laravel validation errors specifically
        if (isset($content['errors']) && is_array($content['errors'])) {
            foreach ($content['errors'] as $field => $fieldErrors) {
                if (is_array($fieldErrors)) {
                    $errorDetails[] = "- Поле '{$field}': " . implode(", ", $fieldErrors);
                } else {
                    $errorDetails[] = "- Поле '{$field}': " . $fieldErrors;
                }
            }
        }

        $fullMessage = [$baseMessage];
        if (!empty($errorDetails)) {
            $fullMessage[] = implode("\n", $errorDetails);
        }

        return implode("\n", $fullMessage);
    }
}