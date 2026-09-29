<?php

namespace Modules\Workflows\Services;

class ConditionCatalog
{
    /**
     * Built-in condition groups for one mailbox.
     *
     * Pass flags to avoid Module::isActive. A missing flag is false.
     * Null flags read tags and customfields from Module::isActive.
     *
     * @param int        $mailboxId
     * @param array|null $flags
     * @return array
     */
    public static function configured(int $mailboxId, ?array $flags = null): array
    {
        $text = self::textOperators();
        $equality = [
            'equal' => 'Is equal',
            'not_equal' => 'Is not equal',
        ];
        $age = [
            'in_the_last' => 'In the last',
            'not_in_the_last' => 'Not in the last',
        ];

        $config = [
            'customer' => [
                'title' => 'Customer',
                'items' => [
                    'customer_name' => self::item('Customer name', $text),
                    'customer_email' => self::item('Customer email', $text),
                ],
            ],
            'message' => [
                'title' => 'Message',
                'items' => [
                    'to' => self::item('To', $text),
                    'cc' => self::item('Cc', $text),
                    'subject' => self::item('Subject', $text),
                    'headers' => self::item('Headers', $text),
                    'body' => self::item('Body', $text),
                    'attachment' => self::item('Attachment', [
                        'contains' => 'Contains',
                        'not_contains' => 'Does not contain',
                    ]),
                ],
            ],
            'conversation' => [
                'title' => 'Conversation',
                'items' => [
                    'conversation_type' => self::item('Type', $equality),
                    'status' => self::item('Status', $equality),
                    'assignee' => self::item('Assignee', $equality),
                    'channel' => self::item('Channel', $equality),
                    'user_action' => self::item('User action', [
                        'replied' => 'Replied',
                        'added_note' => 'Added a note',
                    ]),
                    'new_reply_moved' => self::item('New, reply, or moved', [
                        'is' => 'Is',
                    ]),
                    'customer_viewed' => self::item('Customer viewed', [
                        'yes' => 'Yes',
                        'no' => 'No',
                    ]),
                ],
            ],
            'dates' => [
                'title' => 'Dates',
                'items' => [
                    'waiting_since' => self::item('Waiting since', $age),
                    'last_user_reply' => self::item('Last user reply', $age),
                    'last_customer_reply' => self::item('Last customer reply', $age),
                    'date_created' => self::item('Date created', $age),
                ],
            ],
        ];

        if (self::moduleEnabled($flags, 'tags')) {
            $config['tags'] = [
                'title' => 'Tags',
                'items' => [
                    'tag' => self::item('Tag', [
                        'contains' => 'Contains',
                        'not_contains' => 'Does not contain',
                        'equal' => 'Is equal',
                        'not_equal' => 'Is not equal',
                    ]),
                ],
            ];
        }

        if (self::moduleEnabled($flags, 'customfields')) {
            $config['custom_fields'] = [
                'title' => 'Custom fields',
                'items' => [
                    'custom_field' => self::item('Custom field', [
                        'equal' => 'Is equal',
                        'not_equal' => 'Is not equal',
                        'contains' => 'Contains',
                        'not_contains' => 'Does not contain',
                        'is_set' => 'Is set',
                        'is_not_set' => 'Is not set',
                    ]),
                ],
            ];
        }

        $config['mailbox_id'] = $mailboxId;

        return \Eventy::filter('workflows.conditions_config', $config, $mailboxId);
    }

    /**
     * @param array|null $flags
     * @param string     $alias
     * @return bool
     */
    private static function moduleEnabled(?array $flags, string $alias): bool
    {
        if ($flags === null) {
            return (bool) \Module::isActive($alias);
        }

        return (bool) ($flags[$alias] ?? false);
    }

    /**
     * @return array
     */
    private static function textOperators(): array
    {
        return [
            'contains' => 'Contains',
            'not_contains' => 'Does not contain',
            'equal' => 'Is equal',
            'not_equal' => 'Is not equal',
            'regex' => 'Matches regex',
        ];
    }

    /**
     * @param string $title
     * @param array  $operators
     * @return array
     */
    private static function item(string $title, array $operators): array
    {
        return [
            'title' => $title,
            'operators' => $operators,
            'values' => [],
        ];
    }
}
