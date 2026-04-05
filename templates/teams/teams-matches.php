<?php
/**
 * Front template: team matches list (classic / hybrid themes).
 *
 * Block markup: `teams-matches.html` (also registered for FSE as `clanspress//teams-matches`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/teams-matches.html' );
}

get_footer();
