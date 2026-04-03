<?php
/**
 * Single match template (Clanspress Matches extension).
 *
 * @package clanspress
 */

get_header();

while ( have_posts() ) {
	the_post();
	$match_id = (int) get_the_ID();
	$home_id  = (int) get_post_meta( $match_id, 'cp_match_home_team_id', true );
	$away_id  = (int) get_post_meta( $match_id, 'cp_match_away_team_id', true );
	$status   = (string) get_post_meta( $match_id, 'cp_match_status', true );
	$hs       = (int) get_post_meta( $match_id, 'cp_match_home_score', true );
	$as       = (int) get_post_meta( $match_id, 'cp_match_away_score', true );
	$venue    = (string) get_post_meta( $match_id, 'cp_match_venue', true );
	$raw_when   = (string) get_post_meta( $match_id, 'cp_match_scheduled_at', true );
	$fmt        = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$away_title = function_exists( 'clanspress_matches_resolve_away_team_title' )
		? clanspress_matches_resolve_away_team_title( $match_id )
		: clanspress_matches_team_title( $away_id );
	$away_logo = $away_id < 1 ? (string) get_post_meta( $match_id, 'cp_match_away_external_logo_url', true ) : '';
	$away_link = $away_id < 1 ? (string) get_post_meta( $match_id, 'cp_match_away_external_profile_url', true ) : '';
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'clanspress-single-match' ); ?>>
		<header class="entry-header">
			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			<p class="clanspress-match-meta">
				<?php
				if ( function_exists( 'clanspress_matches_format_datetime_local' ) && '' !== $raw_when ) {
					echo esc_html( clanspress_matches_format_datetime_local( $raw_when, $fmt ) );
				}
				if ( '' !== $status ) {
					echo ' — ';
					$labels = function_exists( 'clanspress_matches_status_labels' ) ? clanspress_matches_status_labels() : array();
					echo esc_html( $labels[ $status ] ?? $status );
				}
				?>
			</p>
		</header>

		<div class="entry-content">
			<section class="clanspress-match-teams" aria-label="<?php esc_attr_e( 'Teams', 'clanspress' ); ?>">
				<p>
					<strong><?php echo esc_html( clanspress_matches_team_title( $home_id ) ); ?></strong>
					<?php esc_html_e( 'vs', 'clanspress' ); ?>
					<strong class="clanspress-match-away">
						<?php if ( '' !== $away_logo ) : ?>
							<img src="<?php echo esc_url( $away_logo ); ?>" alt="" width="36" height="36" loading="lazy" decoding="async" class="clanspress-match-away__logo" />
						<?php endif; ?>
						<?php if ( '' !== $away_link ) : ?>
							<a href="<?php echo esc_url( $away_link ); ?>"><?php echo esc_html( $away_title ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $away_title ); ?>
						<?php endif; ?>
					</strong>
				</p>
				<p class="clanspress-match-score">
					<?php echo esc_html( (string) $hs . ' – ' . (string) $as ); ?>
				</p>
				<?php if ( '' !== $venue ) : ?>
					<p class="clanspress-match-venue"><?php echo esc_html( $venue ); ?></p>
				<?php endif; ?>
			</section>
			<?php the_content(); ?>
		</div>
	</article>
	<?php
}

get_footer();
