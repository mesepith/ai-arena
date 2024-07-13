function toggleNavbar() {
    $('.navbar-toggler-icon .fa-bars').toggle();
    $('.navbar-toggler-icon .fa-times').toggle();
}

function saveSelectedModel() {
    var selectedModel = $(this).val();
    localStorage.setItem('selectedModel', selectedModel);
}

function triggerFileInput() {
    $('#imageInput').trigger('click');
}

function handleFileInputChange() {
    var files = document.getElementById('imageInput').files;
    showUploadLoader();
    uploadFiles(files, function(uploadedFilesResponse) {
        uploadedFiles = uploadedFiles.concat(uploadedFilesResponse);
        displayImagePreviews(files);
        hideUploadLoader();
    });
}

function autoResizeTextarea() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight > 150 ? 150 : this.scrollHeight) + 'px';
}

function handleEnterPress(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        $('#sendButton').click();
    }
}

function handleCopyButtonClick() {
    var contentToCopy = $(this).closest('.message').clone();
    contentToCopy.find('strong').remove();
    contentToCopy.find('button').remove();
    var textToCopy = contentToCopy.text().trim();
    copyToClipboard(textToCopy);
    showCopyConfirmation($(this));
}

function handleCopyCodeButtonClick() {
    var codeContent = $(this).prev('pre').find('code').text();
    copyToClipboard(codeContent);
    showCopyConfirmation($(this));
}

function handleDeleteButtonClick() {
    var messageId = $(this).data('id');
    var sessionInput = $('#sessionInput').val();
    var messageContainer = $(this).closest('.message-container');
    if (confirm('Are you sure you want to delete this message?')) {
        deleteMessage(messageId, sessionInput, messageContainer);
    }
}

function handleSuggestionButtonClick() {
    var suggestionText = $(this).data('suggestion');
    $('#userInput').val(suggestionText);
    $('#sendButton').click();
}
