<?php
/**
 * Notifications database schema.
 *
 * @package Clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Notification;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery -- Custom table lifecycle (dbDelta, SHOW TABLES, DROP TABLE); names from `$wpdb->prefix` + static DDL.

/**
 * Handles database table creation and upgrades for notifications.
 */
final class Notification_Schema {

	public const OPTION_DB_VERSION = 'clanspress_notifications_db_version';
	public const DB_VERSION        = '1.0.0';

	/**
	 * Create or upgrade the notifications table.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'clanspress_notifications';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			type varchar(64) NOT NULL,
			title varchar(255) NOT NULL,
			message text NULL,
			url varchar(2048) NULL,
			actor_id bigint(20) unsigned NULL,
			object_type varchar(64) NULL,
			object_id bigint(20) unsigned NULL,
			data longtext NULL,
			actions longtext NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			is_read tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			read_at datetime NULL,
			actioned_at datetime NULL,
			PRIMARY KEY  (id),
			KEY user_unread (user_id, is_read, created_at),
			KEY user_status (user_id, status, created_at),
			KEY user_created (user_id, created_at),
			KEY type_created (type, created_at),
			KEY actor_id (actor_id),
			KEY object (object_type, object_id)
		) $charset;";

		dbDelta( $sql );

		update_option( self::OPTION_DB_VERSION, self::DB_VERSION );
	}

	/**
	 * Check and upgrade if needed.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$stored = get_option( self::OPTION_DB_VERSION, '' );

		if ( $stored === self::DB_VERSION ) {
			return;
		}

		self::create_tables();
	}

	/**
	 * Get the notifications table name.
	 *
	 * @return string
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'clanspress_notifications';
	}

	/**
	 * Ensure the notifications table exists (handles missing table when the DB version option is stale).
	 *
	 * @return void
	 */
	public static function ensure_table_exists(): void {
		global $wpdb;

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $found !== $table ) {
			self::create_tables();
		}
	}

	/**
	 * Drop the notifications table (uninstall).
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		delete_option( self::OPTION_DB_VERSION );
	}
}

// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
