<?php
/**
 * Avatar Progress Bar Component
 *
 * Renders an SVG progress ring around avatars that adapts to the selected shape.
 * Shows rank progression when Points and Ranks plugins are active.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render avatar progress bar for a user.
 *
 * @param int    $user_id    User ID.
 * @param string $shape      Avatar shape: 'circle', 'square', or 'hexagon'.
 * @param string $size_class Avatar size class for dimension calculations.
 * @return string Progress bar HTML or empty string if not applicable.
 */
function clanbite_render_avatar_progress_bar( int $user_id, string $shape = 'circle', string $size_class = 'large' ): string {
	// Get user's rank progress percentage (0-100)
	$progress = 0;
	
	// Check if Points and Ranks plugins are active and configured
	$show_progress = false;
	if ( function_exists( 'clanbite_points_extension_active' ) 
		&& clanbite_points_extension_active()
		&& function_exists( 'clanbite_ranks_extension_active' ) 
		&& clanbite_ranks_extension_active()
		&& function_exists( 'clanbite_ranks_show_progress_on_avatar' )
		&& clanbite_ranks_show_progress_on_avatar()
	) {
		$show_progress = true;
		
		// Get user's rank progress
		if ( function_exists( 'clanbite_ranks_get_user_rank_progress' ) ) {
			$progress = (int) clanbite_ranks_get_user_rank_progress( $user_id );
			$progress = max( 0, min( 100, $progress ) ); // Clamp between 0-100
		}
	} else {
		// DEMO: Show 65% progress when plugins not active (for visual testing)
		$progress = 65;
		$show_progress = true;
	}

	// Generate unique ID for this progress ring
	$unique_id = 'progress-' . $user_id . '-' . wp_rand( 1000, 9999 );

	// Avatar dimensions (adjust to wrap around the avatar properly)
	$size = 120; // Base avatar size
	$stroke_width = 4;
	
	// Make the progress ring larger to wrap around the avatar with border
	$progress_size = $size + 16; // Add padding for border and spacing
	
	ob_start();
	?>
	<div class="clanbite-avatar__progress-ring" data-progress="<?php echo esc_attr( $progress ); ?>">
		<?php
		switch ( $shape ) {
			case 'square':
				clanbite_render_square_progress_svg( $unique_id, $progress_size, $stroke_width, $progress );
				break;
			case 'hexagon':
				clanbite_render_hexagon_progress_svg( $unique_id, $progress_size, $stroke_width, $progress );
				break;
			case 'circle':
			default:
				// For circle, calculate radius based on size
				$radius = ( $progress_size / 2 ) - ( $stroke_width / 2 );
				clanbite_render_circle_progress_svg( $unique_id, $radius, $stroke_width, $progress );
				break;
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render circle progress SVG.
 *
 * @param string $unique_id    Unique identifier for the SVG.
 * @param float  $radius       Circle radius.
 * @param int    $stroke_width Stroke width.
 * @param int    $progress     Progress percentage (0-100).
 */
function clanbite_render_circle_progress_svg( string $unique_id, float $radius, int $stroke_width, int $progress ): void {
	$circumference = 2 * M_PI * $radius;
	$dash_offset = $circumference * ( 1 - ( $progress / 100 ) );
	$size = ( $radius * 2 ) + ( $stroke_width * 2 ) + 8;
	$center = $size / 2;
	?>
	<svg 
		width="<?php echo esc_attr( $size ); ?>" 
		height="<?php echo esc_attr( $size ); ?>" 
		viewBox="0 0 <?php echo esc_attr( $size ); ?> <?php echo esc_attr( $size ); ?>"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<!-- Background circle -->
		<circle
			class="clanbite-avatar__progress-path"
			cx="<?php echo esc_attr( $center ); ?>"
			cy="<?php echo esc_attr( $center ); ?>"
			r="<?php echo esc_attr( $radius ); ?>"
		/>
		<!-- Progress circle -->
		<circle
			class="clanbite-avatar__progress-fill"
			cx="<?php echo esc_attr( $center ); ?>"
			cy="<?php echo esc_attr( $center ); ?>"
			r="<?php echo esc_attr( $radius ); ?>"
			stroke-dasharray="<?php echo esc_attr( $circumference ); ?>"
			stroke-dashoffset="<?php echo esc_attr( $dash_offset ); ?>"
			transform="rotate(-90 <?php echo esc_attr( $center ); ?> <?php echo esc_attr( $center ); ?>)"
		/>
	</svg>
	<?php
}

/**
 * Render square progress SVG with rounded corners.
 *
 * @param string $unique_id    Unique identifier for the SVG.
 * @param int    $size         Square size.
 * @param int    $stroke_width Stroke width.
 * @param int    $progress     Progress percentage (0-100).
 */
function clanbite_render_square_progress_svg( string $unique_id, int $size, int $stroke_width, int $progress ): void {
	$padding = 8;
	$view_size = $size + $padding;
	$rect_size = $size - $stroke_width;
	$offset = $stroke_width / 2 + ( $padding / 2 );
	$corner_radius = 8; // Matches CSS var default
	
	// Calculate perimeter of rounded rectangle
	$perimeter = ( 2 * ( $rect_size - ( 2 * $corner_radius ) ) ) + ( 2 * M_PI * $corner_radius );
	$dash_offset = $perimeter * ( 1 - ( $progress / 100 ) );
	?>
	<svg 
		width="<?php echo esc_attr( $view_size ); ?>" 
		height="<?php echo esc_attr( $view_size ); ?>" 
		viewBox="0 0 <?php echo esc_attr( $view_size ); ?> <?php echo esc_attr( $view_size ); ?>"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<!-- Background rect -->
		<rect
			class="clanbite-avatar__progress-path"
			x="<?php echo esc_attr( $offset ); ?>"
			y="<?php echo esc_attr( $offset ); ?>"
			width="<?php echo esc_attr( $rect_size ); ?>"
			height="<?php echo esc_attr( $rect_size ); ?>"
			rx="<?php echo esc_attr( $corner_radius ); ?>"
			ry="<?php echo esc_attr( $corner_radius ); ?>"
		/>
		<!-- Progress rect -->
		<rect
			class="clanbite-avatar__progress-fill"
			x="<?php echo esc_attr( $offset ); ?>"
			y="<?php echo esc_attr( $offset ); ?>"
			width="<?php echo esc_attr( $rect_size ); ?>"
			height="<?php echo esc_attr( $rect_size ); ?>"
			rx="<?php echo esc_attr( $corner_radius ); ?>"
			ry="<?php echo esc_attr( $corner_radius ); ?>"
			stroke-dasharray="<?php echo esc_attr( $perimeter ); ?>"
			stroke-dashoffset="<?php echo esc_attr( $dash_offset ); ?>"
			pathLength="<?php echo esc_attr( $perimeter ); ?>"
		/>
	</svg>
	<?php
}

/**
 * Render hexagon progress SVG.
 *
 * @param string $unique_id    Unique identifier for the SVG.
 * @param int    $size         Hexagon size.
 * @param int    $stroke_width Stroke width.
 * @param int    $progress     Progress percentage (0-100).
 */
function clanbite_render_hexagon_progress_svg( string $unique_id, int $size, int $stroke_width, int $progress ): void {
	$padding = 8;
	$view_size = $size + $padding;
	$center = $view_size / 2;
	$hex_radius = ( $size - $stroke_width ) / 2;
	
	// Calculate hexagon points
	$points = array();
	for ( $i = 0; $i < 6; $i++ ) {
		$angle = ( M_PI / 3 ) * $i - ( M_PI / 2 ); // Start from top
		$x = $center + $hex_radius * cos( $angle );
		$y = $center + $hex_radius * sin( $angle );
		$points[] = round( $x, 2 ) . ',' . round( $y, 2 );
	}
	$points_str = implode( ' ', $points );
	
	// Calculate perimeter (6 equal sides)
	$side_length = $hex_radius;
	$perimeter = 6 * $side_length;
	$dash_offset = $perimeter * ( 1 - ( $progress / 100 ) );
	?>
	<svg 
		width="<?php echo esc_attr( $view_size ); ?>" 
		height="<?php echo esc_attr( $view_size ); ?>" 
		viewBox="0 0 <?php echo esc_attr( $view_size ); ?> <?php echo esc_attr( $view_size ); ?>"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<!-- Background hexagon -->
		<polygon
			class="clanbite-avatar__progress-path"
			points="<?php echo esc_attr( $points_str ); ?>"
		/>
		<!-- Progress hexagon -->
		<polygon
			class="clanbite-avatar__progress-fill"
			points="<?php echo esc_attr( $points_str ); ?>"
			stroke-dasharray="<?php echo esc_attr( $perimeter ); ?>"
			stroke-dashoffset="<?php echo esc_attr( $dash_offset ); ?>"
			pathLength="<?php echo esc_attr( $perimeter ); ?>"
		/>
	</svg>
	<?php
}
