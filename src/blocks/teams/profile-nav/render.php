<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server-side render for the Team Profile Navigation block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$team_id = function_exists( 'clanspress_team_profile_context_team_id' ) ? clanspress_team_profile_context_team_id() : 0;
if ( $team_id < 1 ) {
	return;
}

$current_slug = function_exists( 'clanspress_team_profile_route_current_slug' ) ? clanspress_team_profile_route_current_slug() : '';

$subpages = function_exists( 'clanspress_get_team_subpages' ) ? clanspress_get_team_subpages() : array();
$base_url = trailingslashit( get_permalink( $team_id ) );

/**
 * Filter the label for the team profile home link.
 *
 * @param string $label   Default label.
 * @param int    $team_id Team post ID.
 */
$home_label = (string) apply_filters( 'clanspress_team_profile_home_label', __( 'Home', 'clanspress' ), $team_id );

/**
 * Filter: `clanspress_team_profile_nav_show_settings_link` — show the Settings link (team manage URL).
 *
 * Default: team admins/editors (and site teams admins), matching `clanspress_teams_user_can_manage`.
 *
 * @param bool $show    Whether to show the link.
 * @param int  $team_id Team post ID.
 */
$show_settings_link = (bool) apply_filters(
	'clanspress_team_profile_nav_show_settings_link',
	function_exists( 'clanspress_teams_user_can_manage' ) && clanspress_teams_user_can_manage( $team_id ),
	$team_id
);

$settings_url = '';
if ( $show_settings_link && function_exists( 'clanspress_teams_get_team_manage_url' ) ) {
	$settings_url = clanspress_teams_get_team_manage_url( $team_id );
}
$show_settings_link = $show_settings_link && '' !== $settings_url;

/**
 * Filter: `clanspress_team_profile_settings_nav_label` — label for the Settings item.
 *
 * @param string $label   Default label.
 * @param int    $team_id Team post ID.
 */
$settings_label = (string) apply_filters(
	'clanspress_team_profile_settings_nav_label',
	__( 'Settings', 'clanspress' ),
	$team_id
);

$settings_active = ( 'settings' === $current_slug );

$wrapper = get_block_wrapper_attributes(
	array(
		'class'       => 'clanspress-team-profile-nav',
		'role'        => 'navigation',
		'aria-label'  => __( 'Team sections', 'clanspress' ),
	),
	$block
);
?>
<nav <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>>
	<ul class="clanspress-team-profile-nav__list">
		<li class="clanspress-team-profile-nav__item<?php echo empty( $current_slug ) ? ' is-active' : ''; ?>">
			<a
				class="clanspress-team-profile-nav__link"
				href="<?php echo esc_url( $base_url ); ?>"
				<?php echo empty( $current_slug ) ? ' aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( $home_label ); ?>
			</a>
		</li>
		<?php foreach ( $subpages as $slug => $config ) :
			$label   = $config['label'] ?? ucfirst( $slug );
			$cap     = $config['capability'] ?? 'read';
			$allowed = current_user_can( $cap, $team_id );

			if ( ! $allowed ) {
				continue;
			}

			if ( 'events' === $slug && function_exists( 'clanspress_events_are_enabled_for_team' ) && ! clanspress_events_are_enabled_for_team( $team_id ) ) {
				continue;
			}

			$is_active = ( $slug === $current_slug );
			$url       = trailingslashit( $base_url . $slug );
			?>
			<li class="clanspress-team-profile-nav__item<?php echo $is_active ? ' is-active' : ''; ?>">
				<a
					class="clanspress-team-profile-nav__link"
					href="<?php echo esc_url( $url ); ?>"
					<?php echo $is_active ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
		<?php if ( $show_settings_link ) : ?>
			<li class="clanspress-team-profile-nav__item clanspress-team-profile-nav__item--settings<?php echo $settings_active ? ' is-active' : ''; ?>">
				<a
					class="clanspress-team-profile-nav__link"
					href="<?php echo esc_url( $settings_url ); ?>"
					<?php echo $settings_active ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $settings_label ); ?>
				</a>
			</li>
		<?php endif; ?>
	</ul>
</nav>

