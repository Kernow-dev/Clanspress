<?php
/**
 * Front template: manage team (classic / hybrid themes).
 *
 * Block markup: `teams-manage.html` (also registered for FSE as `clanspress//teams-manage`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/teams-manage.html' );
}

get_footer();
