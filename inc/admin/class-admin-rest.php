<?php
/**
 * REST API for the unified Clanspress React admin.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Admin;

defined( 'ABSPATH' ) || exit;


use Kernowdev\Clanspress\Extensions\Abstract_Settings;
use Kernowdev\Clanspress\Extensions\Loader;
use Kernowdev\Clanspress\Extensions\Skeleton;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Registers `clanspress/v1/admin/*` routes.
 */
class Admin_Rest {

	/**
	 * @var array<string, Abstract_Settings>
	 */
	protected array $settings_by_option = array();

	/**
	 * @param array<string, Abstract_Settings> $settings_by_option Option key => settings handler.
	 */
	public function __construct( array $settings_by_option ) {
		$this->settings_by_option = $settings_by_option;
	}

	public function register_routes(): void {
		register_rest_route(
			'clanspress/v1',
			'/admin/bootstrap',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_bootstrap' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			'clanspress/v1',
			'/admin/settings/(?P<option_key>[a-z0-9_]+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'rest_save_settings' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'option_key' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		register_rest_route(
			'clanspress/v1',
			'/admin/extensions',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'rest_save_extensions' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Default icon picker strings (REST bootstrap + localized script data).
	 *
	 * @return array<string, string>
	 */
	public static function get_default_icon_picker_i18n(): array {
		$icon_picker_i18n = array(
			'title'        => __( 'Choose icon', 'clanspress' ),
			'none'         => __( 'No icon', 'clanspress' ),
			'noIcons'      => __( 'No icon packs registered yet.', 'clanspress' ),
			'chooseIcon'   => __( 'Choose icon', 'clanspress' ),
			'mediaLibrary' => __( 'Media Library…', 'clanspress' ),
			'clear'        => __( 'Clear', 'clanspress' ),
		);

		return (array) apply_filters( 'clanspress_admin_icon_picker_i18n', $icon_picker_i18n );
	}

	/**
	 * Icon packs for the unified admin (same data as REST `iconPacks`).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_unified_icon_packs(): array {
		return self::collect_bootstrap_icon_packs();
	}

	/**
	 * Load companion Icon_Packs classes when the plugin constants exist but autoload missed them (REST vs admin edge cases).
	 *
	 * @return void
	 */
	protected static function require_companion_icon_pack_classes(): void {
		if ( ! class_exists( \Kernowdev\ClanspressPoints\Points\Icon_Packs::class, false )
			&& defined( 'CLANSPRESS_POINTS_PATH' ) ) {
			$file = \CLANSPRESS_POINTS_PATH . 'inc/Points/Icon_Packs.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
		if ( ! class_exists( \Kernowdev\ClanspressRanks\Ranks\Icon_Packs::class, false )
			&& defined( 'CLANSPRESS_RANKS_PATH' ) ) {
			$file = \CLANSPRESS_RANKS_PATH . 'inc/Ranks/Icon_Packs.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Guess which extension a pack belongs to (used when `scope` is not set on the pack).
	 *
	 * @param string $id Sanitized pack id.
	 * @return string `points`, `ranks`, or `all`.
	 */
	protected static function infer_icon_pack_scope_from_id( string $id ): string {
		$id = sanitize_key( $id );
		if ( '' === $id ) {
			return 'all';
		}
		if ( 'clanspress-points-starter' === $id || str_starts_with( $id, 'clanspress-points-' ) ) {
			return 'points';
		}
		if ( 'clanspress-starter' === $id || str_starts_with( $id, 'clanspress-starter-' ) ) {
			return 'ranks';
		}
		if ( str_starts_with( $id, 'clanspress-ranks-' ) || str_starts_with( $id, 'clanspress-rank-' ) ) {
			return 'ranks';
		}

		return 'all';
	}

	/**
	 * Ensure each pack has a `scope` key for the React admin (`points` | `ranks` | `all`).
	 *
	 * @param array<string, mixed> $pack Normalized pack row.
	 * @return array<string, mixed>
	 */
	protected static function attach_icon_pack_scope( array $pack ): array {
		if ( isset( $pack['scope'] ) && is_string( $pack['scope'] ) ) {
			$candidate = sanitize_key( $pack['scope'] );
			if ( in_array( $candidate, array( 'points', 'ranks', 'all' ), true ) ) {
				$pack['scope'] = $candidate;
				return $pack;
			}
		}
		$id            = sanitize_key( (string) ( $pack['id'] ?? '' ) );
		$pack['scope'] = self::infer_icon_pack_scope_from_id( $id );

		return $pack;
	}

	/**
	 * Normalize icon pack rows for JSON (numeric keys → JS array; same rules as companion Icon_Packs::get_icon_packs).
	 *
	 * @param array<int, array<string, mixed>> $packs Raw packs.
	 * @return array<int, array<string, mixed>>
	 */
	protected static function normalize_bootstrap_icon_packs( array $packs ): array {
		$out = array();
		foreach ( $packs as $pack ) {
			if ( ! is_array( $pack ) ) {
				continue;
			}
			$id    = sanitize_key( (string) ( $pack['id'] ?? '' ) );
			$label = sanitize_text_field( (string) ( $pack['label'] ?? '' ) );
			$icons = isset( $pack['icons'] ) && is_array( $pack['icons'] ) ? $pack['icons'] : array();
			if ( '' === $id || '' === $label || array() === $icons ) {
				continue;
			}
			$normalized_icons = array();
			foreach ( $icons as $icon ) {
				if ( ! is_array( $icon ) ) {
					continue;
				}
				$icon_id    = sanitize_key( (string) ( $icon['id'] ?? '' ) );
				$icon_label = sanitize_text_field( (string) ( $icon['label'] ?? $icon_id ) );
				$icon_url   = esc_url_raw( (string) ( $icon['url'] ?? '' ) );
				if ( '' === $icon_id || '' === $icon_url ) {
					continue;
				}
				$normalized_icons[] = array(
					'id'    => $icon_id,
					'label' => '' !== $icon_label ? $icon_label : $icon_id,
					'url'   => $icon_url,
				);
			}
			if ( array() === $normalized_icons ) {
				continue;
			}
			$out[] = array(
				'id'    => $id,
				'label' => $label,
				'icons' => $normalized_icons,
			);
		}
		return $out;
	}

	/**
	 * Last-resort starter packs built from companion plugin paths (skips `clanspress_*_icon_packs` filters).
	 *
	 * Used when every icon row was stripped (hostile filters, empty `get_icon_packs()`) but SVGs exist on disk.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected static function get_emergency_starter_packs_normalized(): array {
		$raw = array();

		if ( defined( 'CLANSPRESS_POINTS_FILE' ) ) {
			$check = dirname( CLANSPRESS_POINTS_FILE ) . '/assets/point-icons/starter/coin-bronze.svg';
			if ( is_readable( $check ) ) {
				$base = trailingslashit( plugins_url( 'assets/point-icons/starter', CLANSPRESS_POINTS_FILE ) );
				$raw[] = array(
					'id'    => 'clanspress-points-starter',
					'label' => __( 'Clanspress Points Starter', 'clanspress' ),
					'icons' => array(
						array(
							'id'    => 'coin-bronze',
							'label' => __( 'Bronze Coin', 'clanspress' ),
							'url'   => $base . 'coin-bronze.svg',
						),
						array(
							'id'    => 'coin-silver',
							'label' => __( 'Silver Coin', 'clanspress' ),
							'url'   => $base . 'coin-silver.svg',
						),
						array(
							'id'    => 'coin-gold',
							'label' => __( 'Gold Coin', 'clanspress' ),
							'url'   => $base . 'coin-gold.svg',
						),
						array(
							'id'    => 'gem-blue',
							'label' => __( 'Blue Gem', 'clanspress' ),
							'url'   => $base . 'gem-blue.svg',
						),
						array(
							'id'    => 'gem-purple',
							'label' => __( 'Purple Gem', 'clanspress' ),
							'url'   => $base . 'gem-purple.svg',
						),
						array(
							'id'    => 'star-token',
							'label' => __( 'Star Token', 'clanspress' ),
							'url'   => $base . 'star-token.svg',
						),
					),
				);
			}
		}

		if ( defined( 'CLANSPRESS_RANKS_FILE' ) ) {
			$check = dirname( CLANSPRESS_RANKS_FILE ) . '/assets/rank-icons/starter/wood.svg';
			if ( is_readable( $check ) ) {
				$base = trailingslashit( plugins_url( 'assets/rank-icons/starter', CLANSPRESS_RANKS_FILE ) );
				$raw[] = array(
					'id'    => 'clanspress-starter',
					'label' => __( 'Clanspress Starter', 'clanspress' ),
					'icons' => array(
						array(
							'id'    => 'wood',
							'label' => __( 'Wood', 'clanspress' ),
							'url'   => $base . 'wood.svg',
						),
						array(
							'id'    => 'bronze',
							'label' => __( 'Bronze', 'clanspress' ),
							'url'   => $base . 'bronze.svg',
						),
						array(
							'id'    => 'silver',
							'label' => __( 'Silver', 'clanspress' ),
							'url'   => $base . 'silver.svg',
						),
						array(
							'id'    => 'gold',
							'label' => __( 'Gold', 'clanspress' ),
							'url'   => $base . 'gold.svg',
						),
						array(
							'id'    => 'platinum',
							'label' => __( 'Platinum', 'clanspress' ),
							'url'   => $base . 'platinum.svg',
						),
						array(
							'id'    => 'diamond',
							'label' => __( 'Diamond', 'clanspress' ),
							'url'   => $base . 'diamond.svg',
						),
					),
				);
			}
		}

		return self::normalize_bootstrap_icon_packs( $raw );
	}

	/**
	 * Normalized icon packs for the unified admin (points + ranks starter SVGs, etc.).
	 *
	 * Merges `clanspress_admin_icon_packs` with packs from companion plugins when those
	 * classes exist, deduped by pack id. Filter-only results that are empty after
	 * normalization, or invalid associative shapes, still get starter packs from Points/Ranks.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected static function collect_bootstrap_icon_packs(): array {
		self::require_companion_icon_pack_classes();

		$raw = apply_filters( 'clanspress_admin_icon_packs', array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		// Force a JSON array (associative filter output breaks `Array.isArray` in the React admin).
		$raw = array_values( array_filter( $raw, 'is_array' ) );

		$from_filter = self::normalize_bootstrap_icon_packs( $raw );

		$seen   = array();
		$merged = array();
		foreach ( $from_filter as $pack ) {
			$id = sanitize_key( (string) ( $pack['id'] ?? '' ) );
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$merged[]    = $pack;
		}

		$fallback = array();
		if ( class_exists( \Kernowdev\ClanspressPoints\Points\Icon_Packs::class ) ) {
			$fallback = array_merge(
				$fallback,
				\Kernowdev\ClanspressPoints\Points\Icon_Packs::get_icon_packs()
			);
		}
		if ( class_exists( \Kernowdev\ClanspressRanks\Ranks\Icon_Packs::class ) ) {
			$fallback = array_merge(
				$fallback,
				\Kernowdev\ClanspressRanks\Ranks\Icon_Packs::get_icon_packs()
			);
		}
		foreach ( $fallback as $pack ) {
			if ( ! is_array( $pack ) ) {
				continue;
			}
			$id = sanitize_key( (string) ( $pack['id'] ?? '' ) );
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$merged[]    = $pack;
		}

		if ( array() === $merged ) {
			$merged = self::get_emergency_starter_packs_normalized();
		}

		$scoped = array();
		foreach ( $merged as $pack ) {
			if ( is_array( $pack ) ) {
				$scoped[] = self::attach_icon_pack_scope( $pack );
			}
		}

		return $scoped;
	}

	/**
	 * @return \WP_REST_Response|WP_Error
	 */
	public function rest_bootstrap() {
		$loader     = Loader::instance();
		$extensions = $loader->get_extensions();

		$values   = array();
		$schemas  = array();
		foreach ( $this->settings_by_option as $key => $handler ) {
			$values[ $key ]  = $handler->get_all();
			$schemas[ $key ] = $handler->export_rest_schema();
		}

		return rest_ensure_response(
			array(
				'tabs'             => $this->build_tabs( $extensions ),
				'values'           => $values,
				'optionSchemas'    => $schemas,
				'extensions'       => $this->build_extensions_payload( $loader, $extensions ),
				'generalOptionKey' => General_Settings::OPTION_KEY,
				'iconPacks'        => self::get_unified_icon_packs(),
				'iconPickerI18n'   => self::get_default_icon_picker_i18n(),
				'plugin'           => array(
					'version' => (string) \clanspress()->get_version(),
				),
			)
		);
	}

	/**
	 * @param array<string, Skeleton> $extensions
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_tabs( array $extensions ): array {
		$tabs = array(
			array(
				'id'    => 'general',
				'type'  => 'general',
				'label' => __( 'General', 'clanspress' ),
			),
			array(
				'id'    => 'extensions',
				'type'  => 'extensions',
				'label' => __( 'Extensions', 'clanspress' ),
			),
		);

		$groups_key = Groups_Settings::OPTION_KEY;
		if ( isset( $this->settings_by_option[ $groups_key ] ) ) {
			$groups_handler = $this->settings_by_option[ $groups_key ];
			$groups_schema  = $groups_handler->export_rest_schema();
			if ( ! empty( $groups_schema ) ) {
				$tabs[] = array(
					'id'            => 'core-groups',
					'type'          => 'extension',
					'label'         => __( 'Groups', 'clanspress' ),
					'sectionGroups' => array(
						array(
							'kind'      => 'primary',
							'name'      => __( 'Groups', 'clanspress' ),
							'slug'      => 'groups',
							'optionKey' => $groups_key,
							'sections'  => $groups_schema,
						),
					),
				);
			}
		}

		$by_slug = $extensions;

		$children_by_parent = array();
		foreach ( $extensions as $slug => $ext ) {
			if ( ! $ext instanceof Skeleton ) {
				continue;
			}
			$parent = $ext->parent_slug ?? '';
			if ( '' !== (string) $parent ) {
				$children_by_parent[ $parent ][] = $slug;
			}
		}

		$added = array();

		$loader = Loader::instance();

		foreach ( $extensions as $slug => $ext ) {
			if ( ! $ext instanceof Skeleton ) {
				continue;
			}
			if ( '' !== (string) ( $ext->parent_slug ?? '' ) ) {
				continue;
			}
			if ( ! $loader->is_extension_installed( $slug ) ) {
				continue;
			}

			$groups = $this->section_groups_for_extension_tree( $slug, $ext, $by_slug, $children_by_parent, $loader );
			if ( empty( $groups ) ) {
				continue;
			}

			$tabs[]  = array(
				'id'             => 'ext-' . $slug,
				'type'           => 'extension',
				'label'          => $ext->name,
				'extensionSlug'  => $slug,
				'sectionGroups'  => $groups,
			);
			$added[ $slug ] = true;
		}

		// Orphan child extensions (unknown parent): own tab.
		foreach ( $extensions as $slug => $ext ) {
			if ( ! $ext instanceof Skeleton ) {
				continue;
			}
			$parent = (string) ( $ext->parent_slug ?? '' );
			if ( '' === $parent || isset( $by_slug[ $parent ] ) ) {
				continue;
			}
			if ( ! $loader->is_extension_installed( $slug ) ) {
				continue;
			}
			$admin = $ext->get_settings_admin();
			if ( ! $admin instanceof Abstract_Settings ) {
				continue;
			}
			if ( isset( $added[ $slug ] ) ) {
				continue;
			}

			$tabs[] = array(
				'id'            => 'ext-' . $slug,
				'type'          => 'extension',
				'label'         => $ext->name,
				'extensionSlug' => $slug,
				'sectionGroups' => array(
					array(
						'kind'      => 'child',
						'name'      => $ext->name,
						'slug'      => $slug,
						'optionKey' => $admin->get_option_key(),
						'sections'  => $admin->export_rest_schema(),
					),
				),
			);
			$added[ $slug ] = true;
		}

		return $tabs;
	}

	/**
	 * @param array<string, Skeleton>           $by_slug
	 * @param array<string, array<int, string>> $children_by_parent
	 * @param Loader                              $loader Extension install state.
	 * @return array<int, array<string, mixed>>
	 */
	protected function section_groups_for_extension_tree( string $root_slug, Skeleton $root, array $by_slug, array $children_by_parent, Loader $loader ): array {
		$groups = array();

		$root_admin = $root->get_settings_admin();
		if ( $root_admin instanceof Abstract_Settings ) {
			$schema = $root_admin->export_rest_schema();
			if ( ! empty( $schema ) ) {
				$groups[] = array(
					'kind'      => 'primary',
					'name'      => $root->name,
					'slug'      => $root_slug,
					'optionKey' => $root_admin->get_option_key(),
					'sections'  => $schema,
				);
			}
		}

		if ( empty( $children_by_parent[ $root_slug ] ) ) {
			return $groups;
		}

		foreach ( $children_by_parent[ $root_slug ] as $child_slug ) {
			if ( ! $loader->is_extension_installed( $child_slug ) ) {
				continue;
			}
			if ( ! isset( $by_slug[ $child_slug ] ) ) {
				continue;
			}
			$child = $by_slug[ $child_slug ];
			if ( ! $child instanceof Skeleton ) {
				continue;
			}
			$c_admin = $child->get_settings_admin();
			if ( ! $c_admin instanceof Abstract_Settings ) {
				continue;
			}
			$c_schema = $c_admin->export_rest_schema();
			if ( empty( $c_schema ) ) {
				continue;
			}
			$groups[] = array(
				'kind'      => 'child',
				'name'      => $child->name,
				'slug'      => $child_slug,
				'optionKey' => $c_admin->get_option_key(),
				'sections'  => $c_schema,
			);
		}

		return $groups;
	}

	/**
	 * @param array<string, Skeleton> $extensions
	 * @return array<int, array<string, mixed>> Each item includes `isCoreBundled` (main-package extensions only).
	 */
	protected function build_extensions_payload( Loader $loader, array $extensions ): array {
		$installed   = $loader->get_installed_extensions();
		$official    = $loader->get_official_extensions();
		$extensions  = $this->sort_extensions_by_dependencies( $extensions );
		$out         = array();

		$required    = Loader::get_required_extension_slugs();
		$core_bundle = Loader::get_core_bundled_extension_slugs();

		foreach ( $extensions as $slug => $ext ) {
			if ( ! $ext instanceof Skeleton ) {
				continue;
			}
			$out[] = array(
				'slug'           => $slug,
				'name'           => $ext->name,
				'description'    => $ext->description,
				'version'        => $ext->version,
				'type'           => $ext->type,
				'parentSlug'     => (string) ( $ext->parent_slug ?? '' ),
				'requires'       => array_values( $ext->requires ),
				'isOfficial'     => isset( $official[ $slug ] ),
				'isCoreBundled'  => in_array( $slug, $core_bundle, true ),
				'isInstalled'    => isset( $installed[ $slug ] ),
				'canInstall'     => $ext->can_install(),
				'isRequired'     => in_array( $slug, $required, true ),
			);
		}

		return $out;
	}

	/**
	 * Topological order by `requires` (dependencies before dependents), then slug.
	 *
	 * Extensions listed in {@see Loader::get_required_extension_slugs()} are ordered first among
	 * nodes with the same readiness (e.g. `cp_players` stays at the top).
	 *
	 * @param array<string, Skeleton> $extensions Registered extensions.
	 * @return array<string, Skeleton>
	 */
	protected function sort_extensions_by_dependencies( array $extensions ): array {
		$valid = array();
		foreach ( $extensions as $slug => $extension ) {
			if ( $extension instanceof Skeleton ) {
				$valid[ sanitize_key( (string) $slug ) ] = $extension;
			}
		}

		if ( $valid === array() ) {
			return array();
		}

		$slugs        = array_keys( $valid );
		$in_degree    = array_fill_keys( $slugs, 0 );
		$dependents   = array();
		$required     = Loader::get_required_extension_slugs();
		$required_rank = array_flip( $required );

		foreach ( $valid as $slug => $extension ) {
			foreach ( $extension->requires as $req ) {
				$req = sanitize_key( (string) $req );
				if ( '' === $req || $req === $slug || ! isset( $valid[ $req ] ) ) {
					continue;
				}
				if ( ! isset( $dependents[ $req ] ) ) {
					$dependents[ $req ] = array();
				}
				$dependents[ $req ][] = $slug;
				++$in_degree[ $slug ];
			}
		}

		$queue = array_values(
			array_filter(
				$slugs,
				static function ( string $s ) use ( $in_degree ): bool {
					return 0 === ( $in_degree[ $s ] ?? 1 );
				}
			)
		);

		$sorted = array();
		while ( $queue !== array() ) {
			usort(
				$queue,
				static function ( string $a, string $b ) use ( $required_rank ): int {
					$ra = array_key_exists( $a, $required_rank ) ? (int) $required_rank[ $a ] : 1000;
					$rb = array_key_exists( $b, $required_rank ) ? (int) $required_rank[ $b ] : 1000;
					if ( $ra !== $rb ) {
						return $ra <=> $rb;
					}
					return strcmp( $a, $b );
				}
			);

			while ( $queue !== array() && isset( $sorted[ $queue[0] ] ) ) {
				array_shift( $queue );
			}
			if ( $queue === array() ) {
				break;
			}

			$slug = array_shift( $queue );
			if ( null === $slug ) {
				break;
			}
			$sorted[ $slug ] = $valid[ $slug ];

			foreach ( $dependents[ $slug ] ?? array() as $dep ) {
				--$in_degree[ $dep ];
				if ( 0 === $in_degree[ $dep ] && ! in_array( $dep, $queue, true ) ) {
					$queue[] = $dep;
				}
			}
		}

		// Cycle or missing edges: append remaining in stable slug order.
		foreach ( $slugs as $slug ) {
			if ( ! isset( $sorted[ $slug ] ) ) {
				$sorted[ $slug ] = $valid[ $slug ];
			}
		}

		return $sorted;
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function rest_save_settings( WP_REST_Request $request ) {
		$key = sanitize_key( (string) $request->get_param( 'option_key' ) );

		if ( ! isset( $this->settings_by_option[ $key ] ) ) {
			return new WP_Error( 'clanspress_unknown_option', __( 'Unknown settings group.', 'clanspress' ), array( 'status' => 404 ) );
		}

		$body  = $request->get_json_params();
		$input = is_array( $body ) ? $body : array();

		$handler = $this->settings_by_option[ $key ];
		$saved   = $handler->save_from_input( $input );

		return rest_ensure_response(
			array(
				'optionKey' => $key,
				'values'    => $saved,
			)
		);
	}

	/**
	 * Reduce a requested extension list to slugs that can be enabled together in one save.
	 *
	 * Repeats require-closure pruning and per-extension `can_install_with_slugs()` checks until stable,
	 * so dependencies toggled in the same request are visible to each other before persistence.
	 *
	 * @param array<string, Skeleton> $available Registered extensions.
	 * @param array<int, string>      $requested Slugs the user submitted as enabled.
	 * @return array<int, string>
	 */
	protected function resolve_installable_requested_slugs( array $available, array $requested ): array {
		$slugs = array_values( array_unique( array_map( 'sanitize_key', $requested ) ) );
		$guard = 0;
		while ( $guard++ < 100 ) {
			$prev_count = count( $slugs );
			$set        = array_fill_keys( $slugs, true );

			$slugs = array_values(
				array_filter(
					$slugs,
					static function ( string $slug ) use ( $available, $set ): bool {
						if ( ! isset( $available[ $slug ] ) || ! $available[ $slug ] instanceof Skeleton ) {
							return false;
						}
						$ext = $available[ $slug ];
						foreach ( $ext->requires as $require ) {
							if ( ! isset( $set[ $require ] ) ) {
								return false;
							}
						}
						return true;
					}
				)
			);

			$slugs = array_values(
				array_filter(
					$slugs,
					static function ( string $slug ) use ( $available, $slugs ): bool {
						$ext = $available[ $slug ] ?? null;

						return $ext instanceof Skeleton && $ext->can_install_with_slugs( $slugs );
					}
				)
			);

			if ( count( $slugs ) === $prev_count ) {
				break;
			}
		}

		return $slugs;
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function rest_save_extensions( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) || ! isset( $body['installed'] ) || ! is_array( $body['installed'] ) ) {
			return new WP_Error( 'clanspress_bad_payload', __( 'Invalid extensions payload.', 'clanspress' ), array( 'status' => 400 ) );
		}

		$requested_raw = array_map( 'sanitize_key', $body['installed'] );
		$required      = Loader::get_required_extension_slugs();

		$loader    = Loader::instance();
		$available = $loader->get_extensions();

		$requested = array_values(
			array_unique(
				array_merge( $required, $requested_raw )
			)
		);

		$prev_slugs = array_keys( $loader->get_installed_extensions() );
		foreach ( array_diff( $prev_slugs, $requested ) as $removed_slug ) {
			if ( in_array( (string) $removed_slug, $required, true ) ) {
				continue;
			}
			$loader->uninstall_extension( (string) $removed_slug );
		}

		$resolved = $this->resolve_installable_requested_slugs( $available, $requested );

		$new = array();
		foreach ( $resolved as $slug ) {
			if ( ! isset( $available[ $slug ] ) ) {
				continue;
			}
			$ext = $available[ $slug ];
			if ( ! $ext instanceof Skeleton ) {
				continue;
			}
			$new[ $slug ] = array(
				'version' => $ext->version ?? '1.0.0',
			);
		}

		$new = (array) apply_filters( 'clanspress_validate_installed_extensions', $new, $requested, $available );

		foreach ( $required as $req_slug ) {
			if ( isset( $available[ $req_slug ] ) && $available[ $req_slug ] instanceof Skeleton ) {
				$new[ $req_slug ] = array(
					'version' => (string) ( $available[ $req_slug ]->version ?? '1.0.0' ),
				);
			}
		}

		if ( is_multisite() && is_network_admin() ) {
			update_site_option( 'clanspress_installed_extensions', $new );
		} else {
			update_option( 'clanspress_installed_extensions', $new );
		}

		// Defer regeneration to the next request so `init` runs with the new install list (rewrite rules match registered routes).
		delete_option( 'rewrite_rules' );

		return rest_ensure_response( array( 'installed' => $new ) );
	}
}
