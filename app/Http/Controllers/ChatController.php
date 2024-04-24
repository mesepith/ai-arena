<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatsTitle;
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

         // Fetch distinct chat session IDs along with the title from chats_title table
         $sessions = Chat::select('chats.chat_session_id', 'chats_title.title', 
         Chat::raw('MAX(chats.created_at) as last_message_time'))
        ->join('chats_title', 'chats.id', '=', 'chats_title.chat_id')
        ->where('chats.status', 1)
        ->groupBy('chats.chat_session_id', 'chats_title.title')
        ->orderBy('last_message_time', 'desc')
        ->get();

        // echo '<pre>'; print_r($sessions); exit;
         
    
        if (!$selectedSessionId) {
            
            // If no session_id is provided, start a new one
            $selectedSessionId = Str::uuid()->toString();
            session(['chat_session_id' => $selectedSessionId]);
            // Since this is a new session, there won't be any messages to display
            $chats = [];
        } else {
            
            // Fetch messages from the selected session
            $chats = Chat::where('chat_session_id', $selectedSessionId)->where('status', 1)->get();
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
        $conversationHistory = Chat::where('chat_session_id', $chatSessionId)->where('status', 1)->get()->map(function ($chat) {
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

        $isNewSession = !Chat::where('chat_session_id', $chatSessionId)->exists();

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

        $chat_title='';

        if ($isNewSession) {

            $titleResponse = $this->generateChatTitle($userMessage, 'gpt-3.5-turbo');
            $chat_title = $titleResponse['title'];
            // Save the title to the database
            $chatTitle = new ChatsTitle([
                'chat_id' => $chat->id,
                'chat_session_id' => $chatSessionId,
                'title' => $titleResponse['title'],
                // rest of the fields
            ]);
            $chatTitle->save();
    
            // Update the session list in the frontend
            // Logic to prepend to the session list
        }
        
        // Return the AI response along with the session_id to ensure the frontend knows which session is active
        return response()->json(['ai_response' => $aiResponse, 'session_id' => $chatSessionId, 'message_id' => $chat->id, 'chat_title' => $chat_title]);
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

    public function delete(Request $request, $id)
    {
        $chatSessionId = $request->input('session_id');
        $chat = Chat::where('id', $id)->where('chat_session_id', $chatSessionId)->first();

        if ($chat) {
            $chat->status = 0;
            $chat->save();

            // Check if there are no more active messages in this chat session
            $remainingChatsCount = Chat::where('chat_session_id', $chatSessionId)
                                        ->where('status', 1)
                                        ->count();
                                        
            if ($remainingChatsCount === 0) {
                // No more messages, so update the status of the chats_title as well
                $chatsTitle = ChatsTitle::where('chat_session_id', $chatSessionId)->first();
                if ($chatsTitle) {
                    $chatsTitle->status = 0;
                    $chatsTitle->save();
                }
            }

            return response()->json(['success' => true, 'message' => 'Message deleted successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'Message not found.'], 404);
    }

    private function generateChatTitle($userMessage, $model)
    {
        $modMsg = 'What will be title of this prompt :' . $userMessage . '. It should be under 35 character length';
    
        // Example pseudocode, replace with actual API call and response handling
        $conversation = [['role' => 'user', 'content' => $modMsg]];
        $responseBody = $this->openAIService->generateResponse($conversation, $model);
        $titleResponse = array();
        $titleResponse['title'] = trim($responseBody['ai_response'], '"');
    
        return $titleResponse;
    }
    
}
