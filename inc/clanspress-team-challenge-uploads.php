<?php
/**
 * Team challenge logo upload paths under uploads/clanspress/teams/…/matches/….
 *
 * Logos are stored in a staging folder until a match exists, then moved to the match folder on accept.
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post meta on attachments uploaded via {@see Team_Challenges::rest_upload_challenge_media()}.
 * Value is the challenged `cp_team` ID (string) the upload was scoped to.
 */
const CLANSPRESS_TEAM_CHALLENGE_LOGO_TEAM_META = '_clanspress_team_challenge_logo';

/**
 * Relative uploads path (no leading slash): clanspress/teams/{team_id}/matches/{match_id}
 *
 * @param int $team_id  Challenged team post ID on this site.
 * @param int $match_id Match post ID.
 * @return string
 */
function clanspress_team_match_logo_relative_dir( int $team_id, int $match_id ): string {
	return 'clanspress/teams/' . max( 0, $team_id ) . '/matches/' . max( 0, $match_id );
}

/**
 * Staging relative dir before a match exists: clanspress/teams/{team_id}/matches/staging
 *
 * @param int $challenged_team_id Challenged `cp_team` ID.
 * @return string
 */
function clanspress_team_challenge_logo_staging_relative_dir( int $challenged_team_id ): string {
	return 'clanspress/teams/' . max( 0, $challenged_team_id ) . '/matches/staging';
}

/**
 * Run a callback with `upload_dir` forced to a subdirectory of the uploads base (path + url + subdir).
 *
 * @param string   $relative_dir Path under uploads base, e.g. `clanspress/teams/1/matches/staging` (no leading slash).
 * @param callable $callback     Invoked while the filter is active.
 * @return mixed Return value of `$callback`.
 */
function clanspress_with_upload_subdir( string $relative_dir, callable $callback ) {
	$relative_dir = trim( str_replace( '\\', '/', $relative_dir ), '/' );
	$filter       = static function ( array $uploads ) use ( $relative_dir ): array {
		if ( ! empty( $uploads['error'] ) ) {
			return $uploads;
		}
		$subdir            = '/' . $relative_dir;
		$uploads['subdir'] = $subdir;
		$uploads['path']   = $uploads['basedir'] . $subdir;
		$uploads['url']    = $uploads['baseurl'] . $subdir;
		return $uploads;
	};

	add_filter( 'upload_dir', $filter, 99 );
	$out = $callback();
	remove_filter( 'upload_dir', $filter, 99 );

	return $out;
}

/**
 * Whether an attachment was uploaded as a team-challenge logo for the given challenged team.
 *
 * @param int $attachment_id      Attachment ID.
 * @param int $challenged_team_id Expected challenged team ID.
 * @return bool
 */
function clanspress_team_challenge_logo_attachment_matches_team( int $attachment_id, int $challenged_team_id ): bool {
	if ( $attachment_id < 1 || $challenged_team_id < 1 ) {
		return false;
	}
	$stored = get_post_meta( $attachment_id, CLANSPRESS_TEAM_CHALLENGE_LOGO_TEAM_META, true );

	return (string) $challenged_team_id === (string) $stored;
}

/**
 * Move a challenge-logo attachment into the match directory and refresh metadata.
 *
 * @param int $attachment_id      Attachment to move.
 * @param int $challenged_team_id Challenged team ID (must match {@see CLANSPRESS_TEAM_CHALLENGE_LOGO_TEAM_META}).
 * @param int $match_id           New match ID.
 * @return bool True when the file now lives under the match directory (or already did).
 */
function clanspress_relocate_team_challenge_logo_to_match_dir( int $attachment_id, int $challenged_team_id, int $match_id ): bool {
	if ( $attachment_id < 1 || $match_id < 1 || ! clanspress_team_challenge_logo_attachment_matches_team( $attachment_id, $challenged_team_id ) ) {
		return false;
	}

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return false;
	}

	$old_path = get_attached_file( $attachment_id );
	if ( ! is_string( $old_path ) || '' === $old_path || ! file_exists( $old_path ) ) {
		return false;
	}

	$relative_dir = clanspress_team_match_logo_relative_dir( $challenged_team_id, $match_id );
	$dest_dir     = path_join( $uploads['basedir'], $relative_dir );
	if ( ! wp_mkdir_p( $dest_dir ) ) {
		return false;
	}

	$filename   = wp_basename( $old_path );
	$dest_path  = path_join( $dest_dir, wp_unique_filename( $dest_dir, $filename ) );
	$old_norm   = wp_normalize_path( $old_path );
	$dest_norm  = wp_normalize_path( $dest_path );

	if ( $old_norm === $dest_norm ) {
		return true;
	}

	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( is_array( $meta ) && ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		$old_dir = dirname( $old_path );
		foreach ( $meta['sizes'] as $size ) {
			if ( empty( $size['file'] ) || ! is_string( $size['file'] ) ) {
				continue;
			}
			$thumb = path_join( $old_dir, $size['file'] );
			if ( is_string( $thumb ) && file_exists( $thumb ) ) {
				wp_delete_file( $thumb );
			}
		}
	}

	if ( ! @rename( $old_path, $dest_path ) ) {
		if ( ! @copy( $old_path, $dest_path ) ) {
			return false;
		}
		wp_delete_file( $old_path );
	}

	$relative_file = $relative_dir . '/' . wp_basename( $dest_path );
	wp_update_attached_file( $attachment_id, $relative_file );
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $dest_path ) );

	return true;
}
