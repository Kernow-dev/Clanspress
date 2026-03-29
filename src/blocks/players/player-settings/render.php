<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
?>
<?php
$nav_items = (array) apply_filters( 'clanspress_players_settings_nav_items', array() );
?>
<div
	<?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>
	data-wp-interactive="clanspress-player-settings"
	data-wp-init="callbacks.init"
>
	<?php do_action( 'clanspress_player_settings_before_nav' ); ?>
	<div class="nav-container">
		<div class="nav">
			<?php
			foreach ( $nav_items as $nav_item => $item ) :
				$label       = $item['label'] ?? '';
				$description = $item['description'] ?? '';
				?>
			<div class="nav-item">
				<button
					class="nav-item-header"
					data-wp-on--click="actions.toggleNav"
					data-wp-args='<?php echo wp_json_encode( $nav_item ); ?>'
					data-wp-bind--aria-expanded="state.isThisNavExpanded"
					data-wp-class--active="state.isThisNavExpanded"
					aria-controls="<?php echo esc_attr( $nav_item ); ?>"
				>
					<span class="label"><?php echo esc_html( $label ); ?></span>
					<span class="description"><?php echo esc_html( $description ); ?></span>
				</button>
				<div
					class="nav-sub-items show"
					data-wp-class--show="state.isNavExpanded"
					id="<?php echo esc_attr( $nav_item ); ?>"
				>
					<?php
					$nav_sub_items = (array) apply_filters( "clanspress_players_settings_nav_{$nav_item}_sub_items", array() );
					foreach ( $nav_sub_items as $nav_sub_item => $sub_item ) :
						$sub_label = $sub_item['label'] ?? '';
						?>
						<button
							class="nav-sub-item"
							data-wp-on--click="actions.showPanel"
							data-wp-class--active="state.isThisPanelActive"
							data-wp-args='<?php echo wp_json_encode( $nav_sub_item ); ?>'
							aria-controls="<?php echo esc_attr( 'panel-' . $nav_sub_item ); ?>"
						>
							<?php echo esc_html( $sub_label ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>
			<div class="nav-item controls">
				<?php wp_nonce_field( 'clanspress_profile_settings_save_action', '_clanspress_profile_settings_save_nonce', true, true ); ?>
				<button
					class="save"
					data-wp-on--click="actions.save"
				><?php esc_html_e( 'Save changes', 'clanspress' ); ?></button>
			</div>
		</div>
	</div>
	<div class="setting-panels-container">
		<?php
		foreach ( $nav_items as $nav_item => $item ) :
			$section_label = $item['label'] ?? '';
			$nav_sub_items = (array) apply_filters( "clanspress_players_settings_nav_{$nav_item}_sub_items", array() );
			foreach ( $nav_sub_items as $nav_sub_item => $sub_item ) :
				$settings_label = $sub_item['label'] ?? '';
				?>
			<div
				class="settings-panel <?php echo esc_attr( $nav_sub_item . '-panel' ); ?>"
				data-wp-bind--hidden="!state.isPanelActive"
				id="<?php echo esc_attr( 'panel-' . $nav_sub_item ); ?>"
			>
				<div class="panel-content">
					<div class="panel-header">
						<span class="section-label"><?php echo esc_html( $section_label ); ?></span>
						<span class="settings-label"><?php echo esc_html( $settings_label ); ?></span>
					</div>
					<?php do_action( "clanspress_player_settings_panel_{$nav_sub_item}" ); ?>
				</div>
			</div>
		<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
	<div
		class="toast-box"
		data-wp-bind--hidden="!state.toast.visible"
		data-wp-class--success="state.isToastSuccess"
		data-wp-class--error="state.isToastError"
	>
		<div class="toast-box-icon">
		</div>

		<div class="toast-box-text">
			<p class="toast-heading" data-wp-text="state.toast.heading"></p>
			<p class="toast-description" data-wp-text="state.toast.message"></p>
		</div>
	</div>
</div>
