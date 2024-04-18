<?php

namespace App\Services;

use App\Contracts\AIServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OpenAIService implements AIServiceInterface
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com',
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
        $this->apiKey = env('OPENAI_API_KEY');
    }

    public function generateResponse($conversation, $model)
    {
        try {
            $response = $this->client->post('/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $conversation,
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);

            // Extract the AI's response message
            $aiResponse = $responseBody['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.';

            // Extract token counts from the response
            $promptTokens = $responseBody['usage']['prompt_tokens'] ?? null;
            $completionTokens = $responseBody['usage']['completion_tokens'] ?? null;
            $totalTokens = $responseBody['usage']['total_tokens'] ?? null;

            return [
                'ai_response' => $aiResponse,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
            ];
        } catch (GuzzleException $e) {
            // Handle any exceptions that occur during the API call
            return [
                'ai_response' => 'An error occurred: ' . $e->getMessage(),
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
            ];
        }
    }
}
