@include('workflows::partials/email_form', [
	'exclude_fields' => [
		'to', 'cc', 'bcc', 'no_signature'
	],
	'is_note' => true
])