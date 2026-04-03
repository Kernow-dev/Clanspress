<?php
/**
 * Maintenance class for handling database updates and other maintenance tasks.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress;

/**
 * Runs one-shot maintenance steps keyed by {@see Main::MAINTENANCE_VERSION}.
 *
 * For the 1.0.0 WordPress.org release, all prior incremental rewrite flushes are folded into a single step.
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
	 * Initial public release: flush rewrite rules so virtual routes and CPT rules resolve.
	 *
	 * @return void
	 */
	private static function run_1(): void {
		flush_rewrite_rules( false );
	}
}
