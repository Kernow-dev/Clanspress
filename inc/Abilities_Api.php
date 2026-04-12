<?php
/**
 * WordPress Abilities API integration (WordPress 6.9+).
 *
 * Registers discoverable, schema-backed abilities aligned with {@see Public_Rest} discovery
 * and public team metadata. No-ops on older WordPress versions where the API is unavailable.
 *
 * @package clanspress
 * @link https://developer.wordpress.org/apis/abilities-api/
 */

namespace Kernowdev\Clanspress;

use Kernowdev\Clanspress\Extensions\Loader;
use Kernowdev\Clanspress\Extensions\Skeleton;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Clanspress abilities and category when core supports them.
 *
 * Official companion plugins add abilities on `wp_abilities_api_init` (priority 20) in the
 * same category: forums (`clanspress-forums/*`), social (`clanspress-social/*`),
 * points (`clanspress-points/*`), and ranks (`clanspress-ranks/*`).
 */
final class Abilities_Api {

	public const CATEGORY = 'clanspress';

	/**
	 * Wire Abilities API hooks when available.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		if ( ! function_exists( 'wp_register_ability' ) || ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( self::class, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( self::class, 'register_abilities' ) );
	}

	/**
	 * @return void
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Clanspress', 'clanspress' ),
				'description' => __( 'Community discovery and read-only metadata exposed by the Clanspress plugin.', 'clanspress' ),
			)
		);
	}

	/**
	 * @return void
	 */
	public static function register_abilities(): void {
		wp_register_ability(
			'clanspress/discovery',
			array(
				'label'               => __( 'Clanspress discovery', 'clanspress' ),
				'description'         => __( 'Returns whether this site runs Clanspress, the plugin version, and optional cross-site match sync hints.', 'clanspress' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'        => 'object',
					'description' => __( 'Discovery payload.', 'clanspress' ),
					'properties'  => array(
						'clanspress' => array(
							'type'        => 'boolean',
							'description' => __( 'Always true when Clanspress is active.', 'clanspress' ),
						),
						'name'       => array(
							'type' => 'string',
						),
						'version'    => array(
							'type'        => 'string',
							'description' => __( 'Clanspress plugin version.', 'clanspress' ),
						),
						'match_sync' => array(
							'type'        => 'object',
							'description' => __( 'Present when Libsodium signing is available.', 'clanspress' ),
						),
					),
					'required'   => array( 'clanspress', 'name', 'version' ),
				),
				'execute_callback'    => array( self::class, 'execute_discovery' ),
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'clanspress/public-team',
			array(
				'label'               => __( 'Public team metadata', 'clanspress' ),
				'description'         => __( 'Returns public metadata for a published team given its slug or an absolute team profile URL.', 'clanspress' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'slug' => array(
							'type'        => 'string',
							'description' => __( 'Team post slug (`cp_team` post_name).', 'clanspress' ),
						),
						'url'  => array(
							'type'        => 'string',
							'description' => __( 'Full URL to a team profile page on this site.', 'clanspress' ),
						),
					),
				),
				'output_schema'       => array(
					'type'        => 'object',
					'description' => __( 'Public team fields.', 'clanspress' ),
					'properties'  => array(
						'id'          => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'permalink'   => array( 'type' => 'string' ),
						'logoUrl'     => array( 'type' => 'string' ),
						'motto'       => array( 'type' => 'string' ),
						'country'     => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'id', 'title', 'slug' ),
				),
				'execute_callback'    => array( self::class, 'execute_public_team' ),
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'clanspress/active-extensions',
			array(
				'label'               => __( 'Active Clanspress extensions', 'clanspress' ),
				'description'         => __( 'Lists extensions currently loaded by Clanspress (slug, name, version).', 'clanspress' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'extensions' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'slug'        => array( 'type' => 'string' ),
									'name'        => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'version'     => array( 'type' => 'string' ),
								),
							),
						),
					),
					'required'   => array( 'extensions' ),
				),
				'execute_callback'    => array( self::class, 'execute_active_extensions' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);

		/**
		 * Fires after Clanspress registers core abilities (WordPress 6.9+).
		 *
		 * Companion plugins may call `wp_register_ability()` here to add more abilities
		 * in the same category using slug `clanspress` via {@see Abilities_Api::CATEGORY}.
		 */
		do_action( 'clanspress_abilities_registered' );
	}

	/**
	 * @param mixed $input Ability input (unused).
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute_discovery( $input ) {
		unset( $input );

		return Public_Rest::build_discovery_payload();
	}

	/**
	 * @param mixed $input Associative input with optional `slug` and `url`.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute_public_team( $input ) {
		$args = is_array( $input ) ? $input : array();
		$slug = isset( $args['slug'] ) ? (string) $args['slug'] : '';
		$url  = isset( $args['url'] ) ? esc_url_raw( (string) $args['url'] ) : '';

		return Public_Rest::get_public_team_data_by_slug_or_url( $slug, $url );
	}

	/**
	 * @param mixed $input Ability input (unused).
	 * @return array<string, mixed>
	 */
	public static function execute_active_extensions( $input ) {
		unset( $input );

		$loader = Loader::instance();
		$list   = array();

		foreach ( $loader->get_extensions() as $extension ) {
			if ( ! $extension instanceof Skeleton ) {
				continue;
			}
			$list[] = array(
				'slug'        => (string) $extension->slug,
				'name'        => (string) $extension->name,
				'description' => (string) $extension->description,
				'version'     => (string) $extension->version,
			);
		}

		/**
		 * Filter the payload returned by the `clanspress/active-extensions` ability.
		 *
		 * @param list<array{slug: string, name: string, description: string, version: string}> $list Extensions.
		 */
		$list = (array) apply_filters( 'clanspress_abilities_active_extensions_list', $list );

		return array( 'extensions' => $list );
	}
}
