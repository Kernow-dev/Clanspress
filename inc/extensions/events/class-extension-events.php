<?php
/**
 * Events extension: scheduled team/group events, RSVPs, blocks, and REST.
 *
 * Declares **requires** `cp_players`. Team-scoped routes and permissions integrate with the Teams
 * extension when it is enabled (`function_exists` guards in `inc/events/`). Top-level extension
 * (`parent_slug` empty).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions;
defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanspress\Events\Event_Rsvp_Schema;
use Kernowdev\Clanspress\Events\Events as Events_Runtime;

/**
 * Official extension that boots the `cp_event` subsystem when installed.
 */
class Events extends Skeleton {

	/**
	 * Registers extension metadata and the official-extensions whitelist entry.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Events', 'clanspress' ),
			'cp_events',
			__(
				'Team and group scheduled events, RSVP storage, REST API, and front-end blocks.',
				'clanspress'
			),
			'',
			'1.0.0',
			array( 'cp_players' )
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $name        Human-readable name.
	 * @param string $slug        Unique slug.
	 * @param string $description Short description.
	 * @param string $parent_slug Parent extension slug, or empty.
	 * @param string $version              Semantic version.
	 * @param array  $requires             Required extension slugs.
	 * @param string $requires_clanspress  Minimum Clanspress core version (`x.y.z`).
	 */
	public function setup_extension(
		string $name,
		string $slug,
		string $description,
		string $parent_slug,
		string $version,
		array $requires,
		string $requires_clanspress = ''
	): void {
		parent::setup_extension(
			$name,
			$slug,
			$description,
			$parent_slug,
			$version,
			$requires,
			$requires_clanspress
		);

		remove_filter( 'clanspress_registered_extensions', array( $this, 'register_extension' ) );
		add_filter( 'clanspress_official_registered_extensions', array( $this, 'register_extension' ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_installer(): void {
		flush_rewrite_rules( false );
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_uninstaller(): void {
		$ids = get_posts(
			array(
				'post_type'      => 'cp_event',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}

		Event_Rsvp_Schema::drop_tables();

		flush_rewrite_rules( false );

		parent::run_uninstaller();
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_updater(): void {
	}

	/**
	 * Boot the events runtime (CPT, meta, blocks, REST, RSVP schema).
	 *
	 * @return void
	 */
	public function run(): void {
		Events_Runtime::instance();
	}
}
