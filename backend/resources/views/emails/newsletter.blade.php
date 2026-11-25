<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;">
    {{-- nl2br already turns line breaks into <br>; pre-line on top doubled every one --}}
    <div>{!! nl2br(e($body)) !!}</div>
    <p style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #ddd; font-size: 12px; color: #777;">
        Du bekommst diese Mail, weil du den Newsletter von AUA-Alle.Unsere.Abenteuer abonniert hast.
        <a href="{{ $unsubscribeUrl }}" style="color: #777;">Vom Newsletter abmelden</a>
    </p>
</body>
</html>
