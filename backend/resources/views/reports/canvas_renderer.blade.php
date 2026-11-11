<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111; }
        .canvas { width: 210mm; margin: 0 auto; padding: 12mm; box-sizing: border-box; }
    </style>
</head>
<body>
    <div class="canvas" id="report-canvas">
        @if(!empty($renderedHtml))
            {!! $renderedHtml !!}
        @else
            <pre>{{ json_encode($schema, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>
</body>
</html>
