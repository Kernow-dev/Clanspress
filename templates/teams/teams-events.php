<?php
/**
 * Front template: team events list (classic / hybrid themes).
 *
 * Block markup: `teams-events.html` (also registered for FSE as `clanspress//teams-events`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/teams-events.html' );
}

get_footer();
