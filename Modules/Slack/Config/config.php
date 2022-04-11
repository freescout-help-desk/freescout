<?php

return [
    'name' => 'Slack',
    'options' => [
        'events' => [
        	'default' => [
        		'conversation.created',
        		'conversation.assigned',
        		'conversation.customer_replied',
        		'conversation.user_replied',
        	]
        ],
    ],
];
