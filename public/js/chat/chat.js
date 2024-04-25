$(document).ready(function() {

    var chatBox = $('#chatBox'); // Ensure this is inside document ready to fetch the correct element
    var monitorScrolling = false;
    var aiTyping = false;  // This flag should be true when AI starts typing and false when AI response is completed.
    
    $('#sendButton').click(function() {

        var userInput = $('#userInput').val();
        var modelSelected = $('#modelSelection').val(); // Get the selected model
        var sessionInput = $('#sessionInput').val(); // Get the session_id value
        $('#userInput').val(''); // Clear input field
        // appendMessage(userInput, 'user-message');

        var formData = new FormData();
        formData.append('message', userInput);
        formData.append('session_id', sessionInput);
        formData.append('model', modelSelected);

        var images = document.getElementById('imageInput').files;
        for (var i = 0; i < images.length; i++) {
            formData.append('images[]', images[i]);
        }
        

        // Show the spinnersend-button-pnt
        $('.spinner-border').show();
        $('.send-button-pnt').hide();

        // Send AJAX request using jQuery
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
        aiTyping = true;  // AI starts typing
        var sessionInput = $('#sessionInput').val(); 

        // Append user message with 'Copy' button
        var userMessageElement = $('<div>').addClass('message user-message').html('<strong>User:</strong> ' + userInput);
        var userCopyBtn = $('<button>').addClass('copy-btn btn btn-sm btn-outline-secondary').attr('data-message', userInput).text('Copy');
        userMessageElement.append(userCopyBtn);
        chatBox.append(userMessageElement);

        var aiMessageElement = $('<div>').addClass('message ai-message');
        chatBox.append(aiMessageElement);

        var options = {
            strings: [data.ai_response],
            typeSpeed: 10,
            contentType: 'html',  // Assuming 'data.ai_response' could include HTML
            onComplete: function(self) {
                aiTyping = false;  // AI finishes typing
                monitorScrolling = false; // Disable monitoring as AI finishes typing.
            
                var aiCopyBtn = $('<button>').addClass('copy-btn btn btn-sm btn-outline-secondary')
                                             .attr('data-message', data.ai_response)
                                             .text('Copy');
                var deleteBtn = $('<button>').addClass('delete-btn btn btn-sm btn-outline-danger')
                                             .attr('data-id', data.message_id)
                                             .attr('data-session-id', sessionInput)
                                             .text('Delete');
            
                // Append buttons and deactivate unnecessary scrolling
                aiMessageElement.append(aiCopyBtn).append(deleteBtn);
            
                setTimeout(() => { // Short delay to let potential user scroll adjustments settle.
                    updateScrollBehavior(false); // Update scroll monitoring behavior
                }, 100);
            }
        };

        monitorScrolling = true; // Begin monitoring scrolling during typing
        monitorUserScroll(); 
        new Typed(aiMessageElement[0], options);
    }
    
    // Variable to store user's scroll position
    var userScrollPos = 0;

    function updateScrollBehavior(engage) {
        monitorScrolling = engage;
        if (engage) {
            monitorUserScroll();
        }
    }
// Adjust the scroll sensitivity to allow smoother scrolling

// Variable to store the previous scroll position
var prevScrollPos = 0;

chatBox.on('scroll', function() {
    var currentScrollPos = $(this).scrollTop();

    if (currentScrollPos < prevScrollPos) {
        // User is scrolling up
        if (aiTyping) { // Allow scrolling up during AI typing
            monitorScrolling = false; // Disable auto-scroll during user interaction
        }
    } else {
        // User is scrolling down or not scrolling
        if (aiTyping) { // Re-enable auto-scroll if AI is typing
            monitorScrolling = true;
            monitorUserScroll();
        }
    }

    // Update the previous scroll position for comparison
    prevScrollPos = currentScrollPos;
});

function monitorUserScroll() {
    if (monitorScrolling) {
        var scrollPosition = chatBox.scrollTop() + chatBox.innerHeight();
        var nearBottom = scrollPosition >= chatBox[0].scrollHeight - 100;

        if (nearBottom) {
            chatBox.scrollTop(chatBox.prop("scrollHeight"));
        }

        setTimeout(monitorUserScroll, 300); // Check scroll position repeatedly during typing
    }
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
    chatBox.scrollTop(chatBox.prop("scrollHeight"));
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

                        //load new session
                        $('#newChatButton').click();
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