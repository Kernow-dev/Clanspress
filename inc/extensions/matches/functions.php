<?php
/**
 * Procedural helpers for the Matches extension (themes and integrations).
 *
 * @package clanspress
 */

use Kernowdev\Clanspress\Extensions\Matches;

/**
 * Resolve the running Matches extension from the loader.
 *
 * @return Matches|null Null when the extension is not installed or not booted.
 */
function clanspress_matches(): ?Matches {
	$loader = clanspress()->extensions;
	if ( null === $loader ) {
		return null;
	}

	$ext = $loader->get( 'cp_matches' );

	return $ext instanceof Matches ? $ext : null;
}

/**
 * Human-readable title for a `cp_team` post ID.
 *
 * @param int $team_id Team post ID (`cp_team`).
 * @return string Empty string when the post is missing or not a team.
 */
function clanspress_matches_team_title( int $team_id ): string {
	if ( $team_id <= 0 ) {
		return '';
	}
	$post = get_post( $team_id );
	if ( ! $post || 'cp_team' !== $post->post_type ) {
		return '';
	}

	return get_the_title( $post );
}

/**
 * Away-side label for a match, using a local `cp_team` or external challenge metadata.
 *
 * @param int $match_id Match post ID.
 * @return string
 */
function clanspress_matches_resolve_away_team_title( int $match_id ): string {
	$away = (int) get_post_meta( $match_id, 'cp_match_away_team_id', true );
	if ( $away > 0 ) {
		return clanspress_matches_team_title( $away );
	}

	return sanitize_text_field( (string) get_post_meta( $match_id, 'cp_match_away_external_label', true ) );
}

/**
 * Format a stored GMT datetime string for display using the site timezone.
 *
 * @param string $mysql_gmt Datetime in GMT (MySQL-compatible string).
 * @param string $format    PHP date format passed to `wp_date()`.
 * @return string Empty string when the value cannot be parsed.
 */
function clanspress_matches_format_datetime_local( string $mysql_gmt, string $format ): string {
	if ( '' === trim( $mysql_gmt ) ) {
		return '';
	}
	$ts = strtotime( $mysql_gmt . ' UTC' );
	if ( false === $ts ) {
		return '';
	}

	return wp_date( $format, $ts );
}

/**
 * Map match status keys to localized labels.
 *
 * @return array<string, string> Status slug => translated label.
 */
function clanspress_matches_status_labels(): array {
	return array(
		'scheduled' => __( 'Scheduled', 'clanspress' ),
		'live'      => __( 'Live', 'clanspress' ),
		'finished'  => __( 'Finished', 'clanspress' ),
		'cancelled' => __( 'Cancelled', 'clanspress' ),
	);
}
