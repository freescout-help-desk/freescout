<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .meta { color: #777; margin-bottom: 14px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f2f2f2; }
        .cards td { border: none; padding: 2px 8px 2px 0; }
        .cards .value { font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        {{ __('Range') }}: {{ $filters->from->format('Y-m-d') }} — {{ $filters->to->format('Y-m-d') }}
        · {{ __('Generated') }}: {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}
    </div>

    @if (!empty($data['cards']))
        <table class="cards">
            @foreach (array_chunk($data['cards'], 2) as $pair)
                <tr>
                    @foreach ($pair as $card)
                        <td>{{ $card['label'] }}:</td>
                        <td class="value">{{ $card['value'] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif

    @foreach ($data['sections'] as $section)
        <h2>{{ $section['title'] }}</h2>
        <table>
            <thead>
                <tr>
                    @foreach ($section['headers'] as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($section['rows'] as $row)
                    <tr>
                        @foreach (array_slice($row, 0, count($section['headers'])) as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($section['headers']) }}">{{ __('No data for the selected filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
