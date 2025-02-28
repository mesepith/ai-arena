@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/chat/chat.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://unpkg.com/typed.js@2.1.0/dist/typed.umd.js"></script>
<script src="{{ asset('js/chat/chat.js') }}"></script>
<script src="{{ asset('js/chat/chatHelpers.js') }}"></script>
<script src="{{ asset('js/chat/fileUpload.js') }}"></script>
<script src="{{ asset('js/chat/scrollBehavior.js') }}"></script>
<script src="{{ asset('js/chat/sessionManagement.js') }}"></script>
<script src="{{ asset('js/chat/uiInteractions.js') }}"></script>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row margin-bottom-20">
        <!-- Model selection dropdown -->
        <div class="model-selection-prnt col-10 col-md-3">
            <div class="model-selection">
                <select id="modelSelection" class="form-control">
                    <option value="gpt-4o-mini">Open AI GPT-4o Mini</option>
                    <option value="o1-preview">Open AI o1 Preview</option>
                    <option value="o1-mini">Open AI o1 Mini</option>
                    <option value="gpt-4o">Open AI GPT-4o</option>
                    <option value="gpt-4.5-preview">Open AI GPT 4.5 Preview</option>
                    <option value="claude-3-7-sonnet-20250219">Cloude 3.7 Sonnet</option>
                    <option value="claude-3-5-sonnet-20241022">Cloude 3.5 Sonnet</option>
                    <option value="claude-3-5-haiku-20241022">Cloude 3.5 Haiku</option>
                    <option value="gpt-4-turbo">Open AI GPT-4 Turbo</option>
                    <option value="gpt-3.5-turbo">Open AI GPT-3.5-turbo</option>
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
                @if(count($chats) === 0)
                    <!-- Suggestions Box -->
                    <div id="suggestionBox" class="suggestion-box">
                        <p>How can I help you today?</p>
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <button class="suggestion-btn" data-suggestion="How can I improve my website's performance?">How can I improve my website's performance?</button>
                            </div>
                            <div class="col-12 col-md-6">
                                <button class="suggestion-btn" data-suggestion="Can you help me learn a new programming language?">Can you help me learn a new programming language?</button>
                            </div>
                            <div class="col-12 col-md-6">
                                <button class="suggestion-btn" data-suggestion="How do I organize my daily schedule?">How do I organize my daily schedule?</button>
                            </div>
                            <div class="col-12 col-md-6">
                                <button class="suggestion-btn" data-suggestion="Can you draft a professional email for me?">Can you draft a professional email for me?</button>
                            </div>
                        </div>
                    </div>
                @else
                    @foreach($chats as $chat)
                        <div class="message-container">
                            <div class="message user-message">
                                @if($chat->has_image)
                                    <div class="images-container">
                                        @foreach($chat->images as $image)
                                            <img src="{{ $image->image_location }}" alt="User Image" class="chat-image">
                                        @endforeach
                                    </div>
                                @endif
                                <strong>User:</strong> {{ $chat->user_message }}
                                <button class="copy-btn btn btn-sm btn-outline-secondary" data-message="{{ $chat->user_message }}">Copy</button>
                            </div>
                            <div class="message ai-message">
                                <strong>AI:</strong> {!! $chat->ai_response !!}
                                <button class="copy-btn btn btn-sm btn-outline-secondary" data-message="{{ $chat->ai_response }}">Copy</button>
                                <button class="delete-btn btn btn-sm btn-outline-danger" data-id="{{ $chat->id }}" data-session-id="{{ $selectedSessionId }}">Delete</button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <!-- Input Group for Message Typing Area -->
            <div id="imagePreviews" class="mb-3"></div>
            <div class="input-chat-arena input-group mb-3">
                <textarea class="form-control user-text-inp" id="userInput" placeholder="Type your message here..."></textarea>
                <!-- Upload Icon Button -->
                <div class="input-group-prepend">
                    <button class="btn btn-outline-secondary" type="button" id="uploadButton">
                        <i class="fas fa-upload"></i>
                    </button>
                    <div id="uploadLoader" class="spinner-border text-primary" role="status" style="display: none;">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <input type="file" id="imageInput" hidden multiple accept="image/*,.pdf">
                <input type='hidden' id="sessionInput" value="{{$selectedSessionId}}">
                <div class="input-group-append send-button-pnt">
                    <button id="sendButton" class="btn btn-primary" type="button">Send</button>
                </div>
                <div class="spinner-border spinner-send-btn text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
