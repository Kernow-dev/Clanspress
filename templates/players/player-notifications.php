<?php
/**
 * Front template: player notifications subpage (`/players/{nicename}/notifications/`).
 *
 * Block markup: `player-notifications.html` (registered for FSE as `clanspress//player-notifications`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/player-notifications.html' );
}

get_footer();
