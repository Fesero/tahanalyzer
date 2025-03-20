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
            $chunkData['totals'] = $data['total'] ?? 0;
            if ($chunkData['files'] && $chunkData['totals']) {
                $chunks[] = $chunkData;
            }
        }

        return $chunks;
    }

    public function sendResults(array $data, string $type): bool
    {
        $client = HttpClient::create();
        $chunks = $this->splitIntoChunks($data);
        $success = true;
        $hasErrors = false;

        foreach ($chunks as $chunk) {
            $endpoint = "{$this->baseUrl}/tests/{$type}";
            
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
        $content = $this->getLastResponseContent();

        if (!$content) {
            return "Ошибка сервера (HTTP {$statusCode})";
        }

        $message = [];
        
        // Добавляем основное сообщение об ошибке
        if (isset($content['message'])) {
            $message[] = $content['message'];
        }

        // Добавляем детали ошибки
        if (isset($content['error'])) {
            $message[] = $content['error'];
        }

        // Добавляем ошибки валидации
        if (isset($content['errors'])) {
            if (is_array($content['errors'])) {
                foreach ($content['errors'] as $field => $errors) {
                    if (is_array($errors)) {
                        $message[] = "{$field}: " . implode(', ', $errors);
                    } else {
                        $message[] = "{$field}: {$errors}";
                    }
                }
            } else {
                $message[] = $content['errors'];
            }
        }

        // Если нет деталей, возвращаем базовое сообщение
        if (empty($message)) {
            return "Ошибка сервера (HTTP {$statusCode})";
        }

        return implode("\n", $message);
    }
}