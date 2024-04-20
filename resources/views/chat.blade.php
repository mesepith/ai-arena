<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat Application</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/chat/chat.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
<div class="container-fluid">

    <div class="row margin-bottom-20">
        <!-- Model selection dropdown -->
        <div class="model-selection-prnt col-10 col-md-3">
            <div class="model-selection">
                <select id="modelSelection" class="form-control">
                    <option value="gpt-3.5-turbo">Open AI GPT-3.5-turbo</option>
                    <option value="gpt-4-turbo">Open AI GPT-4 Turbo</option>
                    <option value="claude-3-opus-20240229">Cloude 3 Opus</option>
                    <option value="gpt-4">Open AI GPT-4</option>
                    <option value="claude-3-sonnet-20240229">Cloude 3 Sonnet</option>
                    <option value="claude-3-haiku-20240307">Cloude 3 Haiku</option>
                </select>
            </div>
        </div>
        <!-- Burger menu for mobile -->

        <div class="col-1 d-md-none text-right">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon">
                    <i class="fas fa-bars"></i> <!-- Font Awesome Burger Icon -->
                    <i class="fas fa-times" style="display:none;"></i> <!-- Font Awesome Close Icon -->
                </span>
            </button>
        </div>
    </div>


    <div class="row flex-grow-1">

        <div class="col-md-3 sidebar collapse d-md-block" id="sidebarMenu">
            <!-- New Chat Button -->
            <button id="newChatButton" class="btn btn-success mt-3">Start New Chat</button>
            <div class="list-group">
                @foreach($sessions as $session)
                    <a href="/chat?session_id={{ $session->chat_session_id }}" class="list-group-item list-group-item-action{{ $selectedSessionId == $session->chat_session_id ? ' active' : '' }}">
                        {{ $session->title ?: 'New Session' }}
                    </a>
                @endforeach
            </div>

            
        </div>
        <div class="col-md-9 d-flex flex-column chat-container">
            <div class="chat-box" id="chatBox">
                @foreach($chats as $chat)
                    <div class="message user-message">
                        <strong>User:</strong> {{ $chat->user_message }}
                        <button class="copy-btn btn btn-sm btn-outline-secondary" data-message="{{ $chat->user_message }}">Copy</button>
                    </div>
                    <div class="message ai-message">
                        <strong>AI:</strong> {!! $chat->ai_response !!}
                        <button class="copy-btn btn btn-sm btn-outline-secondary" data-message="{{ $chat->ai_response }}">Copy</button>
                    </div>
                @endforeach
            </div>


                <!-- Input Group for Message Typing Area -->
                <div class="input-group mb-3">
                    <textarea class="form-control" id="userInput" placeholder="Type your message here..."></textarea>
                    <input type='hidden' id="sessionInput" value="{{$selectedSessionId}}">
                    <div class="input-group-append send-button-pnt">
                        <button id="sendButton" class="btn btn-primary" type="button">Send</button>
                    </div>
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>


        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.navbar-toggler').click(function() {
        $('.navbar-toggler-icon .fa-bars').toggle();
        $('.navbar-toggler-icon .fa-times').toggle();
    });
});
</script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="{{ asset('js/chat/chat.js') }}"></script>
</body>
</html>
