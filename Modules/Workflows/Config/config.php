<?php

return [
    'name' => 'Workflows',
	'process_cron' => env('WORKFLOWS_PROCESS_CRON', '0 * * * *'),
	'user_full_name' => env('WORKFLOWS_USER_FULL_NAME', 'Workflow'),
];
