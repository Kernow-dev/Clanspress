<?php
/**
 * Front template: single team event (classic / hybrid themes).
 *
 * Block markup: `teams-events-single.html` (also registered for FSE as `clanspress//teams-events-single`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/teams-events-single.html' );
}

get_footer();
