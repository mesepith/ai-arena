<?php

namespace App\Services;

use App\Contracts\AIServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AnthropicService implements AIServiceInterface
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.anthropic.com',
            'headers' => [
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ]
        ]);
        $this->apiKey = env('ANTHROPIC_API_KEY');
    }

    public function generateResponse($conversation, $model)
    {
        try {
            $response = $this->client->post('/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                ],
                'json' => [
                    'model' => $model,
                    'max_tokens' => 4096,
                    'messages' => $conversation
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);
            $aiContent = $responseBody['content'][0]['text'] ?? 'Sorry, I could not generate a response.';

            // Extract token counts from the response if available
            $inputTokens = $responseBody['usage']['input_tokens'] ?? null;
            $outputTokens = $responseBody['usage']['output_tokens'] ?? null;
            $totalTokens = $inputTokens + $outputTokens;

            return [
                'ai_response' => $aiContent,
                'prompt_tokens' => $inputTokens,
                'completion_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
            ];
        } catch (GuzzleException $e) {
            // Handle API call exception
            return [
                'ai_response' => 'An error occurred: ' . $e->getMessage(),
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
            ];
        }
    }
}
