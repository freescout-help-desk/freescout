<form class="form-inline" action="{{ \Kb::route('knowledgebase.frontend.search', ['mailbox_id'=>\Kb::encodeMailboxId($mailbox->id)], $mailbox) }}">
	<div class="input-group input-group-lg margin-bottom">
		<input type="text" class="form-control" name="q" value="{{ request()->q }}">
			<span class="input-group-btn">
			<button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
		</span>
	</div>
</form>