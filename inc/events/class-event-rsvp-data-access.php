<?php
/**
 * Data access helpers for event RSVPs.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Events;

/**
 * Low-level CRUD for the event RSVP table.
 */
final class Event_Rsvp_Data_Access {
	/**
	 * Supported RSVP statuses.
	 */
	public const STATUS_ACCEPTED  = 'accepted';
	public const STATUS_DECLINED  = 'declined';
	public const STATUS_TENTATIVE = 'tentative';

	/**
	 * Normalize an RSVP status.
	 *
	 * @param mixed $status Raw status.
	 * @return string One of the STATUS_* constants.
	 */
	public static function sanitize_status( $status ): string {
		$status = sanitize_key( (string) $status );
		$allowed = array(
			self::STATUS_ACCEPTED,
			self::STATUS_DECLINED,
			self::STATUS_TENTATIVE,
		);

		return in_array( $status, $allowed, true ) ? $status : self::STATUS_TENTATIVE;
	}

	/**
	 * Get the current RSVP for a user+event.
	 *
	 * @param string $event_type Event type.
	 * @param int    $event_id   Event ID.
	 * @param int    $user_id    User ID.
	 * @return array<string, mixed>|null RSVP row or null.
	 */
	public static function get_user_rsvp( string $event_type, int $event_id, int $user_id ): ?array {
		global $wpdb;

		$event_type = sanitize_key( $event_type );
		$event_id   = absint( $event_id );
		$user_id    = absint( $user_id );

		if ( '' === $event_type || $event_id < 1 || $user_id < 1 ) {
			return null;
		}

		$table = Event_Rsvp_Schema::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE event_type = %s AND event_id = %d AND user_id = %d LIMIT 1",
				$event_type,
				$event_id,
				$user_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Upsert an RSVP for a user+event.
	 *
	 * @param string $event_type Event type.
	 * @param int    $event_id   Event ID.
	 * @param int    $user_id    User ID.
	 * @param string $status     Status slug.
	 * @return array<string, mixed> Result: `status`, `old_status`, `changed`.
	 */
	public static function set_user_rsvp( string $event_type, int $event_id, int $user_id, string $status ): array {
		global $wpdb;

		$event_type = sanitize_key( $event_type );
		$event_id   = absint( $event_id );
		$user_id    = absint( $user_id );
		$status     = self::sanitize_status( $status );

		if ( '' === $event_type || $event_id < 1 || $user_id < 1 ) {
			return array(
				'status'     => $status,
				'old_status' => null,
				'changed'    => false,
			);
		}

		$existing   = self::get_user_rsvp( $event_type, $event_id, $user_id );
		$old_status = is_array( $existing ) ? (string) ( $existing['status'] ?? '' ) : null;

		$now   = current_time( 'mysql', true );
		$table = Event_Rsvp_Schema::table_name();

		if ( is_array( $existing ) ) {
			if ( $old_status === $status ) {
				return array(
					'status'     => $status,
					'old_status' => $old_status,
					'changed'    => false,
				);
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array(
					'status'     => $status,
					'updated_at' => $now,
				),
				array(
					'event_type' => $event_type,
					'event_id'   => $event_id,
					'user_id'    => $user_id,
				),
				array( '%s', '%s' ),
				array( '%s', '%d', '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table,
				array(
					'event_type'  => $event_type,
					'event_id'    => $event_id,
					'user_id'     => $user_id,
					'status'      => $status,
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				array( '%s', '%d', '%d', '%s', '%s', '%s' )
			);
		}

		/**
		 * Fires when a user's RSVP changes for an event.
		 *
		 * Third-party extensions can hook this (e.g. activity feeds).
		 *
		 * @param string      $event_type  Event type slug.
		 * @param int         $event_id    Event ID.
		 * @param int         $user_id     User ID.
		 * @param string      $new_status  New RSVP status.
		 * @param string|null $old_status  Old RSVP status (null when first set).
		 */
		do_action( 'clanspress_event_rsvp_updated', $event_type, $event_id, $user_id, $status, $old_status );

		return array(
			'status'     => $status,
			'old_status' => $old_status,
			'changed'    => true,
		);
	}

	/**
	 * List attendees (user IDs) for an event, optionally filtered by status.
	 *
	 * @param string      $event_type Event type.
	 * @param int         $event_id   Event ID.
	 * @param string|null $status     One of STATUS_* or null for all.
	 * @param int         $limit      Max rows.
	 * @param int         $offset     Offset.
	 * @return array<int> User IDs.
	 */
	public static function get_attendee_user_ids( string $event_type, int $event_id, ?string $status = null, int $limit = 100, int $offset = 0 ): array {
		global $wpdb;

		$event_type = sanitize_key( $event_type );
		$event_id   = absint( $event_id );
		$limit      = max( 1, min( 500, absint( $limit ) ) );
		$offset     = max( 0, absint( $offset ) );

		if ( '' === $event_type || $event_id < 1 ) {
			return array();
		}

		$table = Event_Rsvp_Schema::table_name();

		if ( null !== $status ) {
			$status = self::sanitize_status( $status );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id FROM {$table} WHERE event_type = %s AND event_id = %d AND status = %s ORDER BY updated_at DESC LIMIT %d OFFSET %d",
					$event_type,
					$event_id,
					$status,
					$limit,
					$offset
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id FROM {$table} WHERE event_type = %s AND event_id = %d ORDER BY updated_at DESC LIMIT %d OFFSET %d",
					$event_type,
					$event_id,
					$limit,
					$offset
				)
			);
		}

		return array_values( array_filter( array_map( 'absint', is_array( $ids ) ? $ids : array() ) ) );
	}

	/**
	 * List attendee rows (user id + RSVP status) for an event.
	 *
	 * @param string      $event_type Event type.
	 * @param int         $event_id   Event ID.
	 * @param string|null $status     One of STATUS_* or null for all.
	 * @param int         $limit      Max rows.
	 * @param int         $offset     Offset.
	 * @return array<int, array{user_id:int, status:string}>
	 */
	public static function get_attendee_rows( string $event_type, int $event_id, ?string $status = null, int $limit = 100, int $offset = 0 ): array {
		global $wpdb;

		$event_type = sanitize_key( $event_type );
		$event_id   = absint( $event_id );
		$limit      = max( 1, min( 500, absint( $limit ) ) );
		$offset     = max( 0, absint( $offset ) );

		if ( '' === $event_type || $event_id < 1 ) {
			return array();
		}

		$table = Event_Rsvp_Schema::table_name();

		if ( null !== $status ) {
			$status = self::sanitize_status( $status );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, status FROM {$table} WHERE event_type = %s AND event_id = %d AND status = %s ORDER BY updated_at DESC LIMIT %d OFFSET %d",
					$event_type,
					$event_id,
					$status,
					$limit,
					$offset
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, status FROM {$table} WHERE event_type = %s AND event_id = %d ORDER BY updated_at DESC LIMIT %d OFFSET %d",
					$event_type,
					$event_id,
					$limit,
					$offset
				),
				ARRAY_A
			);
		}

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$uid = isset( $row['user_id'] ) ? absint( $row['user_id'] ) : 0;
			if ( $uid < 1 ) {
				continue;
			}
			$out[] = array(
				'user_id' => $uid,
				'status'  => self::sanitize_status( (string) ( $row['status'] ?? '' ) ),
			);
		}

		return $out;
	}
}

