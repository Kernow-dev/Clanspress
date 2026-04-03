<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.SlowDBQuery -- `meta_key` / `meta_query` / `meta_value` are required for player directory filters; keep queries scoped and paginated.

/**
 * Player Query block: roster subset, ordering, meta filters, and exclusions.
 *
 * @package clanspress
 */

/**
 * Parse a comma- or whitespace-separated list of positive integers (e.g. user IDs).
 *
 * @param string $raw Raw list from block attributes.
 * @return list<int>
 */
function clanspress_player_query_parse_id_list( string $raw ): array {
	$raw   = trim( $raw );
	$parts = preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
	$out   = array();

	foreach ( $parts as $p ) {
		$id = (int) $p;
		if ( $id > 0 ) {
			$out[] = $id;
		}
	}

	return array_values( array_unique( $out ) );
}

/**
 * Parse comma-separated roster role slugs for exclusion (e.g. `admin,member`).
 *
 * @param string $raw Raw list from block attributes.
 * @return list<string>
 */
function clanspress_player_query_parse_role_slugs( string $raw ): array {
	$raw   = trim( $raw );
	$parts = preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
	$out   = array();

	foreach ( $parts as $p ) {
		$slug = sanitize_key( (string) $p );
		if ( '' !== $slug ) {
			$out[] = $slug;
		}
	}

	return array_values( array_unique( $out ) );
}

/**
 * Whether an array looks like a list of meta clauses (numeric keys) or a relation group.
 *
 * @param array<string|int, mixed> $node Meta query node.
 * @return bool
 */
function clanspress_player_query_meta_query_is_indexed_clause_list( array $node ): bool {
	$found = false;

	foreach ( $node as $k => $v ) {
		if ( 'relation' === $k ) {
			continue;
		}
		if ( ! is_int( $k ) && ! ( is_string( $k ) && ctype_digit( (string) $k ) ) ) {
			return false;
		}
		if ( ! is_array( $v ) ) {
			return false;
		}
		$found = true;
	}

	return $found;
}

/**
 * Ensure meta_query shape expected by {@see \WP_User_Query} / {@see \WP_Meta_Query}.
 *
 * @param array<string|int, mixed> $meta_query Sanitized meta query.
 * @return array<int|string, mixed>
 */
function clanspress_player_query_meta_query_for_wp_user_query( array $meta_query ): array {
	if ( array() === $meta_query ) {
		return array();
	}

	if ( isset( $meta_query['key'] ) ) {
		return array( $meta_query );
	}

	return $meta_query;
}

/**
 * Sanitize one meta_query leaf for {@see \WP_User_Query}.
 *
 * @param array<string, mixed> $node Raw clause.
 * @return array<string, mixed>
 */
function clanspress_player_query_sanitize_meta_query_clause( array $node ): array {
	$allowed_compare = array( '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS', 'REGEXP', 'RLIKE' );
	$allowed_type    = array( 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'UNSIGNED', 'TIME' );

	if ( ! isset( $node['key'] ) ) {
		return array();
	}

	$key = sanitize_text_field( (string) $node['key'] );
	if ( strlen( $key ) < 1 || strlen( $key ) > 191 ) {
		return array();
	}

	$out = array( 'key' => $key );

	$compare = '=';
	if ( isset( $node['compare'] ) && is_string( $node['compare'] ) ) {
		$c = strtoupper( trim( $node['compare'] ) );
		if ( in_array( $c, $allowed_compare, true ) ) {
			$compare = $c;
		}
	}
	$out['compare'] = $compare;

	if ( isset( $node['type'] ) && is_string( $node['type'] ) ) {
		$t = strtoupper( $node['type'] );
		if ( in_array( $t, $allowed_type, true ) ) {
			$out['type'] = $t;
		}
	}

	if ( in_array( $compare, array( 'EXISTS', 'NOT EXISTS' ), true ) ) {
		return $out;
	}

	if ( array_key_exists( 'value', $node ) ) {
		$out['value'] = clanspress_player_query_sanitize_meta_value( $node['value'], $compare );
	}

	return $out;
}

/**
 * Normalize meta `value` for a compare operator.
 *
 * @param mixed  $value   Raw value.
 * @param string $compare Compare operator.
 * @return mixed
 */
function clanspress_player_query_sanitize_meta_value( $value, string $compare ) {
	if ( in_array( $compare, array( 'IN', 'NOT IN' ), true ) && is_array( $value ) ) {
		$clean = array();
		foreach ( array_slice( $value, 0, 100 ) as $v ) {
			if ( is_int( $v ) || ( is_string( $v ) && is_numeric( $v ) && (string) (int) $v === (string) $v ) ) {
				$clean[] = (int) $v;
			} elseif ( is_float( $v ) || ( is_string( $v ) && is_numeric( $v ) ) ) {
				$clean[] = (float) $v;
			} else {
				$clean[] = sanitize_text_field( (string) $v );
			}
		}

		return $clean;
	}

	if ( in_array( $compare, array( 'BETWEEN', 'NOT BETWEEN' ), true ) && is_array( $value ) && isset( $value[0], $value[1] ) ) {
		return array( sanitize_text_field( (string) $value[0] ), sanitize_text_field( (string) $value[1] ) );
	}

	if ( is_array( $value ) ) {
		return '';
	}

	if ( is_int( $value ) ) {
		return $value;
	}

	if ( is_float( $value ) ) {
		return $value;
	}

	if ( is_string( $value ) && is_numeric( $value ) ) {
		return strpos( $value, '.' ) !== false ? (float) $value : (int) $value;
	}

	return sanitize_text_field( (string) $value );
}

/**
 * Sanitize a meta_query tree (relation groups and leaf clauses) for {@see \WP_User_Query}.
 *
 * @param mixed $node  Decoded JSON or array.
 * @param int   $depth Recursion guard.
 * @return array<string|int, mixed>
 */
function clanspress_player_query_sanitize_meta_query( $node, int $depth = 0 ): array {
	if ( $depth > 5 || ! is_array( $node ) || array() === $node ) {
		return array();
	}

	if ( isset( $node['relation'] ) || clanspress_player_query_meta_query_is_indexed_clause_list( $node ) ) {
		$out = array();
		if ( isset( $node['relation'] ) ) {
			$rel = strtoupper( sanitize_text_field( (string) $node['relation'] ) );
			if ( in_array( $rel, array( 'AND', 'OR' ), true ) ) {
				$out['relation'] = $rel;
			}
		}

		foreach ( $node as $k => $clause ) {
			if ( 'relation' === $k || ! is_array( $clause ) ) {
				continue;
			}

			$child = clanspress_player_query_sanitize_meta_query( $clause, $depth + 1 );
			if ( array() !== $child ) {
				$out[] = $child;
			}
		}

		$clause_count = 0;
		foreach ( $out as $k => $v ) {
			if ( 'relation' !== $k ) {
				++$clause_count;
			}
		}

		if ( $clause_count < 1 ) {
			return array();
		}

		return $out;
	}

	return clanspress_player_query_sanitize_meta_query_clause( $node );
}

/**
 * Decode and sanitize a meta_query JSON string from block attributes.
 *
 * @param string $json JSON object or array string.
 * @return array<string|int, mixed>
 */
function clanspress_player_query_meta_query_from_json( string $json ): array {
	$json = trim( $json );
	if ( '' === $json ) {
		return array();
	}

	$decoded = json_decode( $json, true );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
		return array();
	}

	return clanspress_player_query_sanitize_meta_query( $decoded );
}

/**
 * Build normalized player-query options from ancestor `clanspress/player-query` block context.
 *
 * @param array<string, mixed> $context Block context.
 * @return array<string, mixed>
 */
function clanspress_player_query_options_from_block_context( array $context ): array {
	$orderby = isset( $context['clanspress/queryOrderby'] ) ? sanitize_key( (string) $context['clanspress/queryOrderby'] ) : 'default';
	if ( '' === $orderby ) {
		$orderby = 'default';
	}

	$order = isset( $context['clanspress/queryOrder'] ) ? strtoupper( sanitize_text_field( (string) $context['clanspress/queryOrder'] ) ) : 'ASC';
	if ( 'DESC' !== $order ) {
		$order = 'ASC';
	}

	return array(
		'orderby'                 => $orderby,
		'order'                   => $order,
		'meta_key'                => isset( $context['clanspress/queryMetaKey'] ) ? sanitize_text_field( (string) $context['clanspress/queryMetaKey'] ) : '',
		'per_page'                => isset( $context['clanspress/queryPerPage'] ) ? max( 0, (int) $context['clanspress/queryPerPage'] ) : 0,
		'offset'                  => isset( $context['clanspress/queryOffset'] ) ? max( 0, (int) $context['clanspress/queryOffset'] ) : 0,
		'meta_query_json'         => isset( $context['clanspress/queryMetaQueryJson'] ) ? (string) $context['clanspress/queryMetaQueryJson'] : '',
		'exclude_users'           => isset( $context['clanspress/queryExcludeUsers'] ) ? (string) $context['clanspress/queryExcludeUsers'] : '',
		'exclude_current_user'    => ! empty( $context['clanspress/queryExcludeCurrentUser'] ),
		'exclude_roles'           => isset( $context['clanspress/queryExcludeRoles'] ) ? (string) $context['clanspress/queryExcludeRoles'] : '',
		'exclude_meta_query_json' => isset( $context['clanspress/queryExcludeMetaQueryJson'] ) ? (string) $context['clanspress/queryExcludeMetaQueryJson'] : '',
	);
}

/**
 * Map block orderby slug to a {@see \WP_User_Query} `orderby` value.
 *
 * @param string $slug Block attribute value.
 * @return string|null Core query value, or null when ordering is handled without WP_User_Query.
 */
function clanspress_player_query_wp_user_query_orderby( string $slug ): ?string {
	$map = array(
		'id'             => 'ID',
		'display_name'   => 'display_name',
		'login'          => 'user_login',
		'nicename'       => 'user_nicename',
		'email'          => 'user_email',
		'url'            => 'user_url',
		'registered'     => 'user_registered',
		'post_count'     => 'post_count',
		'meta_value'     => 'meta_value',
		'meta_value_num' => 'meta_value_num',
		'rand'           => 'rand',
		'include'        => 'include',
		'roster'         => 'include',
	);

	return isset( $map[ $slug ] ) ? $map[ $slug ] : null;
}

/**
 * Resolve roster user IDs for the player template using team context and query options.
 *
 * Team membership always applies first; options narrow, exclude, order, and paginate that set.
 *
 * @param int                  $team_id         Team post ID.
 * @param bool                 $exclude_banned  Whether to omit banned roster members.
 * @param array<string, mixed> $query_options   From {@see clanspress_player_query_options_from_block_context()}.
 * @param \WP_Block|null       $block           Optional block instance for filters.
 * @return list<int>
 */
function clanspress_player_query_resolve_member_user_ids( int $team_id, bool $exclude_banned, array $query_options, $block = null ): array {
	if ( $team_id < 1 || ! function_exists( 'clanspress_teams_get_roster_user_ids' ) ) {
		return array();
	}

	$orderby_slug = isset( $query_options['orderby'] ) ? (string) $query_options['orderby'] : 'default';
	$preserve     = in_array( $orderby_slug, array( 'include', 'roster' ), true );

	$user_ids = clanspress_teams_get_roster_user_ids( $team_id, $exclude_banned, $preserve );
	if ( array() === $user_ids ) {
		return array();
	}

	// Exclude explicit user IDs (like Query Loop “Exclude” posts).
	$exclude_ids = clanspress_player_query_parse_id_list( (string) ( $query_options['exclude_users'] ?? '' ) );
	if ( ! empty( $query_options['exclude_current_user'] ) && is_user_logged_in() ) {
		$exclude_ids[] = (int) get_current_user_id();
	}
	$exclude_ids = array_values( array_unique( array_filter( array_map( 'intval', $exclude_ids ) ) ) );
	if ( array() !== $exclude_ids ) {
		$user_ids = array_values(
			array_diff( array_map( 'intval', $user_ids ), $exclude_ids )
		);
	}

	// Exclude by team roster role slug (additional to “exclude banned”).
	$exclude_roles = clanspress_player_query_parse_role_slugs( (string) ( $query_options['exclude_roles'] ?? '' ) );
	if ( array() !== $exclude_roles && function_exists( 'clanspress_teams_get_member_roles_map' ) ) {
		$map    = clanspress_teams_get_member_roles_map( $team_id );
		$keep   = array();
		$bad    = array_flip( $exclude_roles );
		foreach ( $user_ids as $uid ) {
			$uid  = (int) $uid;
			$role = isset( $map[ $uid ] ) ? sanitize_key( (string) $map[ $uid ] ) : '';
			if ( '' !== $role && isset( $bad[ $role ] ) ) {
				continue;
			}
			$keep[] = $uid;
		}
		$user_ids = $keep;
	}

	// Exclude members matching a meta_query (removed from the roster set).
	$exclude_meta = clanspress_player_query_meta_query_for_wp_user_query(
		clanspress_player_query_meta_query_from_json( (string) ( $query_options['exclude_meta_query_json'] ?? '' ) )
	);
	if ( array() !== $exclude_meta && array() !== $user_ids ) {
		$q = new \WP_User_Query(
			array(
				'include'    => $user_ids,
				'fields'     => 'ID',
				'meta_query' => $exclude_meta,
			)
		);
		$remove = array_map( 'intval', (array) $q->get_results() );
		if ( array() !== $remove ) {
			$user_ids = array_values( array_diff( $user_ids, $remove ) );
		}
	}

	// Filter to members matching include meta_query.
	$include_meta = clanspress_player_query_meta_query_for_wp_user_query(
		clanspress_player_query_meta_query_from_json( (string) ( $query_options['meta_query_json'] ?? '' ) )
	);
	if ( array() !== $include_meta && array() !== $user_ids ) {
		$q = new \WP_User_Query(
			array(
				'include'    => $user_ids,
				'fields'     => 'ID',
				'meta_query' => $include_meta,
			)
		);
		$user_ids = array_map( 'intval', (array) $q->get_results() );
	}

	$offset   = isset( $query_options['offset'] ) ? max( 0, (int) $query_options['offset'] ) : 0;
	$per_page = isset( $query_options['per_page'] ) ? max( 0, (int) $query_options['per_page'] ) : 0;
	$order    = isset( $query_options['order'] ) && 'DESC' === $query_options['order'] ? 'DESC' : 'ASC';
	$meta_key = isset( $query_options['meta_key'] ) ? sanitize_text_field( (string) $query_options['meta_key'] ) : '';

	if ( 'default' === $orderby_slug || '' === $orderby_slug ) {
		if ( $offset > 0 ) {
			$user_ids = array_slice( $user_ids, $offset );
		}
		if ( $per_page > 0 ) {
			$user_ids = array_slice( $user_ids, 0, $per_page );
		}

		/**
		 * Filter member user IDs after resolving the player query (team roster + exclusions + meta + default ordering).
		 *
		 * @param list<int>            $user_ids      User IDs in display order.
		 * @param int                  $team_id       Team post ID.
		 * @param array<string, mixed> $query_options Normalized query options.
		 * @param \WP_Block|null       $block         Player template block, if available.
		 */
		return array_values(
			array_map(
				'intval',
				(array) apply_filters( 'clanspress_player_query_member_user_ids', $user_ids, $team_id, $query_options, $block )
			)
		);
	}

	$wp_orderby = clanspress_player_query_wp_user_query_orderby( $orderby_slug );
	if ( null === $wp_orderby || array() === $user_ids ) {
		if ( $offset > 0 ) {
			$user_ids = array_slice( $user_ids, $offset );
		}
		if ( $per_page > 0 ) {
			$user_ids = array_slice( $user_ids, 0, $per_page );
		}

		return array_values(
			array_map(
				'intval',
				(array) apply_filters( 'clanspress_player_query_member_user_ids', $user_ids, $team_id, $query_options, $block )
			)
		);
	}

	if ( in_array( $wp_orderby, array( 'meta_value', 'meta_value_num' ), true ) && '' === $meta_key ) {
		// Cannot sort by meta without a key; fall back to default ordering path.
		if ( $offset > 0 ) {
			$user_ids = array_slice( $user_ids, $offset );
		}
		if ( $per_page > 0 ) {
			$user_ids = array_slice( $user_ids, 0, $per_page );
		}

		return array_values(
			array_map(
				'intval',
				(array) apply_filters( 'clanspress_player_query_member_user_ids', $user_ids, $team_id, $query_options, $block )
			)
		);
	}

	$args = array(
		'include' => $user_ids,
		'orderby' => $wp_orderby,
		'order'   => $order,
		'fields'  => 'ID',
	);

	if ( in_array( $wp_orderby, array( 'meta_value', 'meta_value_num' ), true ) ) {
		$args['meta_key'] = $meta_key;
	}

	if ( $per_page > 0 ) {
		$args['number'] = $per_page;
	}
	if ( $offset > 0 ) {
		$args['offset'] = $offset;
	}

	$q        = new \WP_User_Query( $args );
	$user_ids = array_map( 'intval', (array) $q->get_results() );

	return array_values(
		array_map(
			'intval',
			(array) apply_filters( 'clanspress_player_query_member_user_ids', $user_ids, $team_id, $query_options, $block )
		)
	);
}

// phpcs:enable WordPress.DB.SlowDBQuery
