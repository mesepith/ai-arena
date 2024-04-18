$(document).ready(function() {
    
    $('#sendButton').click(function() {
        var userInput = $('#userInput').val();
        var modelSelected = $('#modelSelection').val(); // Get the selected model
        var sessionInput = $('#sessionInput').val(); // Get the session_id value
        $('#userInput').val(''); // Clear input field
        appendMessage1(userInput, 'user-message');
        

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
                appendAIMessage(data.ai_response);
                // Construct a meaningful title from the user's input and AI's response
                var userSnippet = userInput.length > 30 ? userInput.substring(0, 30) + '...' : userInput;
                var aiSnippet = data.ai_response.length > 30 ? data.ai_response.substring(0, 30) + '...' : data.ai_response;
                var sessionTitle = userSnippet + ' ... ' + aiSnippet;

                var isNewSession = true;
                $('.list-group a').each(function() {
                    if ($(this).attr('href').includes(data.session_id)) {
                        isNewSession = false;
                        // Update the title for an existing session
                        $(this).text(sessionTitle);
                        return false; // break the loop
                    }
                });

                // If it's a new session, add it to the list with the constructed title
                if (isNewSession) {
                    var newSessionLink = $('<a>')
                        .addClass('list-group-item list-group-item-action')
                        .attr('href', '/chat?session_id=' + data.session_id)
                        .text(sessionTitle); // Use the constructed title

                    $('.list-group').prepend(newSessionLink); // Add the new session link to the top of the list
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

 // Function to append AI messages with formatted code blocks and copy buttons
 function appendAIMessage(aiResponse) {
    // Split the response into segments of code blocks and other content
    let segments = aiResponse.split(/(```.*?```)/sg);
    
    // First, parse non-code segments as Markdown
    let nonCodeSegments = segments.map((segment, index) => {
        // Even indices will be non-code segments due to the way split works with capturing groups
        if (index % 2 === 0) {
            return marked.parse(segment);
        } else {
            return segment; // Leave code segments as-is for now
        }
    });

    // Then, process each segment, applying formatAIResponse only to code blocks
    let processedSegments = nonCodeSegments.map(segment => {
        if (segment.startsWith("```") && segment.endsWith("```")) {
            // Format code blocks now that non-code content has been parsed
            return formatAIResponse(segment);
        } else {
            return segment; // Non-code segments have already been parsed
        }
    });

    // Combine processed segments
    let formattedMessage = processedSegments.join('');
    // Decode HTML entities
    formattedMessage = decodeHtmlEntities(formattedMessage);

    // Create the message element and append it to the chat box
    let messageElement = $('<div>').addClass('message ai-message').html('<strong>AI:</strong> ' + formattedMessage);
    let copyBtn = $('<button>').addClass('copy-btn btn btn-sm btn-outline-secondary').text('Copy');
    messageElement.append(copyBtn);
    $('#chatBox').append(messageElement);
    $('#chatBox').scrollTop($('#chatBox')[0].scrollHeight);
}



function formatAIResponse(codeBlock) {
    // Extract the actual code from the block, removing the triple backticks
    var code = codeBlock.slice(3, -3); // Remove the triple backticks

    // Decode HTML entities within the code block
    code = code.replace(/&quot;/g, '"')
               .replace(/&lt;/g, '<')
               .replace(/&gt;/g, '>');

    // Wrap the decoded code in <pre><code> tags for proper formatting
    var codeHtml = '<pre><code>' + code.trim() + '</code></pre>';

    // Add a "Copy Code" button
    var copyCodeBtn = '<button class="copy-code-btn btn btn-sm btn-outline-secondary">Copy Code</button>';

    // Return the combined HTML for the code block and the "Copy Code" button
    return codeHtml + copyCodeBtn;
}

function decodeHtmlEntities(text) {
    var textArea = document.createElement('textarea');
    textArea.innerHTML = text;
    return textArea.value;
}


   // Utility function to escape HTML
   function escapeHTML(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
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

 // Event handler for the "Copy" button to copy the entire AI response
 $(document).on('click', '.copy-btn', function() {
    var message = $(this).parent().text();
    copyToClipboard(message);
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

});