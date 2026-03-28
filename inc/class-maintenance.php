<?php
/**
 * Maintenance class for handling database updates and other maintenance tasks.
 */
namespace Kernowdev\Clanspress;

/**
 * Class Maintenance
 * Handles database updates and other maintenance tasks.
 */
class Maintenance {
	/**
	 * Run maintenance tasks for a specific version.
	 *
	 * @param int $version The maintenance version to run.
	 * @return void
	 */
	public static function run( int $version ): void {
		$method = 'run_' . $version;
		if ( method_exists( __CLASS__, $method ) ) {
			self::$method();
		}
	}

	/**
	 * Maintenance tasks for version 1.
	 *
	 * @return void
	 */
	private static function run_1(): void {
		// Initial setup, no tasks needed
	}

	/**
	 * Flush rewrite rules after Teams front routes (/teams/create, /teams/manage).
	 *
	 * @return void
	 */
	private static function run_2(): void {
		flush_rewrite_rules( false );
	}

	/**
	 * Flush rewrites after team action URLs moved to teams/{slug}/manage/.
	 *
	 * @return void
	 */
	private static function run_3(): void {
		flush_rewrite_rules( false );
	}
}
