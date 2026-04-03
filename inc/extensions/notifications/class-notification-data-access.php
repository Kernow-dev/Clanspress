<?php
/**
 * Notification data access layer.
 *
 * @package Clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Notification;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Table names from `Notification_Schema::table_name()`; values use `$wpdb->prepare()`.

/**
 * CRUD and query operations for notifications.
 */
final class Notification_Data_Access {

	/**
	 * Notification statuses.
	 */
	public const STATUS_PENDING   = 'pending';
	public const STATUS_ACCEPTED  = 'accepted';
	public const STATUS_DECLINED  = 'declined';
	public const STATUS_DISMISSED = 'dismissed';
	public const STATUS_EXPIRED   = 'expired';

	/**
	 * Insert a new notification.
	 *
	 * @param array<string, mixed> $data {
	 *     Notification data.
	 *
	 *     @type int    $user_id     Required. User to notify.
	 *     @type string $type        Required. Notification type slug.
	 *     @type string $title       Required. Short title.
	 *     @type string $message     Optional. Longer message.
	 *     @type string $url         Optional. Link URL.
	 *     @type int    $actor_id    Optional. User who triggered the notification.
	 *     @type string $object_type Optional. Related object type (e.g., 'team', 'post').
	 *     @type int    $object_id   Optional. Related object ID.
	 *     @type array  $data        Optional. Additional data (JSON encoded).
	 *     @type array  $actions     Optional. Action buttons (see clanspress_notify for format).
	 * }
	 * @return int|\WP_Error Notification ID or error.
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$user_id = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : 0;
		$type    = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : '';
		$title   = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';

		if ( $user_id <= 0 ) {
			return new \WP_Error( 'missing_user', __( 'User ID is required.', 'clanspress' ) );
		}
		if ( '' === $type ) {
			return new \WP_Error( 'missing_type', __( 'Notification type is required.', 'clanspress' ) );
		}
		if ( '' === $title ) {
			return new \WP_Error( 'missing_title', __( 'Notification title is required.', 'clanspress' ) );
		}

		$extra_data = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : null;
		$actions    = isset( $data['actions'] ) && is_array( $data['actions'] ) ? $data['actions'] : null;

		/**
		 * Filter notification data before insertion.
		 *
		 * @param array $data Notification data.
		 */
		$data = (array) apply_filters( 'clanspress_before_insert_notification', $data );

		$insert = array(
			'user_id'     => $user_id,
			'type'        => $type,
			'title'       => $title,
			'message'     => isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : null,
			'url'         => isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : null,
			'actor_id'    => isset( $data['actor_id'] ) ? absint( $data['actor_id'] ) : null,
			'object_type' => isset( $data['object_type'] ) ? sanitize_key( $data['object_type'] ) : null,
			'object_id'   => isset( $data['object_id'] ) ? absint( $data['object_id'] ) : null,
			'data'        => $extra_data ? wp_json_encode( $extra_data ) : null,
			'actions'     => $actions ? wp_json_encode( $actions ) : null,
			'status'      => self::STATUS_PENDING,
			'is_read'     => 0,
			'created_at'  => current_time( 'mysql', true ),
			'read_at'     => null,
			'actioned_at' => null,
		);

		$result = $wpdb->insert( Notification_Schema::table_name(), $insert );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', __( 'Failed to insert notification.', 'clanspress' ) );
		}

		$notification_id = (int) $wpdb->insert_id;

		/**
		 * Fires after a notification is inserted.
		 *
		 * @param int   $notification_id Notification ID.
		 * @param array $data            Notification data.
		 */
		do_action( 'clanspress_notification_inserted', $notification_id, $data );

		return $notification_id;
	}

	/**
	 * Get a notification by ID.
	 *
	 * @param int $notification_id Notification ID.
	 * @return object|null
	 */
	public static function get( int $notification_id ): ?object {
		global $wpdb;

		if ( $notification_id <= 0 ) {
			return null;
		}

		$table = Notification_Schema::table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $notification_id ) );

		if ( ! $row ) {
			return null;
		}

		return self::hydrate_row( $row );
	}

	/**
	 * Get notifications for a user.
	 *
	 * @param int  $user_id  User ID.
	 * @param int  $page     Page number.
	 * @param int  $per_page Per page.
	 * @param bool $unread_only Only unread notifications.
	 * @return array{notifications: object[], total: int, unread_count: int}
	 */
	public static function get_for_user( int $user_id, int $page = 1, int $per_page = 20, bool $unread_only = false ): array {
		global $wpdb;

		Notification_Schema::ensure_table_exists();

		$table    = Notification_Schema::table_name();
		$per_page = min( 50, max( 1, absint( $per_page ) ) );
		$offset   = ( max( 1, $page ) - 1 ) * $per_page;

		if ( $unread_only ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table from schema helper.
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
					$user_id
				)
			);
			$unread_count = $total;
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table from schema helper.
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
					$user_id
				)
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table from schema helper.
			$unread_count = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0", $user_id )
			);
		}

		$limit  = max( 0, (int) $per_page );
		$offset = max( 0, (int) $offset );

		if ( $unread_only ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table from schema helper.
			$sql_base = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND is_read = 0 ORDER BY created_at DESC",
				$user_id
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table from schema helper.
			$sql_base = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
				$user_id
			);
		}

		// Append LIMIT/OFFSET as integers only (no placeholders): some DB drivers / wpdb::prepare combinations mishandle %d in LIMIT.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql_base is from $wpdb->prepare(); LIMIT/OFFSET are non-negative ints.
		$sql = $sql_base . sprintf( ' LIMIT %d OFFSET %d', $limit, $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Hybrid query: $sql_base from prepare() + integer-cast LIMIT/OFFSET.
		$rows = $wpdb->get_results( $sql );

		$notifications = array_map( array( self::class, 'hydrate_row' ), $rows ?: array() );

		return array(
			'notifications' => $notifications,
			'total'         => $total,
			'unread_count'  => $unread_count,
		);
	}

	/**
	 * Get unread count for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public static function get_unread_count( int $user_id ): int {
		global $wpdb;

		Notification_Schema::ensure_table_exists();

		$table = Notification_Schema::table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0", $user_id )
		);
	}

	/**
	 * Mark a notification as read.
	 *
	 * @param int $notification_id Notification ID.
	 * @param int $user_id         User ID (for permission check).
	 * @return bool
	 */
	public static function mark_read( int $notification_id, int $user_id ): bool {
		global $wpdb;

		$table = Notification_Schema::table_name();

		$result = $wpdb->update(
			$table,
			array(
				'is_read' => 1,
				'read_at' => current_time( 'mysql', true ),
			),
			array(
				'id'      => $notification_id,
				'user_id' => $user_id,
			),
			array( '%d', '%s' ),
			array( '%d', '%d' )
		);

		if ( false !== $result && $result > 0 ) {
			/**
			 * Fires after a notification is marked as read.
			 *
			 * @param int $notification_id Notification ID.
			 * @param int $user_id         User ID.
			 */
			do_action( 'clanspress_notification_read', $notification_id, $user_id );
			return true;
		}

		return false;
	}

	/**
	 * Mark all notifications as read for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int Number of notifications marked read.
	 */
	public static function mark_all_read( int $user_id ): int {
		global $wpdb;

		$table = Notification_Schema::table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET is_read = 1, read_at = %s WHERE user_id = %d AND is_read = 0",
				current_time( 'mysql', true ),
				$user_id
			)
		);

		$count = false !== $result ? (int) $result : 0;

		if ( $count > 0 ) {
			/**
			 * Fires after all notifications are marked as read.
			 *
			 * @param int $user_id User ID.
			 * @param int $count   Number marked read.
			 */
			do_action( 'clanspress_notifications_all_read', $user_id, $count );
		}

		return $count;
	}

	/**
	 * Delete a notification.
	 *
	 * @param int $notification_id Notification ID.
	 * @param int $user_id         User ID (for permission check).
	 * @return bool
	 */
	public static function delete( int $notification_id, int $user_id ): bool {
		global $wpdb;

		$table = Notification_Schema::table_name();

		$result = $wpdb->delete(
			$table,
			array(
				'id'      => $notification_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);

		if ( false !== $result && $result > 0 ) {
			/**
			 * Fires after a notification is deleted.
			 *
			 * @param int $notification_id Notification ID.
			 * @param int $user_id         User ID.
			 */
			do_action( 'clanspress_notification_deleted', $notification_id, $user_id );
			return true;
		}

		return false;
	}

	/**
	 * Delete all notifications for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int Number deleted.
	 */
	public static function delete_all_for_user( int $user_id ): int {
		global $wpdb;

		$table = Notification_Schema::table_name();

		$result = $wpdb->delete( $table, array( 'user_id' => $user_id ), array( '%d' ) );

		return false !== $result ? (int) $result : 0;
	}

	/**
	 * Delete notifications by object (e.g., when a team is deleted).
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id   Object ID.
	 * @return int Number deleted.
	 */
	public static function delete_by_object( string $object_type, int $object_id ): int {
		global $wpdb;

		$table = Notification_Schema::table_name();

		$result = $wpdb->delete(
			$table,
			array(
				'object_type' => $object_type,
				'object_id'   => $object_id,
			),
			array( '%s', '%d' )
		);

		return false !== $result ? (int) $result : 0;
	}

	/**
	 * Check if a similar notification already exists (deduplication).
	 *
	 * @param int    $user_id     User ID.
	 * @param string $type        Notification type.
	 * @param string $object_type Object type.
	 * @param int    $object_id   Object ID.
	 * @param int    $actor_id    Actor ID.
	 * @return bool
	 */
	public static function exists( int $user_id, string $type, string $object_type = '', int $object_id = 0, int $actor_id = 0 ): bool {
		global $wpdb;

		$table = Notification_Schema::table_name();

		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND type = %s",
			$user_id,
			$type
		);

		if ( '' !== $object_type ) {
			$sql .= $wpdb->prepare( ' AND object_type = %s', $object_type );
		}
		if ( $object_id > 0 ) {
			$sql .= $wpdb->prepare( ' AND object_id = %d', $object_id );
		}
		if ( $actor_id > 0 ) {
			$sql .= $wpdb->prepare( ' AND actor_id = %d', $actor_id );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql ) > 0;
	}

	/**
	 * Execute an action on a notification.
	 *
	 * @param int    $notification_id Notification ID.
	 * @param int    $user_id         User ID (for permission check).
	 * @param string $action_key      Action key to execute.
	 * @return array{success: bool, message: string, redirect?: string}|\WP_Error
	 */
	public static function execute_action( int $notification_id, int $user_id, string $action_key ) {
		$notification = self::get( $notification_id );

		if ( ! $notification ) {
			return new \WP_Error( 'not_found', __( 'Notification not found.', 'clanspress' ) );
		}

		if ( (int) $notification->user_id !== $user_id ) {
			return new \WP_Error( 'forbidden', __( 'You cannot action this notification.', 'clanspress' ) );
		}

		if ( self::STATUS_PENDING !== $notification->status ) {
			return new \WP_Error( 'already_actioned', __( 'This notification has already been actioned.', 'clanspress' ) );
		}

		$actions = $notification->actions;
		if ( ! is_array( $actions ) || empty( $actions ) ) {
			return new \WP_Error( 'no_actions', __( 'This notification has no actions.', 'clanspress' ) );
		}

		$action = null;
		foreach ( $actions as $a ) {
			if ( isset( $a['key'] ) && $a['key'] === $action_key ) {
				$action = $a;
				break;
			}
		}

		if ( ! $action ) {
			return new \WP_Error( 'invalid_action', __( 'Invalid action.', 'clanspress' ) );
		}

		$handler = $action['handler'] ?? null;
		$status  = $action['status'] ?? self::STATUS_DISMISSED;

		/**
		 * Execute a notification action.
		 *
		 * Handlers should return an array with 'success', 'message', and optionally 'redirect'.
		 *
		 * @param array|null $result       Initial result (null).
		 * @param object     $notification Notification object.
		 * @param array      $action       Action config.
		 * @param int        $user_id      User ID.
		 */
		$result = apply_filters( 'clanspress_notification_action_' . $notification->type, null, $notification, $action, $user_id );

		if ( null === $result && $handler ) {
			/**
			 * Generic notification action handler.
			 *
			 * @param array|null $result       Initial result (null).
			 * @param string     $handler      Handler identifier.
			 * @param object     $notification Notification object.
			 * @param array      $action       Action config.
			 * @param int        $user_id      User ID.
			 */
			$result = apply_filters( 'clanspress_notification_action_handler', null, $handler, $notification, $action, $user_id );
		}

		if ( null === $result ) {
			$result = array(
				'success' => true,
				'message' => $action['success_message'] ?? __( 'Action completed.', 'clanspress' ),
			);
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update notification status.
		self::update_status( $notification_id, $status );

		return $result;
	}

	/**
	 * Update notification status.
	 *
	 * @param int    $notification_id Notification ID.
	 * @param string $status          New status.
	 * @return bool
	 */
	public static function update_status( int $notification_id, string $status ): bool {
		global $wpdb;

		$allowed = array(
			self::STATUS_PENDING,
			self::STATUS_ACCEPTED,
			self::STATUS_DECLINED,
			self::STATUS_DISMISSED,
			self::STATUS_EXPIRED,
		);

		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$table = Notification_Schema::table_name();

		$result = $wpdb->update(
			$table,
			array(
				'status'      => $status,
				'is_read'     => 1,
				'actioned_at' => current_time( 'mysql', true ),
				'read_at'     => current_time( 'mysql', true ),
			),
			array( 'id' => $notification_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $result && $result > 0 ) {
			/**
			 * Fires after a notification status is updated.
			 *
			 * @param int    $notification_id Notification ID.
			 * @param string $status          New status.
			 */
			do_action( 'clanspress_notification_status_changed', $notification_id, $status );
			return true;
		}

		return false;
	}

	/**
	 * Hydrate a database row into a notification object.
	 *
	 * @param object $row Database row.
	 * @return object
	 */
	private static function hydrate_row( object $row ): object {
		$row->id        = (int) $row->id;
		$row->user_id   = (int) $row->user_id;
		$row->actor_id  = $row->actor_id ? (int) $row->actor_id : null;
		$row->object_id = $row->object_id ? (int) $row->object_id : null;
		$row->is_read   = (bool) $row->is_read;
		$row->data      = $row->data ? json_decode( $row->data, true ) : null;
		$row->actions   = $row->actions ? json_decode( $row->actions, true ) : null;
		$row->status    = $row->status ?? self::STATUS_PENDING;

		// Check if notification is actionable (has actions and is pending).
		$row->is_actionable = is_array( $row->actions ) && ! empty( $row->actions ) && self::STATUS_PENDING === $row->status;

		// Add actor info if available.
		if ( $row->actor_id ) {
			$actor = get_userdata( $row->actor_id );
			if ( $actor ) {
				$row->actor = (object) array(
					'id'         => $actor->ID,
					'name'       => $actor->display_name,
					'avatar_url' => get_avatar_url( $actor->ID, array( 'size' => 48 ) ),
				);
			}
		}

		/**
		 * Filter a notification after hydration.
		 *
		 * @param object $row Notification object.
		 */
		return (object) apply_filters( 'clanspress_notification_hydrate', $row );
	}
}

// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
