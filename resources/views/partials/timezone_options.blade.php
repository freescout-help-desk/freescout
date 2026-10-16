@php
    $tz_common = [
        'Pacific/Samoa' => 'Midway Island, Samoa',
        'Pacific/Honolulu' => 'Hawaiian/Aleutian Time',
        'America/Anchorage' => 'Alaska Time',
        'America/Vancouver' => 'Pacific Time (Vancouver)',
        'America/Los_Angeles' => 'Pacific Time (US)',
        'America/Phoenix' => 'Mountain Time (Arizona)',
        'America/Denver' => 'Mountain Time (US)',
        'America/Chicago' => 'Central Time (US)',
        'America/New_York' => 'Eastern Time (US)',
        '--SEPARATOR1--' => null,
        'America/Indiana/Knox' => 'Central Time (Indiana)',
        'America/Indiana/Indianapolis' => 'Eastern Time (Indiana)',
        '--SEPARATOR2--' => null,
        'America/Regina' => 'Central Time (Saskatchewan)',
        'America/Monterrey' => 'Central Time (Mexico City, Monterey)',
        'America/Lima' => 'UTC/GMT -5 hours',
        'America/Caracas' => 'Caracas, La Paz',
        'America/Manaus' => 'Atlantic Time',
        'America/Puerto_Rico' => 'Atlantic Time (Puerto Rico)',
        'America/Santiago' => 'Atlantic Time (Santiago)',
        'Canada/Newfoundland' => 'Newfoundland',
        'America/Thule' => 'Western Greenland Time',
        'America/Sao_Paulo' => 'Eastern Brazil',
        'America/St_Johns' => 'Newfoundland Time',
        'America/Godthab' => 'Central Greenland Time',
        'Etc/GMT+2' => 'GMT-2:00',
        'America/Scoresbysund' => 'Eastern Greenland Time',
        'Atlantic/Reykjavik' => 'Western European Time (Iceland)',
        'UTC' => 'UTC',
        'Europe/London' => 'British Time (London)',
        'Etc/GMT-1' => 'GMT+1:00',
        'Europe/Lisbon' => 'Western European Time (Lisbon)',
        'Europe/Paris' => 'Western European Time',
        'Europe/Berlin' => 'Central European Time',
        'Europe/Bucharest' => 'Eastern European Time',
        'Europe/Kyiv' => 'Kyiv',
        'Africa/Johannesburg' => 'South Africa Standard Time',
        'Africa/Kampala' => 'Eastern Africa Time',
        'Etc/GMT-3' => 'Moscow',
        'Asia/Tehran' => 'Iran Standard Time',
        'Asia/Dubai' => 'UAE (Dubai)',
        'Asia/Kabul' => 'Kabul',
        'Asia/Karachi' => 'Pakistan Standard Time (Karachi)',
        'Asia/Colombo' => 'Sri Jayawardenepura, Colombo',
        'Asia/Kolkata' => 'India',
        'Asia/Kathmandu' => 'Kathmandu',
        'Asia/Dhaka' => 'Bangladesh Standard Time',
        'Asia/Yangon' => 'Yangon, Rangoon',
        'Asia/Jakarta' => 'Western Indonesian Time (Jakarta)',
        'Asia/Bangkok' => 'Thailand (Bangkok)',
        'Asia/Hong_Kong' => 'Hong Kong',
        'Asia/Singapore' => 'Singapore',
        'Australia/West' => 'Australian Western Time',
        'Asia/Tokyo' => 'Tokyo',
        'Australia/North' => 'Australian Central Time (Northern Territory)',
        'Australia/Adelaide' => 'Australian Central Time (Adelaide)',
        'Australia/Sydney' => 'Australian Eastern Time (Sydney)',
        'Pacific/Noumea' => 'Noumea, New Caledonia',
        'Pacific/Norfolk' => 'Norfolk Island (Austl.)',
        'Pacific/Tarawa' => 'Tarawa',
        'Pacific/Auckland' => 'New Zealand Time',
    ];
    $tz_info = function ($timezone) {
        try {
            $zone = new \DateTimeZone($timezone);
        } catch (\Exception $e) {
            return ['', '', 0];
        }
        $offsets = array_unique([(new \DateTime(date('Y').'-01-01', $zone))->getOffset(), (new \DateTime(date('Y').'-07-01', $zone))->getOffset()]);
        sort($offsets);
        $changes = [];
        foreach (array_slice($zone->getTransitions(time(), time() + 400 * 86400), 1, 2) as $transition) {
            $changes[] = $transition['ts'].'|'.(int)$transition['isdst'];
        }

        return [implode('/', array_map(function ($seconds) {
            return ($seconds < 0 ? '-' : '+').gmdate('H:i', abs($seconds));
        }, $offsets)), implode(';', $changes), $offsets[0]];
    };
    $tz_all = [];
    foreach (\DateTimeZone::listIdentifiers() as $tz_id) {
        $tz_all[$tz_id] = $tz_info($tz_id);
    }
    uasort($tz_all, function ($a, $b) {
        return $a[2] <=> $b[2];
    });
@endphp
@if ($current_timezone && !isset($tz_common[$current_timezone]) && !isset($tz_all[$current_timezone]))
    @php $tz = $tz_info($current_timezone) @endphp
    <option value="{{ $current_timezone }}" data-tz-changes="{{ $tz[1] }}" selected="selected">(GMT{{ $tz[0] }}) {{ $current_timezone }}</option>
@endif
@foreach ($tz_common as $tz_id => $tz_name)
    @if ($tz_name)
        @php $tz = $tz_all[$tz_id] ?? $tz_info($tz_id) @endphp
        <option value="{{ $tz_id }}" data-tz-changes="{{ $tz[1] }}" @if ($current_timezone == $tz_id)selected="selected"@endif>(GMT{{ $tz[0] }}) {{ $tz_name }}</option>
    @else
        <option value="{{ $tz_id }}" disabled="disabled">-------------</option>
    @endif
@endforeach
<optgroup label="{{ __('All time zones') }}">
    @foreach ($tz_all as $tz_id => $tz)
        <option value="{{ $tz_id }}" data-tz-changes="{{ $tz[1] }}" @if ($current_timezone == $tz_id && !isset($tz_common[$tz_id]))selected="selected"@endif>(GMT{{ $tz[0] }}) {{ str_replace(['/', '_'], [' / ', ' '], $tz_id) }}</option>
    @endforeach
</optgroup>
