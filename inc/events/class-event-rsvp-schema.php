<?php
/**
 * Events RSVP database schema.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Events;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery -- Custom RSVP table (dbDelta, DROP TABLE); names from `$wpdb->prefix` + static DDL.

/**
 * Handles database table creation and upgrades for event RSVPs.
 */
final class Event_Rsvp_Schema {
	public const OPTION_DB_VERSION = 'clanspress_event_rsvps_db_version';
	public const DB_VERSION        = '1.0.0';

	/**
	 * Create or upgrade the event RSVPs table.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$table   = self::table_name();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_type varchar(64) NOT NULL,
			event_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			status varchar(16) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_user (event_type, event_id, user_id),
			KEY event_status (event_type, event_id, status),
			KEY user_status (user_id, status, updated_at),
			KEY event_updated (event_type, event_id, updated_at)
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
	 * Get the event RSVPs table name.
	 *
	 * @return string
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'clanspress_event_rsvps';
	}

	/**
	 * Remove the RSVP table and version option (e.g. when the Events extension is uninstalled).
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from trusted prefix + constant suffix.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

		delete_option( self::OPTION_DB_VERSION );
	}
}

// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery

