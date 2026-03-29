<?php
/**
 * Clanspress wp-admin: unified React settings + CPT submenu placement.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Admin;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;
use Kernowdev\Clanspress\Extensions\Loader;
use Kernowdev\Clanspress\Extensions\Skeleton;

require_once __DIR__ . '/class-general-settings.php';
require_once __DIR__ . '/class-admin-rest.php';

/**
 * Registers the top-level menu, enqueues the React shell, and wires REST.
 */
class Settings {

	protected static ?Settings $instance = null;

	protected General_Settings $general_settings;

	/**
	 * @var array<string, Abstract_Settings>
	 */
	protected array $settings_registry = array();

	protected Admin_Rest $rest;

	public static function instance(): Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {
		$this->general_settings = new General_Settings();

		$this->settings_registry[ $this->general_settings->get_option_key() ] = $this->general_settings;

		$loader = Loader::instance();
		foreach ( $loader->get_extensions() as $slug => $extension ) {
			if ( ! $extension instanceof Skeleton ) {
				continue;
			}
			$admin = $extension->get_settings_admin();
			if ( $admin instanceof Abstract_Settings ) {
				$this->settings_registry[ $admin->get_option_key() ] = $admin;
			}
		}

		$this->rest = new Admin_Rest( $this->settings_registry );

		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );
		// Priority 5: register before CPT submenus (usually priority 10) so the
		// duplicate "clanspress" submenu is first — otherwise the parent menu
		// links to the first real child (e.g. Teams) instead of this screen.
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_admin_app' ) );
		add_action( 'admin_init', array( $this, 'redirect_legacy_extension_submenu' ) );
	}

	/**
	 * Old bookmark to `clanspress-extensions` lands on the unified screen.
	 */
	public function redirect_legacy_extension_submenu(): void {
		if ( ! is_admin() || ! isset( $_GET['page'] ) ) {
			return;
		}
		if ( 'clanspress-extensions' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=clanspress' ) );
		exit;
	}

	public function register_admin_pages(): void {
		add_menu_page(
			__( 'Clanspress', 'clanspress' ),
			__( 'Clanspress', 'clanspress' ),
			'manage_options',
			'clanspress',
			array( $this, 'render_main_page' ),
			'dashicons-groups',
			56
		);

		// Required: same slug as parent so this is the first submenu entry; WP uses
		// that for the top-level menu href and avoids jumping straight to CPTs.
		add_submenu_page(
			'clanspress',
			__( 'Clanspress settings', 'clanspress' ),
			__( 'Settings', 'clanspress' ),
			'manage_options',
			'clanspress',
			array( $this, 'render_main_page' )
		);
	}

	/**
	 * Enqueue the unified settings app on the main Clanspress screen only.
	 */
	public function maybe_enqueue_admin_app( string $hook_suffix ): void {
		// Top-level only: toplevel_page_clanspress. With a duplicate submenu
		// (same slug as parent), WP often reports clanspress_page_clanspress.
		$settings_hooks = array( 'toplevel_page_clanspress', 'clanspress_page_clanspress' );
		if ( ! in_array( $hook_suffix, $settings_hooks, true ) ) {
			return;
		}

		$handle = 'clanspress-admin-app';
		$rel    = \clanspress()->path . 'assets/dist/clanspress-admin.js';
		$url    = \clanspress()->url . 'assets/dist/clanspress-admin.js';

		if ( ! file_exists( $rel ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					printf(
						'<div class="notice notice-warning"><p>%s</p></div>',
						esc_html__( 'Clanspress admin UI assets are missing. Run `npm run build:admin` from the plugin directory.', 'clanspress' )
					);
				}
			);
			return;
		}

		$asset_file = dirname( $rel ) . '/clanspress-admin.asset.php';
		$asset      = file_exists( $asset_file ) ? include $asset_file : array(
			'dependencies' => array(),
			'version'      => (string) filemtime( $rel ),
		);
		$deps       = is_array( $asset['dependencies'] ?? null ) ? $asset['dependencies'] : array();
		$ver        = isset( $asset['version'] ) ? (string) $asset['version'] : (string) filemtime( $rel );

		wp_enqueue_script(
			$handle,
			$url,
			$deps,
			$ver,
			true
		);

		wp_enqueue_style( 'wp-components' );

		wp_localize_script(
			$handle,
			'clanspressAdmin',
			array(
				'restUrl' => esc_url_raw( rest_url( 'clanspress/v1/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	public function render_main_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap"><div id="clanspress-admin-root"></div></div>';
	}
}
