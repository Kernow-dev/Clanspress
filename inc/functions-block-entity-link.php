<?php
/**
 * Default permalinks and attributes for “link to entity” block options.
 *
 * @package clanspress
 */

/**
 * Resolved profile URL for a user (player blocks).
 *
 * @param int $user_id User ID.
 * @return string URL or empty.
 */
function clanspress_block_player_profile_url( int $user_id ): string {
	if ( $user_id < 1 ) {
		return '';
	}

	if ( function_exists( 'clanspress_get_player_profile_url' ) ) {
		$url = clanspress_get_player_profile_url( $user_id );

		return $url ? (string) $url : '';
	}

	return get_author_posts_url( $user_id );
}

/**
 * Build rel="" for a block link (noopener/noreferrer for new tab + user rel).
 *
 * @param array<string, mixed> $attributes Block attributes (`linkTarget`, `rel`).
 * @return string Space-separated rel tokens.
 */
function clanspress_block_entity_link_rel( array $attributes ): string {
	$target = isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ? '_blank' : '';
	$rel    = isset( $attributes['rel'] ) ? trim( (string) $attributes['rel'] ) : '';

	$parts = array();
	if ( '' !== $rel ) {
		foreach ( preg_split( '/\s+/', $rel, -1, PREG_SPLIT_NO_EMPTY ) as $p ) {
			$p = sanitize_text_field( $p );
			if ( '' !== $p ) {
				$parts[] = $p;
			}
		}
	}

	if ( '_blank' === $target ) {
		$parts[] = 'noopener';
		$parts[] = 'noreferrer';
	}

	return implode( ' ', array_unique( array_filter( $parts ) ) );
}

/**
 * Filterable URL used when a block’s “link” option is enabled.
 *
 * @param string               $url        Default URL.
 * @param string               $block_name Block name, e.g. `clanspress/player-display-name`.
 * @param int                  $entity_id  User ID or team post ID.
 * @param \WP_Block|null       $block      Block instance.
 * @return string
 */
function clanspress_block_entity_link_url( string $url, string $block_name, int $entity_id, $block = null ): string {
	/**
	 * Filter permalink used for linked player/team blocks.
	 *
	 * @param string         $url        URL.
	 * @param string         $block_name Block name.
	 * @param int            $entity_id  User or team post ID.
	 * @param \WP_Block|null $block      Block instance.
	 */
	return (string) apply_filters( 'clanspress_block_entity_link_url', $url, $block_name, $entity_id, $block );
}
