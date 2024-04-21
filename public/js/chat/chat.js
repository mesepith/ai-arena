$(document).ready(function() {
    
    $('#sendButton').click(function() {
        var userInput = $('#userInput').val();
        var modelSelected = $('#modelSelection').val(); // Get the selected model
        var sessionInput = $('#sessionInput').val(); // Get the session_id value
        $('#userInput').val(''); // Clear input field
        // appendMessage(userInput, 'user-message');
        

        // Show the spinnersend-button-pnt
        $('.spinner-border').show();
        $('.send-button-pnt').hide();

        // Send AJAX request using jQuery
        $.ajax({
            url: '/chat',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify({
                message: userInput,
                session_id: sessionInput, // Use the session_id from the hidden input
                model: modelSelected // Send the selected model to the backend
            }),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json'
            },
            success: function(data) {
                
                //appendMessage(data.ai_response, 'ai-message'); 
                appendMessage(data, userInput); 

                var isNewSession = true;
                $('.list-group a').each(function() {
                    if ($(this).attr('href').includes(data.session_id)) {
                        console.log('old session');
                        isNewSession = false;
                        // Update the title for an existing session
                        // $(this).text(sessionTitle);
                        return false; // break the loop
                    }else{
                        console.log('new session');
                    }
                });

                // If it's a new session, add it to the list with the constructed title
                if (isNewSession) {
                    var words = data.chat_title.split(' '); // Split the title into words
                    var newSessionLink = $('<a>')
                        .addClass('list-group-item list-group-item-action active')
                        .attr('href', '/chat?session_id=' + data.session_id);
            
                    words.forEach(function(word, index) {
                        // Create a span for each word and add the animation class
                        var wordSpan = $('<span>')
                            .addClass('word-animate')
                            .css('animation-delay', (index * 0.9) + 's') // Delay each word
                            .html(word + '&nbsp;'); // Add a non-breaking space after the word
                        
                        // Append the word span to the link
                        newSessionLink.append(wordSpan);
                    });
            
                    newSessionLink.hide();
                    $('.list-group').prepend(newSessionLink);
                    newSessionLink.fadeIn();
                }

                // Update the hidden session input and the URL without reloading the page
                $('#sessionInput').val(data.session_id);
                if (!window.location.href.includes(data.session_id)) {
                    window.history.pushState({}, '', '/chat?session_id=' + data.session_id);
                }

                // Hide the spinner
                $('.spinner-border').hide();
                $('.send-button-pnt').show();
                },
                error: function() {
                    // Hide the spinner in case of error as well
                    $('.spinner-border').hide();
                    $('.send-button-pnt').show();
                }
        });

    });

    $('#userInput').keydown(function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#sendButton').click();
        }
    });

    function appendMessage1(message, className) {
        var messageElement = $('<div>').addClass('message ' + className).text(message);
        $('#chatBox').append(messageElement);
        $('#chatBox').scrollTop($('#chatBox')[0].scrollHeight);
    }

    function appendMessage(data, userInput) {

        var message = data.ai_response;
        var messageId = data.message_id;
        var sessionInput = $('#sessionInput').val(); // Get the session_id value

        var messageContainer = $('<div>').addClass('message-container');

        // Create a new div element for the user message
        var userMessageElement = $('<div>').addClass('message user-message');
        userMessageElement.html('<strong>User:</strong> ' + userInput);
        
         // Append a copy button for the user message
        var userCopyBtn = $('<button>')
        .addClass('copy-btn btn btn-sm btn-outline-secondary')
        .attr('data-message', userInput)
        .text('Copy');

        userMessageElement.append(userCopyBtn);

        // Create a new div element for the AI message
        var aiMessageElement = $('<div>').addClass('message ai-message');
        aiMessageElement.html('<strong>AI:</strong> ' + message);

        // Append a copy button for the AI message
        var aiCopyBtn = $('<button>')
        .addClass('copy-btn btn btn-sm btn-outline-secondary')
        .attr('data-message', message)
        .text('Copy');

        // Append a delete button for the AI message with the necessary data attributes
        var deleteBtn = $('<button>')
        .addClass('delete-btn btn btn-sm btn-outline-danger')
        .attr('data-id', messageId)
        .attr('data-session-id', sessionInput)
        .text('Delete');

        aiMessageElement.append(aiCopyBtn).append(deleteBtn);

        // Append both user and AI message elements to the message container
        messageContainer.append(userMessageElement).append(aiMessageElement);

        // Append the new message container to the chat box div
        $('#chatBox').append(messageContainer);
        
        // Scroll to the bottom of the chat box to show the new message
        $('#chatBox').scrollTop($('#chatBox')[0].scrollHeight);
    }
    
    

    $('#newChatButton').click(function() {
        // Generate a new session ID
        var newSessionId = generateUUID(); // Function to generate UUID (explained below)
        $('#sessionInput').val(newSessionId); // Update the hidden session ID input
        console.log('newSessionIdZ: ', newSessionId)

        // Clear the chat box
        $('#chatBox').empty();

        // Optional: Refresh the list of chat sessions
        // You might need to make an AJAX call to the server to get the updated list of sessions
        // and update the session links in the .list-group div

        // If you want to reflect the new session in the URL
        window.location.href = '/chat?session_id=' + newSessionId;
    });

// Event handler for the "Copy" button to copy the entire AI response without "AI:" and button texts, HTML tags
$(document).on('click', '.copy-btn', function() {
    // Clone the parent message element to work with a copy of the content
    var contentToCopy = $(this).closest('.message').clone();

    // Remove the "AI:" label and any button elements from the cloned content
    contentToCopy.find('strong').remove(); // This removes the "AI:" label
    contentToCopy.find('button').remove(); // This removes the buttons

    // Extract the text from the cleaned clone, which now excludes the "AI:" label and button texts
    var textToCopy = contentToCopy.text();

    // Use the extracted text for copying to the clipboard
    copyToClipboard(textToCopy.trim()); // Trim for any leading/trailing whitespace
    showCopyConfirmation($(this));
});




// Event handler for the "Copy Code" button to copy code block content
$(document).on('click', '.copy-code-btn', function() {
    var codeContent = $(this).prev('pre').find('code').text();
    copyToClipboard(codeContent);
    showCopyConfirmation($(this));
});

// Function to copy content to clipboard
function copyToClipboard(content) {
    var $temp = $("<textarea>");
    $("body").append($temp);
    $temp.val(content).select();
    document.execCommand("copy");
    $temp.remove();
}

// Function to show copy confirmation
function showCopyConfirmation($button) {
    var originalText = $button.text();
    $button.text('Copied!').addClass('btn-success').removeClass('btn-outline-secondary');
    setTimeout(function() {
        $button.text(originalText).removeClass('btn-success').addClass('btn-outline-secondary');
    }, 2000);
}

// Function to generate UUID (version 4)
function generateUUID() { // Public Domain/MIT
    var d = new Date().getTime(); //Timestamp
    var d2 = (performance && performance.now && (performance.now()*1000)) || 0; //Time in microseconds since page-load or 0 if unsupported
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16; //random number between 0 and 16
        if(d > 0){ //Use timestamp until depleted
            r = (d + r)%16 | 0;
            d = Math.floor(d/16);
        } else { //Use microseconds since page-load if supported
            r = (d2 + r)%16 | 0;
            d2 = Math.floor(d2/16);
        }
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}

// Scroll the chat to the bottom on initial page load.
scrollToBottom();

function scrollToBottom() {
    var chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Event handler for the "Delete" button to delete messages
$(document).on('click', '.delete-btn', function() {
    var messageId = $(this).data('id');
    var sessionInput = $('#sessionInput').val(); // Get the session_id value
    var messageContainer = $(this).closest('.message-container'); // Assuming each chat has a parent container with class 'message-container'

    if (confirm('Are you sure you want to delete this message?')) {
        $.ajax({
            url: '/chat/delete/' + messageId,
            type: 'POST',
            data: {
                session_id: sessionInput // Pass the session_id along with the request
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function(response) {
                if (response.success) {
                    messageContainer.remove(); // Remove the entire message container from the chat
                    // Check if there are no messages left in this session and remove the session title
                    if ($('.chat-container .message-container').length === 0) {
                        // Remove the session from the sidebar
                        $('.list-group a.active').remove();
                        // Optionally, you could also reset the chat interface or prompt the user to start a new chat
                    }
                } else {
                    alert(response.message); // Show error message
                }
            },
            error: function(xhr) {
                alert('Error: ' + xhr.responseText);
            }
        });
    }
});


});