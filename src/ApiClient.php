<?php
declare(strict_types=1);

namespace Fesero\Tahanalyzer;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    private $lastResponse;
    private $lastError;
    private const CHUNK_SIZE = 3; // Number of files per chunk

    /**
     * Summary of __construct
     * @param string $baseUrl
     * @param string $token
     */
    public function __construct(private string $baseUrl, private string $token) {}

    /**
     * Summary of splitIntoChunks
     * @param array $data
     * @return array[]
     */
    private function splitIntoChunks(array $data): array
    {
        if (!isset($data['files']) || empty($data['files'])) {
            return [$data];
        }

        $files = $data['files'];
        $chunks = [];
        $fileEntries = array_chunk($files, self::CHUNK_SIZE, true);

        foreach ($fileEntries as $chunkFiles) {
            $chunkData = $data;
            $chunkData['files'] = $chunkFiles;

            $chunks[] = $chunkData;
        }

        return $chunks;
    }

    /**
     * Summary of sendResults
     * @param array $data
     * @param string $type
     * @param string $projectName
     * @return bool
     */
    public function sendResults(array $data, string $type, string $projectName): bool
    {
        $client = HttpClient::create(['timeout' => 120]);
        $chunks = $this->splitIntoChunks($data);
        $success = true;

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

    /**
     * Summary of getLastResponse
     * @return ResponseInterface
     */
    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * Summary of getLastError
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Summary of getLastResponseContent
     * @return array|null
     */
    public function getLastResponseContent(): ?array
    {
        if (!$this->lastResponse) {
            return null;
        }

        try {
            return json_decode(json: $this->lastResponse->getContent(), associative: true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Summary of getFormattedError
     * @return string
     */
    public function getFormattedError(): string
    {
        if ($this->lastError) {
            return "Ошибка соединения: {$this->lastError}";
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
                $errorDetails[] = "- Поле '{$field}': " . \is_array($fieldErrors) ? implode(", ", $fieldErrors) : $fieldErrors;
            }
        }

        $fullMessage = [$baseMessage];
        if (!empty($errorDetails)) {
            $fullMessage[] = implode("\n", $errorDetails);
        }

        return implode("\n", $fullMessage);
    }
}