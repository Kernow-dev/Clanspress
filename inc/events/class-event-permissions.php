<?php
/**
 * Visibility and capability checks for `cp_event` posts.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Events;

/**
 * Permissions for team/group scoped events.
 */
final class Event_Permissions {

	/**
	 * Whether the viewer can see an event post (including draft rules).
	 *
	 * @param \WP_Post $post      Event post.
	 * @param int      $viewer_id Viewer user ID (0 = guest).
	 * @return bool
	 */
	public static function viewer_can_see( \WP_Post $post, int $viewer_id ): bool {
		if ( Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( 'publish' !== $post->post_status ) {
			return self::user_can_manage_event( $post->ID, $viewer_id );
		}

		$visibility = (string) get_post_meta( $post->ID, 'cp_event_visibility', true );
		$visibility = $visibility ? $visibility : Event_Post_Type::VISIBILITY_PUBLIC;

		$scope   = (string) get_post_meta( $post->ID, 'cp_event_scope', true );
		$team_id = (int) get_post_meta( $post->ID, 'cp_event_team_id', true );
		$group_id = (int) get_post_meta( $post->ID, 'cp_event_group_id', true );

		if ( Event_Post_Type::VISIBILITY_PUBLIC === $visibility ) {
			return true;
		}

		if ( $viewer_id <= 0 ) {
			return false;
		}

		if ( Event_Post_Type::VISIBILITY_MEMBERS === $visibility ) {
			return true;
		}

		if ( Event_Post_Type::SCOPE_TEAM === $scope && $team_id > 0 ) {
			if ( Event_Post_Type::VISIBILITY_TEAM_ADMINS === $visibility ) {
				return function_exists( 'clanspress_teams_user_can_manage' )
					&& clanspress_teams_user_can_manage( $team_id, $viewer_id );
			}
			if ( Event_Post_Type::VISIBILITY_TEAM_MEMBERS === $visibility ) {
				return function_exists( 'clanspress_teams_get_member_role' )
					&& null !== clanspress_teams_get_member_role( $team_id, $viewer_id );
			}
		}

		if ( Event_Post_Type::SCOPE_GROUP === $scope && $group_id > 0 ) {
			if ( Event_Post_Type::VISIBILITY_TEAM_ADMINS === $visibility ) {
				return function_exists( 'clanspress_groups_user_can_manage' )
					&& clanspress_groups_user_can_manage( $group_id, $viewer_id );
			}
			if ( Event_Post_Type::VISIBILITY_TEAM_MEMBERS === $visibility ) {
				return function_exists( 'clanspress_groups_user_is_member' )
					&& clanspress_groups_user_is_member( $group_id, $viewer_id );
			}
		}

		/**
		 * Filter whether a viewer can see a published `cp_event`.
		 *
		 * @param bool     $can       Whether the viewer can see the event.
		 * @param \WP_Post $post      Event post.
		 * @param int      $viewer_id Viewer user ID.
		 */
		return (bool) apply_filters( 'clanspress_cp_event_viewer_can_see', false, $post, $viewer_id );
	}

	/**
	 * Whether the user can create/edit/delete this event (REST + forms).
	 *
	 * @param int $post_id Event post ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function user_can_manage_event( int $post_id, int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$post = get_post( $post_id );
		if ( ! ( $post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		$scope    = (string) get_post_meta( $post_id, 'cp_event_scope', true );
		$team_id  = (int) get_post_meta( $post_id, 'cp_event_team_id', true );
		$group_id = (int) get_post_meta( $post_id, 'cp_event_group_id', true );

		if ( Event_Post_Type::SCOPE_TEAM === $scope && $team_id > 0 && function_exists( 'clanspress_teams_user_can_manage' ) ) {
			return clanspress_teams_user_can_manage( $team_id, $user_id );
		}

		if ( Event_Post_Type::SCOPE_GROUP === $scope && $group_id > 0 && function_exists( 'clanspress_groups_user_can_manage' ) ) {
			return clanspress_groups_user_can_manage( $group_id, $user_id );
		}

		/**
		 * Filter whether a user can manage a `cp_event`.
		 *
		 * @param bool $can     Whether the user can manage.
		 * @param int  $post_id Event post ID.
		 * @param int  $user_id User ID.
		 */
		return (bool) apply_filters( 'clanspress_cp_event_user_can_manage', false, $post_id, $user_id );
	}

	/**
	 * Whether the viewer may see the RSVP / attendee list for an event post.
	 *
	 * @param \WP_Post $post      Event post.
	 * @param int      $viewer_id Viewer user ID (0 = guest).
	 * @return bool
	 */
	public static function can_view_attendees( \WP_Post $post, int $viewer_id ): bool {
		if ( Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( ! self::viewer_can_see( $post, $viewer_id ) ) {
			return false;
		}

		$att = (string) get_post_meta( $post->ID, 'cp_event_attendees_visibility', true );
		if ( 'public' === $att ) {
			return true;
		}

		if ( $viewer_id <= 0 ) {
			return false;
		}

		if ( self::user_can_manage_event( $post->ID, $viewer_id ) ) {
			return true;
		}

		$scope    = (string) get_post_meta( $post->ID, 'cp_event_scope', true );
		$team_id  = (int) get_post_meta( $post->ID, 'cp_event_team_id', true );
		$group_id = (int) get_post_meta( $post->ID, 'cp_event_group_id', true );

		if ( Event_Post_Type::SCOPE_TEAM === $scope && $team_id > 0 && function_exists( 'clanspress_teams_get_member_role' ) ) {
			return null !== clanspress_teams_get_member_role( $team_id, $viewer_id );
		}

		if ( Event_Post_Type::SCOPE_GROUP === $scope && $group_id > 0 && function_exists( 'clanspress_groups_user_is_member' ) ) {
			return clanspress_groups_user_is_member( $group_id, $viewer_id );
		}

		return false;
	}
}
