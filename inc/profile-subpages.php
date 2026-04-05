<?php
/**
 * Shared profile / entity subpage registry, navigation gate, and visibility helpers.
 *
 * Core registers `player`, `team`, and `group` contexts. Extensions may add contexts with
 * {@see clanspress_profile_subpage_registry_map()} and use {@see clanspress_register_profile_subpage()}
 * / {@see clanspress_get_profile_subpages()}. Navigation blocks should use
 * {@see clanspress_profile_subpages_visible_for_nav()}.
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registered profile subpage context slugs (core: player, team, group).
 *
 * @return list<string>
 */
function clanspress_profile_subpage_contexts(): array {
	return array_keys( clanspress_profile_subpage_registry_map() );
}

/**
 * Configuration map for subpage registries (filterable for additional contexts).
 *
 * Each context defines where the registry is stored, which filter mutates it before sort,
 * and the default FSE template id prefix.
 *
 * @return array<string, array{globals_key: string, filter: string, template_prefix: string}>
 */
function clanspress_profile_subpage_registry_map(): array {
	$map = array(
		'player' => array(
			'globals_key'     => 'clanspress_player_subpages_registry',
			'filter'          => 'clanspress_player_subpages',
			'template_prefix' => 'clanspress-player',
		),
		'team'   => array(
			'globals_key'     => 'clanspress_team_subpages_registry',
			'filter'          => 'clanspress_team_subpages',
			'template_prefix' => 'clanspress-team',
		),
		'group'  => array(
			'globals_key'     => 'clanspress_group_subpages_registry',
			'filter'          => 'clanspress_group_subpages',
			'template_prefix' => 'clanspress-group',
		),
	);

	/**
	 * Register additional profile subpage contexts (third-party entity profiles).
	 *
	 * @param array<string, array{globals_key: string, filter: string, template_prefix: string}> $map Context slug => config.
	 */
	return (array) apply_filters( 'clanspress_profile_subpage_registry_map', $map );
}

/**
 * Sort subpages by position then label.
 *
 * @param array<string, array<string, mixed>> $subpages Registry.
 * @return array<string, array<string, mixed>>
 */
function clanspress_profile_subpages_sort( array $subpages ): array {
	uasort(
		$subpages,
		static function ( $a, $b ) {
			$pa = (int) ( $a['position'] ?? 10 );
			$pb = (int) ( $b['position'] ?? 10 );
			if ( $pa === $pb ) {
				return strcmp( (string) ( $a['label'] ?? '' ), (string) ( $b['label'] ?? '' ) );
			}
			return $pa <=> $pb;
		}
	);

	return $subpages;
}

/**
 * Whether profile subpage navigation is enabled for a context (advanced / legacy gate).
 *
 * Defaults to true. Extensions gate their own tabs via extension settings (for example
 * Clanspress → Events → profile subpage checkboxes). Use {@see apply_filters()} on
 * `clanspress_profile_subpages_nav_enabled` to disable an entire context at once.
 *
 * @param string $context `player`, `team`, `group`, or an extension context.
 * @return bool
 */
function clanspress_profile_subpages_nav_enabled( string $context ): bool {
	$context = sanitize_key( $context );
	$map     = clanspress_profile_subpage_registry_map();

	if ( ! isset( $map[ $context ] ) ) {
		/**
		 * Whether profile subpage navigation is enabled for a custom context.
		 *
		 * @param bool   $enabled Default true.
		 * @param string $context Context slug.
		 */
		return (bool) apply_filters( 'clanspress_profile_subpages_nav_enabled_for_unknown_context', true, $context );
	}

	/**
	 * Whether profile subpage navigation is enabled for a core context.
	 *
	 * @param bool   $enabled Default true.
	 * @param string $context Context slug (`player`, `team`, `group`).
	 */
	return (bool) apply_filters( 'clanspress_profile_subpages_nav_enabled', true, $context );
}

/**
 * Register a subpage tab for a profile context.
 *
 * @param string $context `player`, `team`, `group`, or an extension context from the registry map.
 * @param string $slug    URL segment slug.
 * @param array  $args {
 *     @type string $label          Nav label.
 *     @type string $template_id    FSE template id (default `{prefix}-{slug}`).
 *     @type string $default_blocks Optional default block markup.
 *     @type string $capability     Capability for {@see current_user_can()} (default `read`).
 *     @type int    $position       Sort order (lower first).
 * }
 * @return void
 */
function clanspress_register_profile_subpage( string $context, string $slug, array $args = array() ): void {
	$context = sanitize_key( $context );
	$map     = clanspress_profile_subpage_registry_map();
	if ( ! isset( $map[ $context ] ) ) {
		return;
	}

	$config   = $map[ $context ];
	$slug     = sanitize_key( $slug );
	$defaults = array(
		'label'          => ucfirst( $slug ),
		'template_id'    => "{$config['template_prefix']}-{$slug}",
		'default_blocks' => '',
		'capability'     => 'read',
		'position'       => 10,
	);

	$gkey = $config['globals_key'];
	if ( ! isset( $GLOBALS[ $gkey ] ) || ! is_array( $GLOBALS[ $gkey ] ) ) {
		$GLOBALS[ $gkey ] = array();
	}

	$GLOBALS[ $gkey ][ $slug ] = array_merge( $defaults, $args );
}

/**
 * Resolved subpages for a context (filtered, sorted, and gated by `clanspress_profile_subpages_nav_enabled`).
 *
 * @param string $context Profile context slug.
 * @return array<string, array<string, mixed>>
 */
function clanspress_get_profile_subpages( string $context ): array {
	$context = sanitize_key( $context );
	$map     = clanspress_profile_subpage_registry_map();
	if ( ! isset( $map[ $context ] ) ) {
		return array();
	}

	$config   = $map[ $context ];
	$gkey     = $config['globals_key'];
	$registry = isset( $GLOBALS[ $gkey ] ) && is_array( $GLOBALS[ $gkey ] )
		? $GLOBALS[ $gkey ]
		: array();

	$subpages = (array) apply_filters( $config['filter'], $registry );
	$subpages = clanspress_profile_subpages_sort( $subpages );

	if ( ! clanspress_profile_subpages_nav_enabled( $context ) ) {
		return array();
	}

	return $subpages;
}

/**
 * Single subpage config or null.
 *
 * @param string $context Context slug.
 * @param string $slug    Subpage slug.
 * @return array<string, mixed>|null
 */
function clanspress_get_profile_subpage( string $context, string $slug ): ?array {
	$slug     = sanitize_key( $slug );
	$subpages = clanspress_get_profile_subpages( $context );

	return isset( $subpages[ $slug ] ) ? $subpages[ $slug ] : null;
}

/**
 * Subpages the current user may see in a profile nav (capability + `clanspress_profile_subpage_nav_visible`).
 *
 * Extensions should use this for custom navigation UIs; core blocks call it from render callbacks.
 *
 * @param string                               $context   Context slug (`player`, `team`, `group`, …).
 * @param int                                  $object_id Subject id (user id, team post id, group id).
 * @param array<string, array<string, mixed>> $subpages  Typically {@see clanspress_get_profile_subpages()} for `$context`.
 * @return array<string, array<string, mixed>>
 */
function clanspress_profile_subpages_visible_for_nav( string $context, int $object_id, array $subpages ): array {
	$context = sanitize_key( $context );
	if ( $object_id < 1 ) {
		return array();
	}

	$visible = array();
	foreach ( $subpages as $slug => $config ) {
		if ( ! is_array( $config ) ) {
			continue;
		}

		$slug = sanitize_key( (string) $slug );
		$cap  = isset( $config['capability'] ) ? (string) $config['capability'] : 'read';

		if ( ! current_user_can( $cap, $object_id ) ) {
			continue;
		}

		/**
		 * Whether a subpage tab is shown in profile navigation for this viewer and subject.
		 *
		 * @param bool   $visible    Default true after capability check.
		 * @param string $context    Context slug.
		 * @param string $slug       Subpage slug.
		 * @param int    $object_id  Subject id (meaning per context).
		 * @param array  $config     Subpage config.
		 */
		$show = (bool) apply_filters( 'clanspress_profile_subpage_nav_visible', true, $context, $slug, $object_id, $config );
		if ( $show ) {
			$visible[ $slug ] = $config;
		}
	}

	return $visible;
}

/**
 * @see clanspress_profile_subpages_nav_enabled()
 */
function clanspress_player_profile_subpages_nav_enabled(): bool {
	return clanspress_profile_subpages_nav_enabled( 'player' );
}

/**
 * @see clanspress_profile_subpages_nav_enabled()
 */
function clanspress_team_profile_subpages_nav_enabled(): bool {
	return clanspress_profile_subpages_nav_enabled( 'team' );
}

/**
 * @see clanspress_profile_subpages_nav_enabled()
 */
function clanspress_group_profile_subpages_nav_enabled(): bool {
	return clanspress_profile_subpages_nav_enabled( 'group' );
}
