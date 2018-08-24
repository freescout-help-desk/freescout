{{ App\Mail\Mail::REPLY_SEPARATOR_TEXT }}

{{ (new Html2Text\Html2Text($auto_reply_message))->getText() }}