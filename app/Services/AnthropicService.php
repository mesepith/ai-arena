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
            $formattedMessages = [
                'role' => 'user',
                'content' => []
            ];
    
            foreach ($conversation as $message) {
                if (isset($message['content']) && is_array($message['content']) && isset($message['content']['type'])) {
                    if ($message['content']['type'] === 'image_url') {
                        // Convert image URL to base64
                        $imageUrl = $message['content']['image_url']['url'];
                        $imageData = file_get_contents($imageUrl);
                        $base64Image = base64_encode($imageData);
    
                        $imageMessage = [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'image/png',
                                'data' => $base64Image
                            ]
                        ];
    
                        $formattedMessages['content'][] = $imageMessage;
                    } else {
                        $formattedMessages['content'][] = [
                            'type' => 'text',
                            'text' => $message['content']
                        ];
                    }
                } else {
                    $formattedMessages['content'][] = [
                        'type' => 'text',
                        'text' => $message['content']
                    ];
                }
            }
            
            echo '<pre>'; print_r($formattedMessages); exit;
            $response = $this->client->post('/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                ],
                'json' => [
                    'model' => $model,
                    'max_tokens' => 1024,
                    'messages' => [$formattedMessages]
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
