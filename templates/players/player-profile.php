<?php
/**
 * Front template: player profile / author archive (classic / hybrid themes).
 *
 * Block markup: `player-profile.html` (also registered for FSE as `clanspress//players-player-profile`).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/player-profile.html' );
}

get_footer();
