<?php

namespace Kernowdev\Clanspress\Extensions;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;
use Kernowdev\Clanspress\Extensions\Players\Admin;

require_once __DIR__ . '/functions.php';

/**
 * Extension: Players.
 *
 * This extension adds player functionality, including player profiles and
 * settings.
 */
class Players extends Skeleton {
	protected Admin $admin;

	/**
	 * Sets up our extension loader.
	 */
	public function __construct() {
		parent::__construct(
			'Players',
			'cp_players',
			__(
				'Extends user functionality to add support for players.',
				'clanspress'
			),
			'',
			'0.0.1',
			array()
		);
	}

	/**
	 * Setup and validate extension values.
	 *
	 * @param string $name        The human-readable name of the extension.
	 * @param string $slug        The extensions slug.
	 * @param string $description The extensions description.
	 * @param string $parent_slug The slug of the parent extension.
	 * @param string $version     The extension version.
	 * @param array  $requires    An array of required extensions.
	 */
	public function setup_extension(
		string $name,
		string $slug,
		string $description,
		string $parent_slug,
		string $version,
		array $requires
	): void {
		parent::setup_extension(
			$name,
			$slug,
			$description,
			$parent_slug,
			$version,
			$requires
		);

		// Built-in extensions register as official, not third-party.
		remove_filter( 'clanspress_registered_extensions', array( $this, 'register_extension' ) );
		add_filter(
			'clanspress_official_registered_extensions',
			array( $this, 'register_extension' )
		);
	}

	public function run_installer(): void {
	}

	public function run_uninstaller(): void {
	}

	public function run_updater(): void {
	}

	public function run(): void {
		// Initiate admin functionality and settings.
		$this->admin = new Admin();

		// Maybe initiate player profile functionality.
		if ( $this->admin->get( 'enable_profiles' ) ) {
			$this->enable_profiles();
		}
	}

	public function get_setting( string $key, $fallback = null ) {
		return $this->admin->get( $key, $fallback );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_settings_admin(): ?Abstract_Settings {
		return isset( $this->admin ) ? $this->admin : null;
	}

	/**
	 * Enable player profile functionality.
	 *
	 * The function adds various actions and filters to enable the following
	 * functionality: profile endpoints (players/username), player settings
	 * (player/settings), custom profile gutenberg templates and blocks, custom
	 *  player meta keys.
	 *
	 * @return void
	 */
	public function enable_profiles(): void {
		add_action( 'init', array( $this, 'register_profile_endpoints' ) );
		add_filter( 'author_link', array( $this, 'modify_author_links' ), 10, 3 );
		add_filter( 'query_vars', array( $this, 'register_profile_query_vars' ) );
		add_filter( 'template_include', array( $this, 'maybe_load_player_settings_template' ) );
		add_action( 'init', array( $this, 'register_profile_templates' ) );
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );

		// Blocks and assets.
		add_action( 'init', array( $this, 'register_profile_blocks' ) );

		// Profile meta.
		add_action( 'init', array( $this, 'register_user_meta_keys' ) );

		// Profile settings.
		add_filter( 'clanspress_players_settings_nav_items', array( $this, 'register_player_settings_nav_items' ) );
		add_filter( 'clanspress_players_settings_nav_profile_sub_items', array( $this, 'register_profile_nav_items' ) );
		add_filter( 'clanspress_players_settings_nav_account_sub_items', array( $this, 'register_account_nav_items' ) );
		add_action( 'clanspress_player_settings_panel_profile-info', array( $this, 'do_profile_avatar_fields' ) );
		add_action( 'clanspress_player_settings_panel_profile-info', array( $this, 'do_profile_info_fields' ), 20 );
		add_action( 'clanspress_player_settings_panel_account-info', array( $this, 'do_account_info_fields' ) );

		// Save profile settings.
		add_action( 'clanspress_save_player_settings', array( $this, 'save_player_profile_settings' ), 10, 4 );
		add_action( 'clanspress_save_player_settings', array( $this, 'save_player_account_info_settings' ), 10, 4 );

		// Ajax handlers.
		add_action( 'wp_ajax_clanspress_save_player_settings', array( $this, 'ajax_save_player_settings' ) );
	}

	public function modify_author_links( $link, $author_id, $author_nicename ) {
		return home_url( '/players/' . $author_nicename );
	}

	/**
	 * @return void
	 */
	public function register_profile_endpoints() {
		// 1. Players settings page
		add_rewrite_rule(
			'^players/settings/?$',
			'index.php?players_settings=1',
			'top'
		);

		// 2. Author pagination
		add_rewrite_rule(
			'^players/(?!settings/?$)([^/]+)/page/([0-9]+)/?$',
			'index.php?author_name=$matches[1]&paged=$matches[2]',
			'top'
		);

		// 3. Author first page
		add_rewrite_rule(
			'^players/(?!settings/?$)([^/]+)/?$',
			'index.php?author_name=$matches[1]',
			'top'
		);

		// 4. Players archive
		add_rewrite_rule(
			'^players/?$',
			'index.php?post_type=player_list',
			'top'
		);
	}

	public function register_profile_query_vars( $vars ) {
		$vars[] = 'players_settings';
		return $vars;
	}

	public function maybe_load_player_settings_template( $template ) {
		if ( ! get_query_var( 'players_settings' ) ) {
			return $template;
		}

		// Redirect to login if user is not logged in
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( get_permalink() ) ); // Redirect back after login
			exit;
		}

		// Create a hierarchy of templates.
		$templates = array(
			'player-settings.php',
			'index.php',
		);

		// First, search for PHP templates, which block themes can also use.
		$template = locate_template( $templates );

		// Pass the result into the block template locator and let it figure
		// out whether block templates are supported and this template exists.
		$template = locate_block_template( $template, 'player-settings', $templates );

		return apply_filters( 'clanspress_load_player_settings_template', $template );
	}

	public function register_profile_templates() {
		$this->register_extension_templates( $this->get_profile_templates() );
	}

	/**
	 * Get FSE templates owned by the Players extension.
	 *
	 * @return array<string, array<string, string>>
	 */
	protected function get_profile_templates(): array {
		return array(
			'player-settings' => array(
				'title' => __( 'Player Settings', 'clanspress' ),
				'path'  => clanspress()->path . '/templates/players/player-settings.php',
			),
		);
	}

	public function register_image_sizes() {
		add_image_size(
			'clanspress-cover',
			1184,
			300,
			true
		);
	}

	public function register_profile_blocks() {
		$this->register_extension_block_types_from_metadata_collection( 'build/players' );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_register_script(
			'clanspress-player-settings-localize',
			'',
			array(),
			false,
			true
		);

		wp_localize_script(
			'clanspress-player-settings-localize',
			'CLANSPRESSPLAYERSETTINGS',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'clanspress_profile_settings_save_action' ),
			)
		);

		wp_enqueue_script( 'clanspress-player-settings-localize' );
	}

	public function register_player_settings_nav_items( array $items ) {
		$items['profile'] = array(
			'label'       => __( 'Profile', 'clanspress' ),
			'description' => __( 'Public profile data', 'clanspress' ),
		);

		$items['account'] = array(
			'label'       => __( 'Account', 'clanspress' ),
			'description' => __( 'Account settings', 'clanspress' ),
		);

		return $items;
	}

	public function register_user_meta_keys() {
		register_meta(
			'user',
			'cp_player_avatar_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);

		register_meta(
			'user',
			'cp_player_avatar',
			array(
				'type'              => 'string',
				'description'       => 'Player avatar url.',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_cover_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);

		register_meta(
			'user',
			'cp_player_cover',
			array(
				'type'              => 'string',
				'description'       => 'Player cover url.',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_cover_position_x',
			array(
				'type'              => 'number',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return min( 1, max( 0, (float) $value ) );
				},
				'show_in_rest'      => true,
				'default'           => 0.5,
			)
		);

		register_meta(
			'user',
			'cp_player_cover_position_y',
			array(
				'type'              => 'number',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return min( 1, max( 0, (float) $value ) );
				},
				'show_in_rest'      => true,
				'default'           => 0.5,
			)
		);

		register_meta(
			'user',
			'cp_player_tagline',
			array(
				'type'              => 'string',
				'description'       => 'Player tagline',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_website',
			array(
				'type'              => 'string',
				'description'       => 'Player website',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_bio',
			array(
				'type'              => 'string',
				'description'       => 'Player biography',
				'single'            => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_country',
			array(
				'type'              => 'string',
				'description'       => 'Player country',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_city',
			array(
				'type'              => 'string',
				'description'       => 'Player city',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_birthday',
			array(
				'type'              => 'string',
				'description'       => 'Player birthday',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_first_name',
			array(
				'type'              => 'string',
				'description'       => 'Player first name',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_last_name',
			array(
				'type'              => 'string',
				'description'       => 'Player last name',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		// register_meta(
		// 'user',
		// 'clanspress_player_avatar_id',
		// array(
		// 'type'              => 'integer',
		// 'single'            => true,
		// 'sanitize_callback' => 'absint',
		// 'show_in_rest'      => true,
		// 'default'           => 0,
		// )
		// );
	}

	public function register_profile_nav_items( array $items ) {
		$items['profile-info'] = array(
			'label'       => __( 'Profile Info', 'clanspress' ),
			'description' => __( 'General account data', 'clanspress' ),
		);

		$items['social-networks'] = array(
			'label'       => __( 'Social Networks', 'clanspress' ),
			'description' => __( 'General account data', 'clanspress' ),
		);

		return $items;
	}

	public function register_account_nav_items( array $items ) {
		$items['account-info'] = array(
			'label'       => __( 'Account Info', 'clanspress' ),
			'description' => __( 'General account data', 'clanspress' ),
		);

		return $items;
	}

	public function do_profile_avatar_fields() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return null;
		}

		$user_avatar           = clanspress_players_get_display_avatar( $user_id, true );
		$user_cover            = clanspress_players_get_display_cover( $user_id, true );
		$background_position_x = round( clanspress_players_get_display_cover_position_x( $user_id ) * 100 ) . '% ';
		$background_position_y = round( clanspress_players_get_display_cover_position_y( $user_id ) * 100 ) . '% ';

		$background_position = $background_position_x . ' ' . $background_position_y;

		do_action( 'clanspress_before_profile_avatar_fields', $user_id );

		$avatars_enabled = $this->admin->get( 'enable_avatars' );
		$covers_enabled  = $this->admin->get( 'enable_covers' );

		if ( $avatars_enabled || $covers_enabled ) :
			?>
		<div class="settings-row">
			<div class="avatar-cover-preview">
				<?php if ( $covers_enabled ) : ?>
					<div class="cover-preview" style="background-image: url(<?php echo esc_url( $user_cover ); ?>); background-position: <?php echo esc_attr( $background_position ); ?>;"></div>
				<?php endif; ?>
				<?php if ( $avatars_enabled ) : ?>
					<div class="avatar-preview" style="background-image: url(<?php echo esc_url( $user_avatar ); ?>);"></div>
				<?php endif; ?>
			</div>
			<?php if ( $avatars_enabled ) : ?>
				<button
						class="change-media avatar"
						data-wp-on--click="actions.selectAvatar"
				><?php esc_html_e( 'Set avatar', 'clanspress' ); ?></button>
				<input
						type="file"
						accept="image/png,image/jpeg"
						hidden
						data-wp-on--change="actions.updateAvatar"
						id="profile-avatar"
						name="profile_avatar"
				>
			<?php endif; ?>
			<?php if ( $covers_enabled ) : ?>
				<button
						class="change-media cover"
						data-wp-on--click="actions.selectCover"
				><?php esc_html_e( 'Set cover image', 'clanspress' ); ?></button>
				<input
						type="file"
						accept="image/png,image/jpeg"
						hidden
						data-wp-on--change="actions.updateCover"
						id="profile-cover"
						name="profile_cover"
				>
			<?php endif; ?>
		</div>
		<?php endif; ?>
			<?php
	}

	public function do_profile_info_fields() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return null;
		}

		$user          = get_userdata( $user_id );
		$user_tagline  = clanspress_players_get_display_tagline( $user_id, true );
		$user_bio      = clanspress_players_get_display_bio( $user_id, true );
		$user_website  = clanspress_players_get_display_website( $user_id, true );
		$user_country  = clanspress_players_get_display_country( $user_id, 'code', true );
		$user_city     = clanspress_players_get_display_city( $user_id, true );
		$user_birthday = clanspress_players_get_display_birthday( $user_id, true );

		do_action( 'clanspress_before_profile_info_fields', $user_id, $user );
		?>
		<div class="settings-section">
			<h2 class="settings-section-title"><?php esc_html_e( 'About You', 'clanspress' ); ?></h2>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="display-name"><?php esc_html_e( 'Profile Name', 'clanspress' ); ?></label>
						<input type="text" id="display-name" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="display_name" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="profile-tagline"><?php esc_html_e( 'Tagline', 'clanspress' ); ?></label>
						<input type="text" id="profile-tagline" name="profile_tagline" value="<?php echo esc_attr( $user_tagline ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_tagline" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<textarea id="profile-description" name="profile_description" data-wp-class--error="state.isError" placeholder="<?php esc_html_e( 'Write a little description about you...', 'clanspress' ); ?>"><?php echo wp_kses_post( $user_bio ); ?></textarea>
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_description" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="profile-website"><?php esc_html_e( 'Public website', 'clanspress' ); ?></label>
						<input type="text" id="profile-website" name="profile_website" value="<?php echo esc_attr( $user_website ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_website" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="profile-country"><?php esc_html_e( 'Country', 'clanspress' ); ?></label>
						<select id="profile-country" name="profile_country" data-wp-class--error="state.isError">
							<option value="" <?php selected( $user_country, '', true ); ?>><?php esc_html_e( 'Select Country', 'clanspress' ); ?></option>
							<?php
							$countries = clanspress_players_get_countries();

							if ( $countries ) :
								?>
								<?php foreach ( $countries as $country_code => $country ) : ?>
									<option value="<?php echo esc_attr( $country_code ); ?>" <?php selected( $user_country, $country_code, true ); ?>><?php echo esc_html( $country ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="display_name" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="profile-city"><?php esc_html_e( 'City', 'clanspress' ); ?></label>
						<input type="text" id="profile-city" name="profile_city" value="<?php echo esc_attr( $user_city ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_city" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input small active">
						<label for="profile-birthday"><?php esc_html_e( 'Birthday', 'clanspress' ); ?></label>
						<input type="date" id="profile-birthday" name="profile_birthday" value="<?php echo esc_attr( $user_birthday ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_birthday" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
		</div>
		<?php

		do_action( 'clanspress_after_profile_info_fields', $user_id, $user );
	}

	public function do_account_info_fields() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return null;
		}

		$user            = get_userdata( $user_id );
		$user_first_name = clanspress_players_get_account_firstname( $user_id, true );
		$user_last_name  = clanspress_players_get_account_lastname( $user_id, true );

		do_action( 'clanspress_before_account_info_fields', $user_id, $user );
		?>
		<div class="settings-section">
			<h2 class="settings-section-title"><?php esc_html_e( 'Personal Info', 'clanspress' ); ?></h2>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="account-first-name"><?php esc_html_e( 'First Name', 'clanspress' ); ?></label>
						<input type="text" id="account-first-name" name="account_first_name" value="<?php echo esc_attr( $user_first_name ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="account_first_name" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="account-last-name"><?php esc_html_e( 'Surname', 'clanspress' ); ?></label>
						<input type="text" id="account-last-name" name="account_last_name" value="<?php echo esc_attr( $user_last_name ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="account_last_name" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="account-email"><?php esc_html_e( 'Email Address', 'clanspress' ); ?></label>
						<input type="text" id="account-email" name="account_email" value="<?php echo esc_attr( $user->user_email ); ?>" data-wp-class--error="state.isError" disabled>
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="account_email" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<?php /* translators: %s: Player profile URL base. */ ?>
						<label for="account-url"><?php echo esc_html( sprintf( __( 'URL Username: %s', 'clanspress' ), home_url( '/players/' ) ) ); ?></label>
						<input type="text" id="account-url" name="account_url" value="<?php echo esc_attr( $user->user_nicename ); ?>" data-wp-class--error="state.isError" disabled>
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="account_url" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
		</div>
		<?php

		do_action( 'clanspress_after_account_info_fields', $user_id, $user );
	}

	public function save_player_profile_settings( $filtered_data, $data, $files, $user_id ) {
		$errors = array();

		// Handle avatar and cover image first
		if ( isset( $files['profile_avatar'] ) ) {
			$_FILES['profile_avatar'] = $files['profile_avatar'];

			$old_avatar = get_user_meta( $user_id, 'cp_player_avatar_id', true );

			add_filter(
				'upload_dir',
				function ( $dirs ) use ( $user_id ) {
					$sub            = "/clanspress/player/{$user_id}";
					$dirs['subdir'] = $sub;
					$dirs['path']   = $dirs['basedir'] . $sub;
					$dirs['url']    = $dirs['baseurl'] . $sub;
					return $dirs;
				}
			);

			require_once ABSPATH . 'wp-admin/includes/media.php';

			$attachment_id = media_handle_upload( 'profile_avatar', 0 );

			remove_all_filters( 'upload_dir' );

			if ( ! is_wp_error( $attachment_id ) ) {
				if ( $old_avatar ) {
					wp_delete_attachment( $old_avatar, true );
				}

				update_user_meta( $user_id, 'cp_player_avatar_id', $attachment_id );
				update_user_meta( $user_id, 'cp_player_avatar', wp_get_attachment_url( $attachment_id ) );
			} else {
				$errors['profile_avatar'] = $attachment_id->get_error_message();
			}
		}

		if ( isset( $files['profile_cover'] ) ) {
			$_FILES['profile_cover'] = $files['profile_cover'];

			$old_cover = get_user_meta( $user_id, 'cp_player_cover_id', true );

			add_filter(
				'upload_dir',
				function ( $dirs ) use ( $user_id ) {
					$sub            = "/clanspress/player/{$user_id}";
					$dirs['subdir'] = $sub;
					$dirs['path']   = $dirs['basedir'] . $sub;
					$dirs['url']    = $dirs['baseurl'] . $sub;
					return $dirs;
				}
			);

			require_once ABSPATH . 'wp-admin/includes/media.php';

			$attachment_id = media_handle_upload( 'profile_cover', 0 );

			remove_all_filters( 'upload_dir' );

			if ( ! is_wp_error( $attachment_id ) ) {
				if ( $old_cover ) {
					wp_delete_attachment( $old_cover, true );
				}

				update_user_meta( $user_id, 'cp_player_cover_id', $attachment_id );
				update_user_meta( $user_id, 'cp_player_cover', wp_get_attachment_image_url( $attachment_id, 'clanspress-cover' ) );
			} else {
				$errors['profile_cover'] = $attachment_id->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_cover_position_x'] ) ) {
			$profile_cover_position_x = apply_filters( 'clanspress_player_settings_update_display_cover_position_x', $filtered_data['profile_cover_position_x'], $user_id );

			if ( ! is_wp_error( $profile_cover_position_x ) ) {
				update_user_meta( $user_id, 'cp_player_cover_position_x', $profile_cover_position_x );
			} else {
				$errors['profile_cover_position_x'] = $profile_cover_position_x->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_cover_position_y'] ) ) {
			$profile_cover_position_y = apply_filters( 'clanspress_player_settings_update_display_cover_position_y', $filtered_data['profile_cover_position_y'], $user_id );

			if ( ! is_wp_error( $profile_cover_position_y ) ) {
				update_user_meta( $user_id, 'cp_player_cover_position_y', $profile_cover_position_y );
			} else {
				$errors['profile_cover_position_y'] = $profile_cover_position_y->get_error_message();
			}
		}

		if ( isset( $filtered_data['display_name'] ) ) {
			$display_name = apply_filters( 'clanspress_player_settings_update_display_name', sanitize_user( $filtered_data['display_name'] ), $user_id );

			if ( ! is_wp_error( $display_name ) ) {
				$result = wp_update_user(
					array(
						'ID'           => $user_id,
						'display_name' => $display_name,
					)
				);
			} else {
				$errors['display_name'] = $display_name->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_tagline'] ) ) {
			$display_tagline = apply_filters( 'clanspress_player_settings_update_tagline', sanitize_text_field( $filtered_data['profile_tagline'] ), $user_id );

			if ( ! is_wp_error( $display_tagline ) ) {
				update_user_meta( $user_id, 'cp_player_tagline', $display_tagline );
			} else {
				$errors['profile_tagline'] = $display_tagline->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_description'] ) ) {
			$display_description = apply_filters( 'clanspress_player_settings_update_description', wp_kses_post( $filtered_data['profile_description'] ), $user_id );

			if ( ! is_wp_error( $display_description ) ) {
				update_user_meta( $user_id, 'cp_player_bio', $display_description );
			} else {
				$errors['profile_description'] = $display_description->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_website'] ) ) {
			$display_website = apply_filters( 'clanspress_player_settings_update_website', sanitize_text_field( $filtered_data['profile_website'] ), $user_id );

			if ( ! is_wp_error( $display_website ) ) {
				update_user_meta( $user_id, 'cp_player_website', $display_website );
			} else {
				$errors['profile_website'] = $display_website->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_country'] ) ) {
			$display_country = apply_filters( 'clanspress_player_settings_update_country', sanitize_text_field( $filtered_data['profile_country'] ), $user_id );

			if ( ! is_wp_error( $display_country ) ) {
				update_user_meta( $user_id, 'cp_player_country', $display_country );
			} else {
				$errors['profile_country'] = $display_country->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_city'] ) ) {
			$display_city = apply_filters( 'clanspress_player_settings_update_city', sanitize_text_field( $filtered_data['profile_city'] ), $user_id );

			if ( ! is_wp_error( $display_city ) ) {
				update_user_meta( $user_id, 'cp_player_city', $display_city );
			} else {
				$errors['profile_city'] = $display_city->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_birthday'] ) ) {
			$display_birthday = apply_filters( 'clanspress_player_settings_update_birthday', sanitize_text_field( $filtered_data['profile_birthday'] ), $user_id );

			if ( ! is_wp_error( $display_birthday ) ) {
				update_user_meta( $user_id, 'cp_player_birthday', $display_birthday );
			} else {
				$errors['profile_birthday'] = $display_birthday->get_error_message();
			}
		}

		if ( ! empty( $errors ) ) {
			add_filter(
				'clanspress_save_player_settings_save_status',
				function ( $saved ) {
					return false;
				}
			);

			add_filter(
				'clanspress_save_player_settings_errors',
				function ( $known_errors ) use ( $errors ) {
					return array_merge( $errors, $known_errors );
				}
			);
		}
	}

	public function save_player_account_info_settings( $filtered_data, $data, $files, $user_id ) {
		$errors = array();

		if ( isset( $filtered_data['account_first_name'] ) ) {
			$account_first_name = apply_filters( 'clanspress_player_settings_update_account_firstname', sanitize_text_field( $filtered_data['account_first_name'] ), $user_id );

			if ( ! is_wp_error( $account_first_name ) ) {
				update_user_meta( $user_id, 'cp_player_first_name', $account_first_name );
			} else {
				$errors['account_fist_name'] = $account_first_name->get_error_message();
			}
		}

		if ( isset( $filtered_data['account_last_name'] ) ) {
			$account_last_name = apply_filters( 'clanspress_player_settings_update_account_lastname', sanitize_text_field( $filtered_data['account_last_name'] ), $user_id );

			if ( ! is_wp_error( $account_last_name ) ) {
				update_user_meta( $user_id, 'cp_player_last_name', $account_last_name );
			} else {
				$errors['account_last_name'] = $account_last_name->get_error_message();
			}
		}

		if ( ! empty( $errors ) ) {
			add_filter(
				'clanspress_save_player_settings_save_status',
				function ( $saved ) {
					return false;
				}
			);

			add_filter(
				'clanspress_save_player_settings_errors',
				function ( $known_errors ) use ( $errors ) {
					return array_merge( $errors, $known_errors );
				}
			);
		}
	}

	/**
	 * Ajax: save player settings.
	 *
	 * This function doesn't save any data, it is only the entry point for
	 * other functions to hook in and save the data. This function returns a
	 * json response to the front-end player settings block.
	 *
	 * @return void
	 */
	public function ajax_save_player_settings() {
		check_ajax_referer( 'clanspress_profile_settings_save_action', 'nonce' );

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( 'Not logged in' );
		}

		/**
		 * Let third-party developers hook into saving fields.
		 * $data = $_POST, sanitized below.
		 * $files = $_FILES.
		 */
		$data  = wp_unslash( $_POST );
		$files = wp_unslash( $_FILES );

		$filtered_data = apply_filters( 'clanspress_save_player_settings_filtered_data', $data, $user_id );

		// general hook for 3rd parties
		do_action( 'clanspress_save_player_settings', $filtered_data, $data, $files, $user_id );

		$saved  = apply_filters( 'clanspress_save_player_settings_save_status', true );
		$errors = apply_filters( 'clanspress_save_player_settings_errors', array() );

		if ( ! empty( $errors ) || ! $saved ) {
			wp_send_json_error(
				array(
					'errors' => $errors,
				)
			);
		}

		wp_send_json_success();
	}
}
