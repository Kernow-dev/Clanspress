<?php
/**
 * Front template: player settings (classic / hybrid themes).
 *
 * Block markup: `player-settings.html` (also registered for FSE as `clanspress//player-settings`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/player-settings.html' );
}

get_footer();
