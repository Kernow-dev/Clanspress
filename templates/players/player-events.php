<?php
/**
 * Front template: player events subpage (`/players/{nicename}/events/`).
 *
 * Block markup: `player-events.html` (registered for FSE as `clanspress//player-events`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/player-events.html' );
}

get_footer();
