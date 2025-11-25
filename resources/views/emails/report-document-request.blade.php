<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $emailTitle ?? 'Document Request' }}</title>
</head>
<body>
    {!! nl2br(e($emailBody)) !!}

    @if(!empty($documentRequestUrl))
        <p>
            <a href="{{ $documentRequestUrl }}">{{ $documentRequestUrl }}</a>
        </p>
    @endif
</body>
</html>
