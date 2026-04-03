<?php
/**
 * Front template: create team (classic / hybrid themes).
 *
 * Block markup: `teams-create.html` (also registered for FSE as `clanspress//teams-create`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/teams-create.html' );
}

get_footer();
