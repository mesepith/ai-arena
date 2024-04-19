<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Services\OpenAIService;
use App\Services\AnthropicService;
use Illuminate\Support\Str;
use Parsedown;
use stdClass;

class ChatController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function index(Request $request)
    {
        $selectedSessionId = $request->get('session_id');
    
        // Fetch distinct chat session IDs along with the first message as the title
        $sessions = Chat::select('chat_session_id', Chat::raw('MIN(SUBSTRING(user_message, 1, 50)) as title'), Chat::raw('MIN(created_at) as first_message_time'))
                         ->groupBy('chat_session_id')
                         ->orderBy('first_message_time', 'asc')
                         ->get();
    
        if (!$selectedSessionId) {
            
            // If no session_id is provided, start a new one
            $selectedSessionId = Str::uuid()->toString();
            session(['chat_session_id' => $selectedSessionId]);
            // Since this is a new session, there won't be any messages to display
            $chats = [];
        } else {
            
            // Fetch messages from the selected session
            $chats = Chat::where('chat_session_id', $selectedSessionId)->get();
            session(['chat_session_id' => $selectedSessionId]);

            // Format each AI response
            $chats->transform(function ($chat) {
                $chat->ai_response = $this->formatAIResponse($chat->ai_response);
                return $chat;
            });

            
        }

    
        return view('chat', compact('sessions', 'chats', 'selectedSessionId'));
    }
    

    public function store(Request $request)
    {
        // echo '<pre>'; print_r($request->input());
        $userMessage = $request->input('message');
        // Check if session_id is provided in the request, otherwise generate a new one
        $chatSessionId = $request->input('session_id');

        if( $chatSessionId !== session('chat_session_id')){ return response()->json(['success' => 0, 'message' => 'chat session is not matching']);}

        // Set the new or existing session_id in the session
        session(['chat_session_id' => $chatSessionId]);

        // Your existing code to handle the message and AI response
        $conversationHistory = Chat::where('chat_session_id', $chatSessionId)->get()->map(function ($chat) {
            return [
                ['role' => 'user', 'content' => $chat->user_message],
                ['role' => 'assistant', 'content' => $chat->ai_response],
            ];
        })->collapse()->toArray();

        $conversationHistory[] = ['role' => 'user', 'content' => $userMessage];

        $model = $request->input('model', 'gpt-3.5-turbo'); // Default to gpt-3.5-turbo if not provided
        $aiService = $this->getAIService($model);
        $responseBody = $aiService->generateResponse($conversationHistory, $model);

        // $aiResponse = $responseBody['ai_response'];

        // Extract and format the AI response
        $aiResponse = $this->formatAIResponse($responseBody['ai_response']);

        // Extract token counts from the OpenAI response if available
        $promptTokens = $responseBody['prompt_tokens'];
        $completionTokens = $responseBody['completion_tokens'];
        $totalTokens = $responseBody['total_tokens'];

         // Dynamically set the service_by attribute based on the selected AI model
        if (str_contains($model, 'claude')) {
            $service_by = 'anthropic';
        } else {
            $service_by = 'openai';
        }

        $chat = new Chat();
        $chat->user_message = $userMessage;
        $chat->ai_response = $aiResponse;
        $chat->chat_session_id = $chatSessionId;

        $chat->ai_model = $model;
        $chat->service_by = $service_by;
        $chat->prompt_tokens = $promptTokens;
        $chat->completion_tokens = $completionTokens;
        $chat->total_tokens = $totalTokens;

        $chat->save();
        
        // Return the AI response along with the session_id to ensure the frontend knows which session is active
        return response()->json(['ai_response' => $aiResponse, 'session_id' => $chatSessionId]);
    }

    private function getAIService($model)
    {
        // You might want to add logic to determine the service based on the model string
        if (str_contains($model, 'claude')) {
            return new AnthropicService();
        } else {
            return new OpenAIService();
        }
    }

    private function formatAIResponse($response)
{
    // First, format explicitly marked code blocks
    $response = preg_replace_callback('/```(.*?)\n(.*?)```/s', function ($matches) {
        $languageIdentifier = trim($matches[1]);
        $codeBlock = $matches[2];

        // Check if the first line is a language identifier and remove it
        if (preg_match('/^\w+$/', $languageIdentifier)) {
            return '<div><pre><code>' . htmlspecialchars($codeBlock, ENT_QUOTES, 'UTF-8') . '</code></pre><button class="copy-code-btn btn btn-sm btn-outline-secondary">Copy Code</button></div>';
        } else {
            // If it's not just a language identifier, include it back
            return '<div><pre><code>' . htmlspecialchars($languageIdentifier . "\n" . $codeBlock, ENT_QUOTES, 'UTF-8') . '</code></pre><button class="copy-code-btn btn btn-sm btn-outline-secondary">Copy Code</button></div>';
        }
    }, $response);

    // Handle unmarked code patterns
    $response = preg_replace_callback('/<\?(php)?(.*?)\?>/s', function ($matches) {
        $codeBlock = trim($matches[2]);
        return '<div><pre><code>' . htmlspecialchars($codeBlock, ENT_QUOTES, 'UTF-8') . '</code></pre><button class="copy-code-btn btn btn-sm btn-outline-secondary">Copy Code</button></div>';
    }, $response);

    // Now, handle Markdown formatting for the rest of the content
    $parsedown = new Parsedown();
    $response = $parsedown->text($response);

    return $response;
}

public function displayMarkdownAsHtml1(Request $request)
{   
    $selectedSessionId = 'b07ea763-7ca6-4b62-bd96-c1a9d3a50c2b';
    $selectedSessionId = '57ccb51e-3f6e-4d77-8b26-09c10b04076a';
    $chats = Chat::where('chat_session_id', $selectedSessionId)->get();
    
    // Simulating receiving the AI response as request input for demonstration
    $markdownText = $request->input('ai_response', $chats[0]->ai_response);

    $parsedown = new Parsedown();
    $htmlOutput = $parsedown->text($markdownText);

    // Convert URLs to clickable links
    $htmlOutput = $this->linkify($htmlOutput);

    return view('display', ['htmlContent' => $htmlOutput]);
}

public function displayMarkdownAsHtml(Request $request)
    {   
        $selectedSessionId = '57ccb51e-3f6e-4d77-8b26-09c10b04076a';
        $selectedSessionId = 'b07ea763-7ca6-4b62-bd96-c1a9d3a50c2z';
        $chats = Chat::where('chat_session_id', $selectedSessionId)->get();

        $parsedown = new Parsedown();
        $htmlOutput = '';

        foreach ($chats as $chat) {
            $chatObj = new stdClass();
            $chat->ai_response = $this->codeFormat($chat->ai_response);
            $chatObj->ai_response = $parsedown->text($chat->ai_response);  // Assuming 'ai_response' is the field you want to parse.
            $htmlOutput .= $chatObj->ai_response . "<br>";  // Concatenating each parsed response into one HTML string.
        }

        // Convert URLs to clickable links
        $htmlOutput = $this->linkify($htmlOutput);

        return view('display', ['htmlContent' => $htmlOutput]);
    }

    private function linkify($text)
    {
        $urlPattern = '/\bhttps?:\/\/[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))/';
        return preg_replace_callback($urlPattern, function ($matches) {
            return "<a href=\"{$matches[0]}\" target=\"_blank\" rel=\"noopener noreferrer\">{$matches[0]}</a>";
        }, $text);
    }

    private function codeFormat($response)
    {
        $response = preg_replace_callback('/<\?(php)?(.*?)\?>/s', function ($matches) {
            $codeBlock = trim($matches[2]);
            return htmlspecialchars($codeBlock, ENT_QUOTES, 'UTF-8');
        }, $response);
    
        return $response;
    }

    

    
}
