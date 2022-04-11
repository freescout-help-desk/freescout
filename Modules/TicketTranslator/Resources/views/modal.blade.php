<div class="form-horizontal">

	<input type="hidden" class="input-translate-thread-id" value="{{ $thread->id }}"/>

	<div class="form-group">
	    <label class="col-sm-2 control-label">{{ __('From') }}</label>
	    <div class="col-sm-10">
			<select type="text" class="form-control input-sized-lg input-translate-from">
				<option value="auto">{{ __('Auto') }}</option>
		        @foreach ($languages as $lang_code => $lang_info)
		            <option value="{{ $lang_code }}">{{  $lang_info['name_en'] }}</option>
		        @endforeach
		    </select>
		</div>
	</div>

	<div class="form-group">
	    <label class="col-sm-2 control-label">{{ __('Into') }}</label>
	    <div class="col-sm-10">
			<select type="text" class="form-control input-sized-lg input-translate-into">
		        @foreach ($languages as $lang_code => $lang_info)
		            <option value="{{ $lang_code }}" @if (Auth::user()->getLocale() == $lang_code)) selected @endif>{{ $lang_info['name_en'] }}</option>
		        @endforeach
		    </select>
		</div>
	</div>

	<div class="form-group">
		<div class="col-sm-10 col-sm-offset-2">
			<button class="btn btn-primary" data-loading-text="{{ __('Translating') }}…" onclick="translateThread(this)">{{ __('Translate') }}</button>
		</div>
	</div>
</div>