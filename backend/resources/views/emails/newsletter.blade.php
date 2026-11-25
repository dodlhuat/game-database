<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;">
    {{-- nl2br already turns line breaks into <br>; pre-line on top doubled every one --}}
    <div>{!! nl2br(e($body)) !!}</div>
</body>
</html>
