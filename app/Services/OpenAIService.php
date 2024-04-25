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
        $formattedConversation = array_map(function ($message) {
            if (isset($message['content']) && is_array($message['content']) && isset($message['content']['type'])) {
                if ($message['content']['type'] === 'image_url') {
                    // Correctly formatting the image_url to be an object with 'url' key
                    $imageContent = [
                        'type' => 'image_url',
                        'image_url' => $message['content']['image_url']
                    ];
                    // If there is text associated with the image, include it as a separate text object
                    if (isset($message['content']['text'])) {
                        return [
                            'role' => $message['role'],
                            'content' => [
                                ['type' => 'text', 'text' => $message['content']['text']],
                                $imageContent
                            ]
                        ];
                    } else {
                        return [
                            'role' => $message['role'],
                            'content' => [$imageContent]
                        ];
                    }
                } else {
                    // If no special handling is needed, return the message as is
                    return $message;
                }
            } else {
                // This assumes that 'content' is already properly formatted if not an array with 'type'
                return $message;
            }
        }, $conversation);
    
        try {
            $response = $this->client->post('/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $formattedConversation,
                ],
            ]);
    
            $responseBody = json_decode($response->getBody(), true);
    
            // Debug output
            // echo '<pre>'; print_r($responseBody); // Uncomment for debugging
    
            // Return the necessary response components
            return [
                'ai_response' => $responseBody['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.',
                'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? null,
                'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? null,
                'total_tokens' => $responseBody['usage']['total_tokens'] ?? null,
            ];
        } catch (GuzzleException $e) {
            return [
                'ai_response' => 'An error occurred: ' . $e->getMessage(),
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
            ];
        }
    }
    


    
}
