<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * User Navigation block render.
 *
 * @package Clanspress
 */

$avatar_size   = $attributes['avatarSize'] ?? 32;
$show_username = $attributes['showUsername'] ?? false;

$is_logged_in = is_user_logged_in();

if ( $is_logged_in ) {
	$user         = wp_get_current_user();
	$user_id      = $user->ID;
	$display_name = $user->display_name;

	// Use Clanspress player avatar if available, fallback to Gravatar.
	if ( function_exists( 'clanspress_players_get_display_avatar' ) ) {
		$avatar_url = clanspress_players_get_display_avatar( $user_id, false, 'thumbnail' );
	} else {
		$avatar_url = get_avatar_url( $user_id, array( 'size' => $avatar_size * 2 ) );
	}

	$profile_url = function_exists( 'clanspress_get_player_profile_url' )
		? clanspress_get_player_profile_url( $user_id )
		: get_author_posts_url( $user_id );

	$menu_items = clanspress_get_user_nav_menu_items( $user_id );
} else {
	$guest_links = clanspress_get_user_nav_guest_links();
}

$context = array(
	'isOpen' => false,
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-user-nav' . ( $is_logged_in ? ' is-logged-in' : ' is-guest' ),
	),
	$block
);
?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	data-wp-interactive="clanspress/user-nav"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
	data-wp-on-document--click="actions.handleOutsideClick"
	data-wp-class--is-open="context.isOpen"
>
	<?php if ( $is_logged_in ) : ?>
		<button
			type="button"
			class="clanspress-user-nav__trigger"
			aria-label="<?php esc_attr_e( 'User menu', 'clanspress' ); ?>"
			aria-expanded="false"
			aria-haspopup="true"
			data-wp-on--click="actions.toggleDropdown"
			data-wp-bind--aria-expanded="context.isOpen"
		>
			<img
				src="<?php echo esc_url( $avatar_url ); ?>"
				alt=""
				class="clanspress-user-nav__avatar"
				width="<?php echo esc_attr( $avatar_size ); ?>"
				height="<?php echo esc_attr( $avatar_size ); ?>"
			/>
			<?php if ( $show_username ) : ?>
				<span class="clanspress-user-nav__username"><?php echo esc_html( $display_name ); ?></span>
			<?php endif; ?>
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				width="16"
				height="16"
				fill="currentColor"
				class="clanspress-user-nav__caret"
				aria-hidden="true"
			>
				<path d="M7 10l5 5 5-5z" />
			</svg>
		</button>

		<div
			class="clanspress-user-nav__dropdown"
			role="menu"
			aria-label="<?php esc_attr_e( 'User menu', 'clanspress' ); ?>"
			hidden
			data-wp-bind--hidden="!context.isOpen"
		>
			<div class="clanspress-user-nav__dropdown-header">
				<a href="<?php echo esc_url( $profile_url ); ?>" class="clanspress-user-nav__profile-link">
					<img
						src="<?php echo esc_url( $avatar_url ); ?>"
						alt=""
						class="clanspress-user-nav__dropdown-avatar"
						width="40"
						height="40"
					/>
					<div class="clanspress-user-nav__profile-info">
						<span class="clanspress-user-nav__profile-name"><?php echo esc_html( $display_name ); ?></span>
						<span class="clanspress-user-nav__profile-label"><?php esc_html_e( 'View Profile', 'clanspress' ); ?></span>
					</div>
				</a>
			</div>

			<?php if ( ! empty( $menu_items ) ) : ?>
				<?php
				$current_group = '';
				foreach ( $menu_items as $item ) :
					$group = $item['group'] ?? '';

					if ( $group !== $current_group ) :
						if ( '' !== $current_group ) :
							?>
							</div>
							<?php
						endif;
						$current_group = $group;
						?>
						<div class="clanspress-user-nav__menu-group" data-group="<?php echo esc_attr( $group ); ?>">
						<?php
					endif;
					?>
					<a
						href="<?php echo esc_url( $item['url'] ); ?>"
						class="clanspress-user-nav__menu-item<?php echo ! empty( $item['class'] ) ? ' ' . esc_attr( $item['class'] ) : ''; ?>"
						role="menuitem"
						<?php if ( ! empty( $item['target'] ) ) : ?>
							target="<?php echo esc_attr( $item['target'] ); ?>"
						<?php endif; ?>
					>
						<?php if ( ! empty( $item['icon'] ) ) : ?>
							<span class="clanspress-user-nav__menu-icon dashicons dashicons-<?php echo esc_attr( $item['icon'] ); ?>"></span>
						<?php endif; ?>
						<span class="clanspress-user-nav__menu-label"><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
				<?php if ( '' !== $current_group ) : ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

	<?php else : ?>
		<div class="clanspress-user-nav__guest-links">
			<?php foreach ( $guest_links as $link ) : ?>
				<a
					href="<?php echo esc_url( $link['url'] ); ?>"
					class="clanspress-user-nav__guest-link clanspress-user-nav__guest-link--<?php echo esc_attr( $link['style'] ?? 'secondary' ); ?>"
					<?php if ( ! empty( $link['target'] ) ) : ?>
						target="<?php echo esc_attr( $link['target'] ); ?>"
					<?php endif; ?>
				>
					<?php echo esc_html( $link['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
