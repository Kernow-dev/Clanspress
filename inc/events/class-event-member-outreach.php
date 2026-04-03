<?php
/**
 * Optional roster outreach when managers create or update `cp_event` posts.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Events;

use WP_Post;

/**
 * Sends in-app notifications and/or seeds tentative RSVPs for team or group rosters.
 *
 * Third-party group plugins should supply member user IDs via
 * {@see 'clanspress_group_event_member_user_ids'}.
 */
final class Event_Member_Outreach {

	public const MODE_NONE            = 'none';
	public const MODE_NOTIFY          = 'notify';
	public const MODE_RSVP_TENTATIVE  = 'rsvp_tentative';

	/**
	 * RSVP table event_type for scheduled `cp_event` posts.
	 */
	private const RSVP_EVENT_TYPE = 'clanspress_event';

	/**
	 * Normalize a REST/form outreach mode.
	 *
	 * @param mixed $raw Raw value.
	 * @return string One of the MODE_* constants.
	 */
	public static function sanitize_mode( $raw ): string {
		$m = sanitize_key( (string) $raw );
		if ( self::MODE_NOTIFY === $m || self::MODE_RSVP_TENTATIVE === $m ) {
			return $m;
		}

		return self::MODE_NONE;
	}

	/**
	 * Notify roster members and optionally add tentative RSVPs for a published `cp_event`.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $mode     One of {@see self::MODE_NONE}, {@see self::MODE_NOTIFY}, {@see self::MODE_RSVP_TENTATIVE}.
	 * @return array{ notified: int, rsvp_set: int, skipped: bool } skipped is true when there were no recipient IDs.
	 */
	public static function run( int $event_id, string $mode ): array {
		$out = array(
			'notified' => 0,
			'rsvp_set' => 0,
			'skipped'  => false,
		);

		if ( $event_id < 1 || self::MODE_NONE === $mode ) {
			return $out;
		}

		$post = get_post( $event_id );
		if ( ! ( $post instanceof WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return $out;
		}

		if ( 'publish' !== $post->post_status && 'future' !== $post->post_status ) {
			return $out;
		}

		$scope = sanitize_key( (string) get_post_meta( $event_id, 'cp_event_scope', true ) );
		$team_id = (int) get_post_meta( $event_id, 'cp_event_team_id', true );
		$group_id = (int) get_post_meta( $event_id, 'cp_event_group_id', true );

		$user_ids = self::collect_recipient_user_ids( $scope, $team_id, $group_id );

		/**
		 * Filter user IDs included in event member outreach (notifications / tentative RSVPs).
		 *
		 * @param list<int> $user_ids User IDs.
		 * @param int       $event_id `cp_event` post ID.
		 * @param string    $scope    `cp_event_scope` meta (`team` or `group`).
		 * @param int       $team_id  Team post ID when scope is team.
		 * @param int       $group_id Group object ID when scope is group.
		 * @param string    $mode     Outreach mode (`none`, `notify`, `rsvp_tentative`).
		 */
		$user_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', (array) apply_filters( 'clanspress_event_member_outreach_user_ids', $user_ids, $event_id, $scope, $team_id, $group_id, $mode ) ),
					static function ( int $id ): bool {
						return $id > 0;
					}
				)
			)
		);

		if ( array() === $user_ids ) {
			$out['skipped'] = true;

			return $out;
		}

		$event_title = get_the_title( $post );
		$url         = Event_Entity_Rest_Controller::public_permalink_for_event_post( $post );

		foreach ( $user_ids as $uid ) {
			if ( self::MODE_RSVP_TENTATIVE === $mode ) {
				$existing = Event_Rsvp_Data_Access::get_user_rsvp( self::RSVP_EVENT_TYPE, $event_id, $uid );
				if ( null === $existing ) {
					Event_Rsvp_Data_Access::set_user_rsvp( self::RSVP_EVENT_TYPE, $event_id, $uid, Event_Rsvp_Data_Access::STATUS_TENTATIVE );
					++$out['rsvp_set'];
				}
			}

			if ( self::MODE_NOTIFY === $mode || self::MODE_RSVP_TENTATIVE === $mode ) {
				if ( function_exists( 'clanspress_notify' ) && function_exists( 'clanspress_notifications_extension_active' ) && clanspress_notifications_extension_active() ) {
					$is_group = Event_Post_Type::SCOPE_GROUP === $scope;
					if ( self::MODE_RSVP_TENTATIVE === $mode ) {
						$title = sprintf(
							/* translators: %s: event title */
							__( 'You\'re invited: %s', 'clanspress' ),
							$event_title
						);
						$message = __( 'Open the event to confirm your attendance.', 'clanspress' );
					} elseif ( $is_group ) {
						$title = sprintf(
							/* translators: %s: event title */
							__( 'New group event: %s', 'clanspress' ),
							$event_title
						);
						$message = __( 'A manager posted a new event for your group.', 'clanspress' );
					} else {
						$title = sprintf(
							/* translators: %s: event title */
							__( 'New team event: %s', 'clanspress' ),
							$event_title
						);
						$message = __( 'A manager posted a new event for your roster.', 'clanspress' );
					}

					$notif_type = $is_group ? 'group_event' : 'team_event';

					clanspress_notify(
						$uid,
						$notif_type,
						$title,
						array(
							'message'     => $message,
							'url'         => $url ? $url : home_url( '/' ),
							'dedupe'      => false,
							'object_type' => Event_Post_Type::POST_TYPE,
							'object_id'   => $event_id,
						)
					);
					++$out['notified'];
				}
			}
		}

		/**
		 * Fires after event member outreach runs for a `cp_event`.
		 *
		 * @param int                  $event_id Event post ID.
		 * @param string               $mode     Outreach mode.
		 * @param array<string, mixed> $result   `notified`, `rsvp_set`, `skipped` counts.
		 */
		do_action( 'clanspress_event_member_outreach_completed', $event_id, $mode, $out );

		return $out;
	}

	/**
	 * Resolve roster user IDs before the outreach filter runs.
	 *
	 * @param string $scope    `team` or `group` scope slug.
	 * @param int    $team_id  Team post ID.
	 * @param int    $group_id Group ID.
	 * @return list<int>
	 */
	private static function collect_recipient_user_ids( string $scope, int $team_id, int $group_id ): array {
		if ( Event_Post_Type::SCOPE_TEAM === $scope && $team_id > 0 && function_exists( 'clanspress_teams' ) ) {
			$teams = clanspress_teams();
			if ( ! $teams instanceof \Kernowdev\Clanspress\Extensions\Teams ) {
				return array();
			}

			$map = $teams->get_team_member_roles_map( $team_id );
			$out = array();
			foreach ( array_keys( $map ) as $uid ) {
				$uid = (int) $uid;
				if ( $uid < 1 ) {
					continue;
				}
				$role = isset( $map[ $uid ] ) ? (string) $map[ $uid ] : '';
				if ( \Kernowdev\Clanspress\Extensions\Teams::TEAM_ROLE_BANNED === $role ) {
					continue;
				}
				$out[] = $uid;
			}

			return $out;
		}

		if ( Event_Post_Type::SCOPE_GROUP === $scope && $group_id > 0 ) {
			/**
			 * Provide WordPress user IDs for members of a group-scoped event’s roster.
			 *
			 * Core leaves this empty; a `cp_group` integration should filter and return IDs.
			 *
			 * @param int[] $user_ids Default empty.
			 * @param int   $group_id Group object ID.
			 */
			$from_filter = apply_filters( 'clanspress_group_event_member_user_ids', array(), $group_id );

			return array_values(
				array_unique(
					array_filter(
						array_map( 'absint', (array) $from_filter ),
						static function ( int $id ): bool {
							return $id > 0;
						}
					)
				)
			);
		}

		return array();
	}
}
