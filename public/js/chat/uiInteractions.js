function initUIInteractions() {
    // Additional UI interactions can be initialized here
}

function copyToClipboard(content) {
    var $temp = $("<textarea>");
    $("body").append($temp);
    $temp.val(content).select();
    document.execCommand("copy");
    $temp.remove();
}

function showCopyConfirmation($button) {
    var originalText = $button.text();
    $button.text('Copied!').addClass('btn-success').removeClass('btn-outline-secondary');
    setTimeout(function() {
        $button.text(originalText).removeClass('btn-success').addClass('btn-outline-secondary');
    }, 2000);
}

function deleteMessage(messageId, sessionInput, messageContainer) {
    $.ajax({
        url: '/chat/delete/' + messageId,
        type: 'POST',
        data: { session_id: sessionInput },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        success: function(response) {
            if (response.success) {
                messageContainer.remove();
                if ($('.chat-container .message-container').length === 0) {
                    $('.list-group a.active').remove();
                    $('#newChatButton').click();
                }
            } else {
                alert(response.message);
            }
        },
        error: function(xhr) {
            alert('Error: ' + xhr.responseText);
        }
    });
}
