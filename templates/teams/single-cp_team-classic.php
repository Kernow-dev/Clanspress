<?php
/**
 * Single team template for classic (non-block) themes.
 *
 * Renders the same block markup as the FSE template (`single-cp_team.php`) via `do_blocks()`
 * so team blocks resolve the current post in the loop.
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) {
	the_post();

	$markup_path = clanspress()->path . 'templates/teams/single-cp_team.php';
	if ( ! is_readable( $markup_path ) ) {
		the_content();
		continue;
	}

	$markup = file_get_contents( $markup_path );
	if ( false === $markup ) {
		the_content();
		continue;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo do_blocks( $markup );
}

get_footer();
