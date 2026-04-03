<?php
/**
 * Registers the `cp_event` CPT and post meta for scheduled events (teams / groups).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Events;

/**
 * Custom post type and meta for front-end events (in-person or virtual).
 */
final class Event_Post_Type {

	public const POST_TYPE = 'cp_event';

	public const SCOPE_TEAM  = 'team';
	public const SCOPE_GROUP = 'group';

	public const MODE_IN_PERSON = 'in_person';
	public const MODE_VIRTUAL  = 'virtual';

	public const VISIBILITY_PUBLIC        = 'public';
	public const VISIBILITY_MEMBERS       = 'members';
	public const VISIBILITY_TEAM_MEMBERS  = 'team_members';
	public const VISIBILITY_TEAM_ADMINS   = 'team_admins';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_cpt' ), 9 );
		add_action( 'init', array( $this, 'register_meta' ), 11 );
	}

	/**
	 * Register the event post type (no public CPT URLs; use team/group virtual routes).
	 *
	 * @return void
	 */
	public function register_cpt(): void {
		$labels = array(
			'name'          => _x( 'Events', 'post type general name', 'clanspress' ),
			'singular_name' => _x( 'Event', 'post type singular name', 'clanspress' ),
			'add_new'       => __( 'Add New', 'clanspress' ),
			'add_new_item'  => __( 'Add New Event', 'clanspress' ),
			'edit_item'     => __( 'Edit Event', 'clanspress' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'description'         => __( 'Clanspress scheduled events.', 'clanspress' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'rest_base'           => 'cp_event',
				'rest_controller_class' => 'WP_REST_Posts_Controller',
				'supports'            => array( 'title', 'editor', 'excerpt', 'author' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'has_archive'         => false,
			)
		);
	}

	/**
	 * Register post meta with REST exposure; editor UI can be added later.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$scope_schema = array(
			'type' => 'string',
			'enum' => array( self::SCOPE_TEAM, self::SCOPE_GROUP ),
		);

		$mode_schema = array(
			'type' => 'string',
			'enum' => array( self::MODE_IN_PERSON, self::MODE_VIRTUAL ),
		);

		$visibility_schema = array(
			'type' => 'string',
			'enum' => array(
				self::VISIBILITY_PUBLIC,
				self::VISIBILITY_MEMBERS,
				self::VISIBILITY_TEAM_MEMBERS,
				self::VISIBILITY_TEAM_ADMINS,
			),
		);

		$att_vis_schema = array(
			'type' => 'string',
			'enum' => array( 'public', 'hidden' ),
		);

		$meta = array(
			'cp_event_scope'                 => array(
				'type'              => 'string',
				'default'           => self::SCOPE_TEAM,
				'sanitize_callback' => array( $this, 'sanitize_scope' ),
				'show_in_rest'      => array( 'schema' => $scope_schema ),
			),
			'cp_event_team_id'               => array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			),
			'cp_event_group_id'              => array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			),
			'cp_event_mode'                  => array(
				'type'              => 'string',
				'default'           => self::MODE_IN_PERSON,
				'sanitize_callback' => array( $this, 'sanitize_mode' ),
				'show_in_rest'      => array( 'schema' => $mode_schema ),
			),
			'cp_event_virtual_url'           => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => true,
			),
			'cp_event_address_line1'         => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'cp_event_address_line2'         => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'cp_event_locality'              => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'cp_event_region'                => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'cp_event_postcode'              => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'cp_event_country'               => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'cp_event_starts_at'             => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_datetime_gmt' ),
				'show_in_rest'      => true,
			),
			'cp_event_ends_at'               => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_datetime_gmt' ),
				'show_in_rest'      => true,
			),
			'cp_event_visibility'            => array(
				'type'              => 'string',
				'default'           => self::VISIBILITY_PUBLIC,
				'sanitize_callback' => array( $this, 'sanitize_visibility' ),
				'show_in_rest'      => array( 'schema' => $visibility_schema ),
			),
			'cp_event_attendees_visibility'  => array(
				'type'              => 'string',
				'default'           => 'hidden',
				'sanitize_callback' => array( $this, 'sanitize_attendees_visibility' ),
				'show_in_rest'      => array( 'schema' => $att_vis_schema ),
			),
		);

		foreach ( $meta as $key => $args ) {
			$args['single']            = true;
			$args['object_subtype']    = self::POST_TYPE;
			$args['auth_callback']     = array( $this, 'meta_auth_edit_post' );
			$args['show_in_rest']      = $args['show_in_rest'] ?? true;
			register_post_meta( self::POST_TYPE, $key, $args );
		}
	}

	/**
	 * Limit meta writes in REST/editor to users who can edit the post.
	 *
	 * @param mixed $allowed  Prior decision (unused).
	 * @param mixed $meta_key Meta key (unused).
	 * @param mixed $post_id  Post ID.
	 * @return bool
	 */
	public function meta_auth_edit_post( $allowed, $meta_key, $post_id ): bool {
		unset( $allowed, $meta_key );
		return current_user_can( 'edit_post', (int) $post_id );
	}

	/**
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_scope( $value ): string {
		$value = sanitize_key( (string) $value );
		return in_array( $value, array( self::SCOPE_TEAM, self::SCOPE_GROUP ), true ) ? $value : self::SCOPE_TEAM;
	}

	/**
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_mode( $value ): string {
		$value = sanitize_key( (string) $value );
		return in_array( $value, array( self::MODE_IN_PERSON, self::MODE_VIRTUAL ), true ) ? $value : self::MODE_IN_PERSON;
	}

	/**
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_visibility( $value ): string {
		$value   = sanitize_key( (string) $value );
		$allowed = array(
			self::VISIBILITY_PUBLIC,
			self::VISIBILITY_MEMBERS,
			self::VISIBILITY_TEAM_MEMBERS,
			self::VISIBILITY_TEAM_ADMINS,
		);
		return in_array( $value, $allowed, true ) ? $value : self::VISIBILITY_PUBLIC;
	}

	/**
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_attendees_visibility( $value ): string {
		$value   = sanitize_key( (string) $value );
		$allowed = array( 'public', 'hidden' );
		return in_array( $value, $allowed, true ) ? $value : 'hidden';
	}

	/**
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_datetime_gmt( $value ): string {
		if ( null === $value || '' === $value ) {
			return '';
		}
		if ( is_numeric( $value ) ) {
			return gmdate( 'Y-m-d H:i:s', (int) $value );
		}
		$str = sanitize_text_field( (string) $value );
		$ts  = strtotime( $str );
		if ( false === $ts ) {
			return '';
		}
		return gmdate( 'Y-m-d H:i:s', $ts );
	}
}
