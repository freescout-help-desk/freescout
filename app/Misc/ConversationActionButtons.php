<?php

namespace App\Misc;

class ConversationActionButtons
{
    // Location constants
    const LOCATION_TOOLBAR = 'toolbar';
    const LOCATION_DROPDOWN = 'dropdown';
    const LOCATION_BOTH = 'both';

    /**
     * Get all available conversation actions
     */
    public static function getActions($conversation, $user, $mailbox)
    {
            $actions = [
                    // Default toolbar actions
                    'reply'         => [
                            'icon'           => 'glyphicon-share-alt',
                            'location'       => self::LOCATION_TOOLBAR,
                            'label'          => __('Reply'),
                            'permission'     => function ($conversation) {
                                    return ( ! $conversation->isPhone() || ( $conversation->customer && $conversation->customer->getMainEmail() ) )
                                           && \Eventy::filter('conversation.reply_button.enabled', true, $conversation);
                            },
                            'class'          => 'conv-reply',
                            'fixed_location' => true,
                    ],
                    'note'          => [
                            'icon'           => 'glyphicon-edit',
                            'location'       => self::LOCATION_TOOLBAR,
                            'label'          => __('Note'),
                            'permission'     => function ($conversation) {
                                    return \Eventy::filter('conversation.note_button.enabled', true, $conversation);
                            },
                            'class'          => 'conv-add-note',
                            'fixed_location' => true,
                    ],
                    'delete'        => [
                            'icon'           => 'glyphicon-trash',
                            'location'       => self::LOCATION_TOOLBAR,
                            'label'          => $conversation->state != \App\Conversation::STATE_DELETED ? __('Delete') : __('Delete Forever'),
                            'permission'     => function ($conversation, $user) {
                                    return $user->can('delete', $conversation);
                            },
                            'class'          => $conversation->state != \App\Conversation::STATE_DELETED ? 'conv-delete' : 'conv-delete-forever',
                            'fixed_location' => true,
                    ],
                    'delete_mobile' => [
                            'icon'           => 'glyphicon-trash',
                            'location'       => self::LOCATION_DROPDOWN,
                            'label'          => $conversation->state != \App\Conversation::STATE_DELETED ? __('Delete') : __('Delete Forever'),
                            'permission'     => function ($conversation, $user) {
                                    return $user->can('delete', $conversation);
                            },
                            'class'          => $conversation->state != \App\Conversation::STATE_DELETED ? 'conv-delete' : 'conv-delete-forever',
                            'fixed_location' => true,
                            'mobile_only'    => true,
                    ],

                    // Default dropdown actions
                    'follow'        => [
                            'icon'           => 'glyphicon-bell',
                            'location'       => self::LOCATION_DROPDOWN,
                            'label'          => __('Follow'),
                            'permission'     => function () {
                                    return true;
                            },
                            'class'          => 'conv-follow',
                            'has_opposite'   => true,
                            'opposite'       => [
                                    'label' => __('Unfollow'),
                                    'class' => 'conv-follow',
                            ],
                            'fixed_location' => true,
                    ],
                    'forward'       => [
                            'icon'           => 'glyphicon-arrow-right',
                            'location'       => self::LOCATION_DROPDOWN,
                            'label'          => __('Forward'),
                            'permission'     => function () {
                                    return true;
                            },
                            'class'          => 'conv-forward',
                            'fixed_location' => true,
                    ],
                    'merge'         => [
                            'icon'           => 'glyphicon-indent-left',
                            'location'       => self::LOCATION_DROPDOWN,
                            'label'          => __('Merge'),
                            'permission'     => function ($conversation) {
                                    return ! $conversation->isChat();
                            },
                            'class'          => '',
                            'url'            => function ($conversation) {
                                    return route('conversations.ajax_html', array_merge([ 'action' => 'merge_conv' ], \Request::all(), [ 'conversation_id' => $conversation->id ]));
                            },
                            'attrs'          => [
                                    'data-trigger'         => 'modal',
                                    'data-modal-title'     => __('Merge Conversations'),
                                    'data-modal-no-footer' => 'true',
                                    'data-modal-on-show'   => 'initMergeConv',
                            ],
                            'fixed_location' => true,
                    ],
                    'move'          => [
                            'icon'           => 'glyphicon-log-out',
                            'location'       => self::LOCATION_DROPDOWN,
                            'label'          => __('Move'),
                            'permission'     => function ($conversation, $user) {
                                    return $user->can('move', \App\Conversation::class);
                            },
                            'class'          => '',
                            'url'            => function ($conversation) {
                                    return route('conversations.ajax_html', array_merge([ 'action' => 'move_conv' ], \Request::all(), [ 'conversation_id' => $conversation->id ]));
                            },
                            'attrs'          => [
                                    'data-trigger'         => 'modal',
                                    'data-modal-title'     => __('Move Conversation'),
                                    'data-modal-no-footer' => 'true',
                                    'data-modal-on-show'   => 'initMoveConv',
                            ],
                            'fixed_location' => true,
                    ],
                    'print'         => [
                            'icon'           => 'glyphicon-print',
                            'location'       => self::LOCATION_DROPDOWN,
                            'label'          => __('Print'),
                            'class'          => '',
                            'permission'     => function () {
                                    return true;
                            },
                            'url'            => function () {
                                    return \Request::getRequestUri() . '&amp;print=1';
                            },
                            'attrs'          => [
                                    'target' => '_blank',
                            ],
                            'fixed_location' => true,
                    ],
            ];

            // Allow overriding default actions while preserving backwards compatibility
            $actions = \Eventy::filter('conversation.get_action_buttons', $actions, $conversation, $user, $mailbox);

            // Filter actions based on permissions
            foreach ($actions as $key => $action) {
                if (! $action['permission']($conversation, $user)) {
                        unset($actions[ $key ]);
                }
            }

            return $actions;
    }

        /**
         * Get actions for a specific location
         */
    public static function getActionsByLocation($actions, $location)
    {
        return array_filter($actions, function ($action) use ($location) {
            if (! empty($action['fixed_location'])) {
                    // Modified logic for responsive display
                if ($location === self::LOCATION_TOOLBAR) {
                    // Show in toolbar if it's a toolbar action
                    return $action['location'] === self::LOCATION_TOOLBAR;
                } elseif ($location === self::LOCATION_DROPDOWN) {
                        // Show in dropdown if explicitly flagged or if it's a dropdown action
                        return $action['location'] === self::LOCATION_DROPDOWN ||
                               ( ! empty($action['show_in_dropdown']) && $action['location'] === self::LOCATION_TOOLBAR );
                }
            }

                return $action['location'] === $location;
        });
    }
}
