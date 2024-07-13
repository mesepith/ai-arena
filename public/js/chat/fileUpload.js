var uploadedFiles = [];
var imagePreviews = [];

function uploadFiles(files, callback) {
    var uploadedFiles = [];
    var uploadCount = 0;

    for (var i = 0; i < files.length; i++) {
        var formData = new FormData();
        formData.append('file', files[i]);

        $.ajax({
            url: 'https://filebox.zahiralam.com/api/upload',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                uploadedFiles.push(response.file);
                uploadCount++;
                if (uploadCount === files.length) {
                    callback(uploadedFiles);
                }
            },
            error: function() {
                uploadCount++;
                if (uploadCount === files.length) {
                    callback(uploadedFiles);
                }
            }
        });
    }
}

function displayImagePreviews(files) {
    var previewContainer = $('#imagePreviews');

    for (var i = 0; i < files.length; i++) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var imgWrapper = $('<div>').css({
                display: 'inline-block',
                position: 'relative',
                margin: '5px'
            });

            var img = $('<img>').attr('src', e.target.result).css({
                width: '50px',
                height: '50px',
            });

            var removeBtn = $('<span>').html('&times;').css({
                position: 'absolute',
                top: '0',
                right: '0',
                cursor: 'pointer',
                background: 'red',
                color: 'white',
                borderRadius: '50%',
                padding: '2px'
            }).click(function() {
                var index = imagePreviews.indexOf(imgWrapper);
                if (index > -1) {
                    imagePreviews.splice(index, 1);
                    uploadedFiles.splice(index, 1);
                    imgWrapper.remove();
                }
            });

            imgWrapper.append(img).append(removeBtn);
            previewContainer.append(imgWrapper);
            imagePreviews.push(imgWrapper);
        };
        reader.readAsDataURL(files[i]);
    }
}

function showUploadLoader() {
    $('#uploadButton').hide();
    $('#uploadLoader').show();
}

function hideUploadLoader() {
    $('#uploadLoader').hide();
    $('#uploadButton').show();
}
