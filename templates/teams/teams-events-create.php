<?php
/**
 * Front template: create team event (classic / hybrid themes).
 *
 * Block markup: `teams-events-create.html` (FSE: `clanspress//teams-events-create`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/teams-events-create.html' );
}

get_footer();
