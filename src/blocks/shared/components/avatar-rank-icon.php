<?php
/**
 * Avatar Rank Icon Component
 *
 * Renders rank icon overlay on avatars, positioned according to avatar shape.
 * Shows when Ranks plugin is active and configured to show icons.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render avatar rank icon overlay for a user.
 *
 * @param int    $user_id User ID.
 * @param string $shape   Avatar shape: 'circle', 'square', or 'hexagon'.
 * @return string Rank icon HTML or empty string if not applicable.
 */
function clanbite_render_avatar_rank_icon( int $user_id, string $shape = 'circle' ): string {
	// Check if Ranks plugin is active
	if ( ! function_exists( 'clanbite_ranks_extension_active' ) || ! clanbite_ranks_extension_active() ) {
		return '';
	}

	// Check if rank icon should be shown on avatar
	if ( ! function_exists( 'clanbite_ranks_show_icon_on_avatar' ) || ! clanbite_ranks_show_icon_on_avatar() ) {
		return '';
	}

	// Get user's rank icon
	$rank_icon = '';
	if ( function_exists( 'clanbite_ranks_get_rank_icon' ) ) {
		$rank_icon = clanbite_ranks_get_rank_icon( $user_id );
	}

	// No icon to display
	if ( empty( $rank_icon ) ) {
		return '';
	}

	// Get rank name for alt text
	$rank_name = '';
	if ( function_exists( 'clanbite_ranks_get_user_rank_name' ) ) {
		$rank_name = clanbite_ranks_get_user_rank_name( $user_id );
	}

	ob_start();
	?>
	<div class="clanbite-avatar__rank-icon" aria-hidden="true">
		<?php
		// Check if $rank_icon is an image URL or SVG markup
		if ( preg_match( '/^<svg/i', $rank_icon ) ) {
			// It's SVG markup - output directly
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG from Ranks plugin, assumed sanitized
			echo $rank_icon;
		} elseif ( filter_var( $rank_icon, FILTER_VALIDATE_URL ) ) {
			// It's an image URL
			?>
			<img 
				src="<?php echo esc_url( $rank_icon ); ?>" 
				alt="<?php echo esc_attr( $rank_name ? sprintf( __( '%s rank', 'clanbite' ), $rank_name ) : __( 'Rank badge', 'clanbite' ) ); ?>"
				loading="lazy"
			/>
			<?php
		} else {
			// Fallback: treat as escaped HTML
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rank icon from plugin
			echo $rank_icon;
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}
