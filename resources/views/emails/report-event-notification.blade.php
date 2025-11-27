<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $subjectLine }}</title>
</head>
<body>
    <h2>{{ $subjectLine }}</h2>
    <p>{{ $introLine }}</p>

    @if(!empty($details))
        <ul>
            @foreach($details as $label => $value)
                <li><strong>{{ $label }}:</strong> {{ $value }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
