<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * User Navigation block render.
 *
 * @package Clanbite
 */

$avatar_size   = $attributes['avatarSize'] ?? 32;
$show_username = $attributes['showUsername'] ?? false;

$is_logged_in = is_user_logged_in();

if ( $is_logged_in ) {
	$user         = wp_get_current_user();
	$user_id      = $user->ID;
	$display_name = $user->display_name;

	// Use the unified avatar rendering system
	$avatar_trigger = '';
	if ( function_exists( 'clanbite_render_avatar' ) ) {
		$avatar_trigger = clanbite_render_avatar(
			array(
				'type'          => 'player',
				'id'            => $user_id,
				'size'          => 'small',
				'context'       => 'user_nav',
				'extra_classes' => array( 'clanbite-user-nav__avatar' ),
				'link'          => false,
			)
		);
	}

	// Fallback if unified system isn't available
	if ( '' === $avatar_trigger ) {
		$avatar_url_fallback = function_exists( 'clanbite_players_get_display_avatar' )
			? clanbite_players_get_display_avatar( $user_id, false, '', 'user_nav', 'small' )
			: get_avatar_url( $user_id, array( 'size' => max( 96, (int) $avatar_size * 2 ) ) );

		$avatar_trigger = sprintf(
			'<span class="clanbite-avatar clanbite-avatar--player clanbite-avatar--shape-circle clanbite-user-nav__avatar"><span class="clanbite-avatar__clip"><img src="%1$s" alt="" class="clanbite-avatar__img" width="%2$d" height="%2$d" loading="lazy" decoding="async" /></span></span>',
			esc_url( $avatar_url_fallback ),
			(int) $avatar_size
		);
	}

	$profile_url = function_exists( 'clanbite_get_player_profile_url' )
		? clanbite_get_player_profile_url( $user_id )
		: get_author_posts_url( $user_id );

	$menu_items = clanbite_get_user_nav_menu_items( $user_id );
} else {
	$guest_links = clanbite_get_user_nav_guest_links();
}

$context = array(
	'isOpen' => false,
);
$dropdown_id = wp_unique_id( 'clanbite-user-nav-dropdown-' );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-user-nav' . ( $is_logged_in ? ' is-logged-in' : ' is-guest' ),
	),
	$block
);

$user_nav_root_open = '<div '
	. trim( (string) $wrapper_attributes )
	. ' data-wp-interactive="clanbite/user-nav"'
	. ' data-wp-context="' . esc_attr( wp_json_encode( $context ) ) . '"'
	. ' data-wp-on-document--click="actions.handleOutsideClick"'
	. ' data-wp-on-document--keydown="actions.handleKeydown"'
	. ' data-wp-class--is-open="context.isOpen"'
	. '>';
?>
<?php ob_start(); ?>
<?php clanbite_echo_block_fragment_html( $user_nav_root_open ); ?>
	<?php if ( $is_logged_in ) : ?>
		<button
			type="button"
			class="clanbite-user-nav__trigger"
			aria-label="<?php esc_attr_e( 'User menu', 'clanbite' ); ?>"
			aria-expanded="false"
			aria-haspopup="menu"
			aria-controls="<?php echo esc_attr( $dropdown_id ); ?>"
			data-wp-on--click="actions.toggleDropdown"
			data-wp-bind--aria-expanded="context.isOpen"
		>
			<?php clanbite_echo_block_fragment_html( (string) $avatar_trigger ); ?>
			<?php if ( $show_username ) : ?>
				<span class="clanbite-user-nav__username"><?php echo esc_html( $display_name ); ?></span>
			<?php endif; ?>
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				width="16"
				height="16"
				fill="currentColor"
				class="clanbite-user-nav__caret"
				aria-hidden="true"
			>
				<path d="M7 10l5 5 5-5z" />
			</svg>
		</button>

		<div
			id="<?php echo esc_attr( $dropdown_id ); ?>"
			class="clanbite-user-nav__dropdown"
			role="menu"
			aria-label="<?php esc_attr_e( 'User menu', 'clanbite' ); ?>"
			hidden
			data-wp-bind--hidden="!context.isOpen"
		>
			<div class="clanbite-user-nav__dropdown-header">
				<a href="<?php echo esc_url( $profile_url ); ?>" class="clanbite-user-nav__profile-link">
					<div class="clanbite-user-nav__profile-info">
						<span class="clanbite-user-nav__profile-name"><?php echo esc_html( $display_name ); ?></span>
						<span class="clanbite-user-nav__profile-label"><?php esc_html_e( 'View Profile', 'clanbite' ); ?></span>
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
						<div class="clanbite-user-nav__menu-group" data-group="<?php echo esc_attr( $group ); ?>">
						<?php
					endif;
					?>
					<a
						href="<?php echo esc_url( $item['url'] ); ?>"
						class="clanbite-user-nav__menu-item<?php echo ! empty( $item['class'] ) ? ' ' . esc_attr( $item['class'] ) : ''; ?>"
						role="menuitem"
						<?php if ( ! empty( $item['target'] ) ) : ?>
							target="<?php echo esc_attr( $item['target'] ); ?>"
						<?php endif; ?>
					>
						<?php if ( ! empty( $item['icon'] ) ) : ?>
							<span class="clanbite-user-nav__menu-icon dashicons dashicons-<?php echo esc_attr( $item['icon'] ); ?>"></span>
						<?php endif; ?>
						<span class="clanbite-user-nav__menu-label"><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
				<?php if ( '' !== $current_group ) : ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

	<?php else : ?>
		<div class="clanbite-user-nav__guest-links">
			<?php foreach ( $guest_links as $link ) : ?>
				<a
					href="<?php echo esc_url( $link['url'] ); ?>"
					class="clanbite-user-nav__guest-link clanbite-user-nav__guest-link--<?php echo esc_attr( $link['style'] ?? 'secondary' ); ?>"
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
<?php echo wp_kses( (string) ob_get_clean(), clanbite_block_fragment_allowed_html()); ?>
