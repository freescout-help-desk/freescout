<?php

namespace App\Helpers;

use App\Conversation;
use Eventy;
use Request;

class ConversationActions {
		// Location constants
		const LOCATION_TOOLBAR = 'toolbar';
		const LOCATION_DROPDOWN = 'dropdown';
		const LOCATION_BOTH = 'both';

		/**
		 * Get all available conversation actions
		 */
		public static function getActions( $conversation, $user, $mailbox ) {
				$actions = [
						// Default toolbar actions - keeping them in toolbar for backwards compatibility
						'reply'   => [
								'icon'           => 'glyphicon-share-alt',
								'location'       => self::LOCATION_TOOLBAR, // Forcing toolbar location for compatibility
								'label'          => __( 'Reply' ),
								'permission'     => function ( $conversation ) {
										return ( ! $conversation->isPhone() || ( $conversation->customer && $conversation->customer->getMainEmail() ) )
										       && Eventy::filter( 'conversation.reply_button.enabled', true, $conversation );
								},
								'class'          => 'conv-reply',
								'fixed_location' => true,
						],
						'note'    => [
								'icon'           => 'glyphicon-edit',
								'location'       => self::LOCATION_TOOLBAR, // Forcing toolbar location for compatibility
								'label'          => __( 'Note' ),
								'permission'     => function ( $conversation ) {
										return Eventy::filter( 'conversation.note_button.enabled', true, $conversation );
								},
								'class'          => 'conv-add-note',
								'fixed_location' => true,
						],
						'delete'  => [
								'icon'           => 'glyphicon-trash',
								'location'       => self::LOCATION_BOTH, // Keeping both locations for compatibility
								'label'          => $conversation->state != Conversation::STATE_DELETED ? __( 'Delete' ) : __( 'Delete Forever' ),
								'permission'     => function ( $conversation, $user ) {
										return $user->can( 'delete', $conversation );
								},
								'class'          => $conversation->state != Conversation::STATE_DELETED ? 'conv-delete' : 'conv-delete-forever',
								'mobile_only'    => true,
								'fixed_location' => true,
						],

						// Default dropdown actions - keeping them in dropdown for backwards compatibility
						'follow'  => [
								'icon'           => 'glyphicon-bell',
								'location'       => self::LOCATION_DROPDOWN,
								'label'          => __( 'Follow' ),
								'permission'     => function () {
										return true;
								},
								'class'          => 'conv-follow',
								'has_opposite'   => true,
								'opposite'       => [
										'label' => __( 'Unfollow' ),
										'class' => 'conv-follow',
								],
								'fixed_location' => true,
						],
						'forward' => [
								'icon'           => 'glyphicon-arrow-right',
								'location'       => self::LOCATION_DROPDOWN,
								'label'          => __( 'Forward' ),
								'permission'     => function () {
										return true;
								},
								'class'          => 'conv-forward',
								'fixed_location' => true,
						],
						'merge'   => [
								'icon'           => 'glyphicon-indent-left',
								'location'       => self::LOCATION_DROPDOWN,
								'label'          => __( 'Merge' ),
								'permission'     => function ( $conversation ) {
										return ! $conversation->isChat();
								},
								'class'          => '',
								'url'            => function ( $conversation ) {
										return route( 'conversations.ajax_html', array_merge( [ 'action' => 'merge_conv' ], Request::all(), [ 'conversation_id' => $conversation->id ] ) );
								},
								'attrs'          => [
										'data-trigger'         => 'modal',
										'data-modal-title'     => __( 'Merge Conversations' ),
										'data-modal-no-footer' => 'true',
										'data-modal-on-show'   => 'initMergeConv',
								],
								'fixed_location' => true,
						],
						'move'    => [
								'icon'           => 'glyphicon-log-out',
								'location'       => self::LOCATION_DROPDOWN,
								'label'          => __( 'Move' ),
								'permission'     => function ( $conversation, $user ) {
										return $user->can( 'move', Conversation::class );
								},
								'class'          => '',
								'url'            => function ( $conversation ) {
										return route( 'conversations.ajax_html', array_merge( [ 'action' => 'move_conv' ], Request::all(), [ 'conversation_id' => $conversation->id ] ) );
								},
								'attrs'          => [
										'data-trigger'         => 'modal',
										'data-modal-title'     => __( 'Move Conversation' ),
										'data-modal-no-footer' => 'true',
										'data-modal-on-show'   => 'initMoveConv',
								],
								'fixed_location' => true,
						],
						'print'   => [
								'icon'           => 'glyphicon-print',
								'location'       => self::LOCATION_DROPDOWN,
								'label'          => __( 'Print' ),
								'permission'     => function () {
										return true;
								},
								'url'            => function () {
										return Request::getRequestUri() . '&amp;print=1';
								},
								'attrs'          => [
										'target' => '_blank',
								],
								'fixed_location' => true,
								'class'          => '',
						],
				];

				// Allow overriding default actions while preserving backwards compatibility
				$actions = Eventy::filter( 'conversation.actions', $actions, $conversation, $user, $mailbox );

				// Filter actions based on permissions
				foreach ( $actions as $key => $action ) {
						if ( ! $action['permission']( $conversation, $user ) ) {
								unset( $actions[ $key ] );
						}
				}

				return $actions;
		}

		/**
		 * Get actions for a specific location
		 */
		public static function getActionsByLocation( $actions, $location ) {
				return array_filter( $actions, function ( $action ) use ( $location ) {
						// If action has fixed_location, respect its original location
						if ( ! empty( $action['fixed_location'] ) ) {
								return $action['location'] === $location || $action['location'] === self::LOCATION_BOTH;
						}

						// For new/custom actions, use the specified location
						return $action['location'] === $location || $action['location'] === self::LOCATION_BOTH;
				} );
		}

		/**
		 * Override an action's location
		 * This should only be used for custom actions, not default ones
		 */
		public static function setActionLocation( $action_key, $location ) {
				// This method can be used to change location of custom actions
				// Default actions will maintain their original location for compatibility
				return Eventy::filter( 'conversation.action.location', $location, $action_key );
		}
}
