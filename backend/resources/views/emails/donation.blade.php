<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;">
    <p>Hallo,</p>
    <p>
        <strong>{{ $donorName }}</strong> ({{ $donorEmail }}) möchte folgende Spiele verschenken:
    </p>
    <ul>
        @foreach ($games as $game)
            <li>{{ $game }}</li>
        @endforeach
    </ul>
    <p>Die Person hat bestätigt, dass alle Spiele vollständig und in einem annehmbaren Zustand sind.</p>
    @if (count($images))
        <p>{{ count($images) }} Bild(er) sind an diese Mail angehängt.</p>
    @endif
    <p>Antworte einfach direkt auf diese Mail, um dich mit {{ $donorName }} abzustimmen.</p>
</body>
</html>
