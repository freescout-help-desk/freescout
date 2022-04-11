<select name="settings[officehours.schedule][{{ $day }}][0][from]" class="form-control">
    <option value=""></option>
    <option value="off" @if (!empty($settings['officehours.schedule'][$day][0]['from']) && $settings['officehours.schedule'][$day][0]['from'] == 'off') selected @endif>{{ __('Day off') }}</option>
    @for($i = 0; $i < 24; $i++)
    	@php
    		$value = $i.':00';
    	@endphp
    	<option value="{{ $value }}" @if (!empty($settings['officehours.schedule'][$day][0]['from']) && $settings['officehours.schedule'][$day][0]['from'] == $value) selected @endif >{{ $value }}</option>
    @endfor
</select> 
&nbsp;–&nbsp; 
<select name="settings[officehours.schedule][{{ $day }}][0][to]" class="form-control">
    <option value=""></option>
    <option value="off" @if (!empty($settings['officehours.schedule'][$day][0]['to']) && $settings['officehours.schedule'][$day][0]['to'] == 'off') selected @endif>{{ __('Day off') }}</option>
    @for($i = 0; $i < 24; $i++)
    	@php
    		$value = $i.':00';
    	@endphp
    	<option value="{{ $value }}" @if (!empty($settings['officehours.schedule'][$day][0]['to']) && $settings['officehours.schedule'][$day][0]['to'] == $value) selected @endif >{{ $value }}</option>
    @endfor
</select> 