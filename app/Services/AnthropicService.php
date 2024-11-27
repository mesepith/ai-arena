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
            $formattedMessages = [];
            $userContent = [];
            $httpClient = new Client(); // Guzzle HTTP client for downloading files
    
            foreach ($conversation as $message) {
                if ($message['role'] === 'user') {
                    if (isset($message['content']) && is_array($message['content']) && isset($message['content']['type'])) {
                        if ($message['content']['type'] === 'image_url') {
                            $fileUrl = str_replace(' ', '%20', $message['content']['image_url']['url']); // Ensure URL encoding
    
                            try {
                                $response = $httpClient->get($fileUrl, [
                                    'headers' => [
                                        'User-Agent' => 'Mozilla/5.0 (compatible; AnthropicService/1.0)',
                                    ],
                                ]);
                                $fileData = $response->getBody()->getContents();
                                if (!$fileData) {
                                    throw new \Exception("Failed to fetch file data from URL: $fileUrl");
                                }
    
                                // Determine if the file is an image or PDF
                                $fileType = $response->getHeaderLine('Content-Type');
                                if (strpos($fileType, 'image/') !== false) {
                                    // Handle image
                                    $base64Image = base64_encode($fileData);
                                    $userContent[] = [
                                        'type' => 'image',
                                        'source' => [
                                            'type' => 'base64',
                                            'media_type' => $fileType, // e.g., image/png or image/jpeg
                                            'data' => $base64Image
                                        ]
                                    ];
                                } elseif ($fileType === 'application/pdf') {
                                    // Handle PDF
                                    $base64Pdf = base64_encode($fileData);
                                    $userContent[] = [
                                        'type' => 'document',
                                        'source' => [
                                            'type' => 'base64',
                                            'media_type' => $fileType,
                                            'data' => $base64Pdf
                                        ]
                                    ];
                                } else {
                                    throw new \Exception("Unsupported file type: $fileType");
                                }
                            } catch (\Exception $e) {
                                throw new \Exception("Error downloading file: " . $e->getMessage());
                            }
                        }
                    } else {
                        $userContent[] = [
                            'type' => 'text',
                            'text' => $message['content']
                        ];
                    }
                } else {
                    if (!empty($userContent)) {
                        $formattedMessages[] = [
                            'role' => 'user',
                            'content' => $userContent
                        ];
                        $userContent = [];
                    }
    
                    $formattedMessages[] = [
                        'role' => $message['role'],
                        'content' => [['type' => 'text', 'text' => $message['content']]]
                    ];
                }
            }
    
            if (!empty($userContent)) {
                $formattedMessages[] = [
                    'role' => 'user',
                    'content' => $userContent
                ];
            }
    
            // Send API Request
            $response = $this->client->post('/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'anthropic-beta' => 'pdfs-2024-09-25'
                ],
                'json' => [
                    'model' => $model,
                    'max_tokens' => 1024,
                    'messages' => $formattedMessages
                ],
            ]);
    
            $responseBody = json_decode($response->getBody(), true);
            $aiContent = $responseBody['content'][0]['text'] ?? 'Sorry, I could not generate a response.';
    
            return [
                'ai_response' => $aiContent,
                'prompt_tokens' => $responseBody['usage']['input_tokens'] ?? null,
                'completion_tokens' => $responseBody['usage']['output_tokens'] ?? null,
                'total_tokens' => ($responseBody['usage']['input_tokens'] ?? 0) + ($responseBody['usage']['output_tokens'] ?? 0),
            ];
        } catch (GuzzleException $e) {
            return [
                'ai_response' => 'An error occurred: ' . $e->getMessage(),
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
            ];
        } catch (\Exception $e) {
            return [
                'ai_response' => 'An error occurred: ' . $e->getMessage(),
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
            ];
        }
    }
    
}
