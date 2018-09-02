{{ __('Hello :user_name', ['user_name' => $user->getFirstName()]) }},

{!! __("This is a quick note to let you know that your :company_name password has been successfully updated. If you didn't request this change, please let us know by replying to this email.", ['company_name' => App\Option::getCompanyName()]) !!}