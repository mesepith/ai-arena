<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Editor</title>
    <!-- Quill's Stylesheet -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <!-- Highlight.js Stylesheet -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.4.0/styles/default.min.css">
</head>
<body>
    <h1>AI Document Editor</h1>
    <!-- Editor Container for Quill -->
    <div id="editor" style="height: 300px;"></div>

    <!-- Script Includes -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.4.0/highlight.min.js"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: false // Disable toolbar if not needed
            }
        });

        // Example content with mixed regular text and code
        var regularText = 'Here is some regular text explaining the code below:';
        var codeSnippet = 'const greeting = \'Hello, world!\';\nconsole.log(greeting);';

        // Insert regular text
        quill.insertText(0, regularText + '\n\n', { bold: false });

        // Insert code snippet with formatting
        quill.insertText(quill.getLength(), codeSnippet, {
            'code-block': true,
            'color': '#24292e' // GitHub-like monospace font color
        });

        // Apply syntax highlighting
        document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('pre code').forEach((el) => {
                hljs.highlightElement(el);
            });
        });
    </script>
</body>
</html>
