{{-- In resources/views/display.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Content</title>
    <style>
        /* Styling for code blocks */
        pre code {
            background-color: #f4f4f4; /* Light gray background */
            border: 1px solid #ccc; /* Adding a subtle border */
            display: block;
            padding: 10px;
            overflow-x: auto; /* Makes it scrollable horizontally */
            font-family: 'Courier New', Courier, monospace;
            color:inherit;
        }

        /* Styling for tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd; /* Light grey border for table cells */
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0; /* Slightly darker background for headers */
        }
        code {color: #e83e8c;}
    </style>
</head>
<body>
    {!! $htmlContent !!}
</body>
</html>
