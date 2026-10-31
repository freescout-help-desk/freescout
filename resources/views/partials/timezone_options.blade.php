@php
    // Calculates a list of all time zones known to PHP with their current offsets.
    $tz_build = function ($names) {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $list = [];
        foreach ($names as $name) {
            $seconds = (new \DateTimeZone($name))->getOffset($now);
            $list[$name] = [
                'title'  => str_replace('_', ' ', $name),
                'offset' => ($seconds < 0 ? '-' : '+').sprintf('%02d:%02d', (int)(abs($seconds) / 3600), (int)(abs($seconds) % 3600 / 60)),
            ];
        }
        return $list;
    };

    // Offsets are calculated for the current moment, so they are correct during
    // summer and winter time. The list is cached for one day (1440 minutes).
    // The key includes the version of the time zone database, so the cache
    // is refreshed after PHP's time zone data is updated.
    try {
        $tz_list = \Cache::remember('timezone_options_'.timezone_version_get(), 1440, function () use ($tz_build) {
            return $tz_build(\DateTimeZone::listIdentifiers());
        });
    } catch (\Exception $e) {
        // Cache may be unavailable (for example, in the installer).
        $tz_list = $tz_build(\DateTimeZone::listIdentifiers());
    }

    // A saved zone which is not in the list (an old alias like Australia/West
    // or a zone from the previous hand-written list) must stay selectable.
    if (!empty($current_timezone) && !isset($tz_list[$current_timezone])) {
        try {
            $tz_list = array_merge($tz_list, $tz_build([$current_timezone]));
            ksort($tz_list);
        } catch (\Exception $e) {
            // Unknown zone, ignore.
        }
    }
@endphp
@foreach ($tz_list as $tz_name => $tz_data)
    <option value="{{ $tz_name }}" data-name="{{ $tz_data['title'] }}" data-offset="{{ $tz_data['offset'] }}" @if ($current_timezone == $tz_name)selected="selected"@endif>{{ $tz_data['title'] }} ({{ $tz_data['offset'] }})</option>
@endforeach
