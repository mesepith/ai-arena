$(document).ready(function() {
    initChat();

    // Event handler bindings
    bindChatEventHandlers();

    // Initialize session management
    initSessionManagement();

    // Initialize UI interactions
    initUIInteractions();
});

function initChat() {
    // scrollToBottom();
    // Initialization logic if needed
}

function bindChatEventHandlers() {
    $('.navbar-toggler').click(toggleNavbar);
    $('#modelSelection').change(saveSelectedModel);
    $('#uploadButton').click(triggerFileInput);
    $('#imageInput').change(handleFileInputChange);
    $('#userInput').on('input', autoResizeTextarea);
    $('#sendButton').click(sendMessage);
    $('#userInput').keydown(handleEnterPress);
    $(document).on('click', '.copy-btn', handleCopyButtonClick);
    $(document).on('click', '.copy-code-btn', handleCopyCodeButtonClick);
    $(document).on('click', '.delete-btn', handleDeleteButtonClick);
    $('.suggestion-btn').click(handleSuggestionButtonClick);
}

function sendMessage() {
    $('#suggestionBox').hide();

    var userInput = $('#userInput').val();
    var modelSelected = $('#modelSelection').val();
    var sessionInput = $('#sessionInput').val();
    $('#userInput').val('');

    var formData = new FormData();
    formData.append('message', userInput);
    formData.append('session_id', sessionInput);
    formData.append('model', modelSelected);

    uploadedFiles.forEach(function(file) {
        formData.append('uploaded_files[]', JSON.stringify(file));
    });

    $('.spinner-send-btn').show();
    $('.send-button-pnt').hide();

    $.ajax({
        url: '/chat',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        success: function(data) {
            appendMessage(data, userInput);

            var isNewSession = true;
            $('.list-group a').each(function() {
                if ($(this).attr('href').includes(data.session_id)) {
                    isNewSession = false;
                    return false;
                }
            });

            if (isNewSession) {
                var words = data.chat_title.split(' ');
                var newSessionLink = $('<a>')
                    .addClass('list-group-item list-group-item-action active')
                    .attr('href', '/chat?session_id=' + data.session_id);

                words.forEach(function(word, index) {
                    var wordSpan = $('<span>')
                        .addClass('word-animate')
                        .css('animation-delay', (index * 0.9) + 's')
                        .html(word + '&nbsp;');
                    newSessionLink.append(wordSpan);
                });

                newSessionLink.hide();
                $('.list-group').prepend(newSessionLink);
                newSessionLink.fadeIn();
            }

            $('#sessionInput').val(data.session_id);
            if (!window.location.href.includes(data.session_id)) {
                window.history.pushState({}, '', '/chat?session_id=' + data.session_id);
            }

            $('.spinner-send-btn').hide();
            $('.send-button-pnt').show();
            $('#imagePreviews').empty();
            uploadedFiles = [];
            imagePreviews = [];
        },
        error: function() {
            $('.spinner-send-btn').hide();
            $('.send-button-pnt').show();
        }
    });
}

function appendMessage(data, userInput) {
    aiTyping = true;
    var sessionInput = $('#sessionInput').val();

    var userMessageElement = $('<div>').addClass('message user-message');

    if (data.images && data.images.length > 0) {
        var imagesContainer = $('<div>').addClass('images-container');
        data.images.forEach(function(imageString) {
            var image = JSON.parse(imageString);
            var img = $('<img>').attr('src', image.file_domain + image.file_path).addClass('chat-image');
            imagesContainer.append(img);
        });
        userMessageElement.append(imagesContainer);
    }

    userMessageElement.append('<strong>User:</strong> ' + userInput);

    var userCopyBtn = $('<button>').addClass('copy-btn btn btn-sm btn-outline-secondary').attr('data-message', userInput).text('Copy');
    userMessageElement.append(userCopyBtn);
    $('#chatBox').append(userMessageElement);

    var aiMessageElement = $('<div>').addClass('message ai-message');
    $('#chatBox').append(aiMessageElement);

    var options = {
        strings: [data.ai_response],
        typeSpeed: 10,
        contentType: 'html',
        onComplete: function() {
            aiTyping = false;
            monitorScrolling = false;

            var aiCopyBtn = $('<button>').addClass('copy-btn btn btn-sm btn-outline-secondary')
                                         .attr('data-message', data.ai_response)
                                         .text('Copy');
            var deleteBtn = $('<button>').addClass('delete-btn btn btn-sm btn-outline-danger')
                                         .attr('data-id', data.message_id)
                                         .attr('data-session-id', sessionInput)
                                         .text('Delete');
            aiMessageElement.append(aiCopyBtn).append(deleteBtn);

            setTimeout(() => {
                updateScrollBehavior(false);
            }, 100);
        }
    };

    monitorScrolling = true;
    monitorUserScroll();
    new Typed(aiMessageElement[0], options);
}
