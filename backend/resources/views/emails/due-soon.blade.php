<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;">
    <p>Hallo {{ $loan->user->name }},</p>
    <p>
        dein ausgeliehenes Spiel <strong>{{ $loan->copy->game->title }}</strong>
        ist am <strong>{{ $loan->due_date->format('d.m.Y') }}</strong> zurückzugeben.
    </p>
    <p>Bitte bringe es rechtzeitig zurück oder beantrage eine Verlängerung in deinem Dashboard.</p>
</body>
</html>
