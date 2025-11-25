<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $emailTitle ?? 'Document Request' }}</title>
</head>
<body>
    <p>Dear Partner,</p>

    <p>{!! nl2br(e($emailBody)) !!}</p>

    @if(!empty($requestedDocuments))
        <p>The following documents are requested:</p>
        <ul>
            @foreach($requestedDocuments as $document)
                <li>{{ $document }}</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($otherDocumentNote))
        <p><strong>Additional notes:</strong> {{ $otherDocumentNote }}</p>
    @endif

    <p>
        <strong>Report details</strong><br>
        Damage ID: {{ $report->damage_id ?? 'Not provided' }}<br>
        Building: {{ $report->building->name ?? 'N/A' }}<br>
        Report Reference: {{ $report->uuid }}
    </p>

    <p>Thank you,<br>The DAMArisk Team</p>
</body>
</html>
