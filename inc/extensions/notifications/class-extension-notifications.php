<?php
/**
 * Extension: Notifications (first-party).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions;

use Kernowdev\Clanspress\Extensions\Notification\Notification_Schema;
use Kernowdev\Clanspress\Extensions\Notification\Notifications_Runtime;

/**
 * Official extension that boots the in-site notifications subsystem (DB, REST, bell block, profile tab).
 */
class Notifications extends Skeleton {

	/**
	 * Registers the extension definition.
	 */
	public function __construct() {
		parent::__construct(
			'Notifications',
			'cp_notifications',
			__(
				'In-site notifications, REST API, and the notification bell block.',
				'clanspress'
			),
			'',
			'1.0.0',
			array( 'cp_players' )
		);
	}

	/**
	 * Use the official-extensions filter (not third-party registration).
	 *
	 * @param string $name        Extension name.
	 * @param string $slug        Extension slug.
	 * @param string $description Description.
	 * @param string $parent_slug Parent slug.
	 * @param string $version     Version.
	 * @param array  $requires    Required extension slugs.
	 */
	public function setup_extension(
		string $name,
		string $slug,
		string $description,
		string $parent_slug,
		string $version,
		array $requires
	): void {
		parent::setup_extension(
			$name,
			$slug,
			$description,
			$parent_slug,
			$version,
			$requires
		);

		remove_filter( 'clanspress_registered_extensions', array( $this, 'register_extension' ) );
		add_filter(
			'clanspress_official_registered_extensions',
			array( $this, 'register_extension' )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_installer(): void {
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_uninstaller(): void {
		Notification_Schema::drop_tables();
		parent::run_uninstaller();
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_updater(): void {
	}

	/**
	 * Boot notifications (hooks, blocks, schema upgrades).
	 *
	 * @return void
	 */
	public function run(): void {
		Notifications_Runtime::instance();
	}
}
