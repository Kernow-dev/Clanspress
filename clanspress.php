<?php
/**
 * Plugin Name: Clanspress
 * Plugin URI: https://clanspress.com
 * Description: Community management system for Gamers and Sports teams
 * Version: 1.0.0
 * Requires at least: 6.7
 * Tested up to: 6.9
 * Requires PHP: 8.2
 * Author: kernow.dev
 * Author URI: https://kernow.dev
 * Donate link: https://kernow.dev
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clanspress
 * Domain Path: /languages
 *
 * @link    https://clanspress.com/
 *
 * @package clanspress
 * @version 1.0.0
 */

namespace Kernowdev\Clanspress;

defined( 'ABSPATH' ) || exit;

use AllowDynamicProperties;
use Kernowdev\Clanspress\Admin\Settings;
use Kernowdev\Clanspress\Extensions\Loader as Extension_Loader;
use Kernowdev\Clanspress\Cross_Site_Match_Sync;
use Kernowdev\Clanspress\Public_Rest;
use Kernowdev\Clanspress\Wordban;

// Use composer autoload.
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/class-block-patterns.php';
require_once __DIR__ . '/inc/clanspress-private-media.php';
require_once __DIR__ . '/inc/clanspress-team-challenge-uploads.php';
require_once __DIR__ . '/inc/functions-block-templates.php';
require_once __DIR__ . '/inc/functions-request-input.php';
require_once __DIR__ . '/inc/functions-country-flags.php';
require_once __DIR__ . '/inc/functions-block-entity-link.php';
require_once __DIR__ . '/inc/profile-subpages.php';
require_once __DIR__ . '/shortcut-function.php';

/**
 * Main initiation class.
 */
#[AllowDynamicProperties]
final class Main {
	/**
	 * Current version.
	 *
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * Maintenance upgrade counter (single step for 1.0.0 public release).
	 *
	 * @var int
	 */
	public const MAINTENANCE_VERSION = 1;

	/**
	 * Singleton instance of plugin.
	 *
	 * @var Main|null
	 */
	protected static ?Main $instance = null;

	/**
	 * The token, used to prefix values in DB.
	 *
	 * @var   string
	 */
	protected string $_token = 'clanspress_';

	/**
	 * URL of the plugin directory.
	 *
	 * @var string
	 */
	public string $url = '';

	/**
	 * Path of the plugin directory.
	 *
	 * @var string
	 */
	public string $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	public string $basename = '';

	/**
	 * The main plugin file.
	 *
	 * @var string
	 */
	public string $file;

	/**
	 * Detailed activation error messages.
	 *
	 * @var array
	 */
	protected array $activation_errors = array();

	/**
	 * Extension loader (assigned in {@see init()} after textdomain load).
	 *
	 * @var Extension_Loader|null Null until {@see init()} runs.
	 */
	public ?Extension_Loader $extensions = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return Main A single instance of this class.
	 */
	public static function instance(): Main {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets up our plugin.
	 */
	protected function __construct() {
		$this->file     = basename( __FILE__ );
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Activate the plugin.
	 */
	public function _activate(): void {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		Extension_Loader::persist_required_extensions_on_activation();
		// Regenerate on next request so extensions that register rewrites on `init` are included.
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements(): bool {
		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action(
			'all_admin_notices',
			array( $this, 'requirements_not_met_notice' )
		);

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		// Didn't meet the requirements.
		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 */
	public function deactivate_me(): void {
		// We do a check for deactivate_plugins before calling it to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements(): bool {
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 */
	public function requirements_not_met_notice(): void {
		// Compile default message.
		$default_message = sprintf(
			/* translators: %s: URL to the Plugins admin screen. */
			__(
				'Clanspress Plugin is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.',
				'clanspress'
			),
			esc_url( admin_url( 'plugins.php' ) )
		);

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors ) {
			$details = '<small>' . implode(
				'</small><br /><small>',
				$this->activation_errors
			) . '</small>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<p><?php echo wp_kses_post( $default_message ); ?></p>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 */
	public function _deactivate(): void {
	}

	/**
	 * Hooks run at 0.
	 */
	public function early_hooks(): void {
	}

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'init' ), 0 );

		add_action( 'init', array( $this, 'register_core_blocks' ), 20 );

		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_visibility_container_block_editor' ) );

		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'filter_plugin_action_links' ) );

		add_filter( 'block_categories_all', array( $this, 'register_block_categories' ), 5, 2 );

		// Scripts and styles.
		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_admin_styles' )
		);
	}

	/**
	 * Register block editor categories (core Clanspress + per-extension groups).
	 *
	 * @param array<int, array<string, mixed>> $categories Existing categories.
	 * @param \WP_Block_Editor_Context|null    $context    Editor context (unused).
	 * @return array<int, array<string, mixed>>
	 */
	public function register_block_categories( array $categories, $context ): array {
		$ours = array(
			array(
				'slug'  => 'clanspress',
				'title' => __( 'Clanspress', 'clanspress' ),
			),
			array(
				'slug'  => 'clanspress-players',
				'title' => __( 'Clanspress Players', 'clanspress' ),
			),
			array(
				'slug'  => 'clanspress-teams',
				'title' => __( 'Clanspress Teams', 'clanspress' ),
			),
			array(
				'slug'  => 'clanspress-matches',
				'title' => __( 'Clanspress Matches', 'clanspress' ),
			),
		);

		return array_merge( $ours, $categories );
	}

	/**
	 * Prepend Settings and website links on the Plugins list screen.
	 *
	 * @param array<int, string> $links Existing action link HTML.
	 * @return array<int, string>
	 */
	public function filter_plugin_action_links( array $links ): array {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return $links;
		}

		$prepend = array();

		if ( current_user_can( 'manage_options' ) ) {
			$prepend[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'admin.php?page=clanspress' ) ),
				esc_html__( 'Settings', 'clanspress' )
			);
		}

		$prepend[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://clanspress.com/' ),
			esc_html__( 'Website', 'clanspress' )
		);

		return array_merge( $prepend, $links );
	}

	/**
	 * Init hooks
	 */
	public function init(): void {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Load translated strings for the plugin.
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Standard plugin i18n; languages live under /languages (not only language packs).
		load_plugin_textdomain(
			'clanspress',
			false,
			dirname( $this->basename ) . '/languages/'
		);

		require_once $this->path . 'inc/groups/functions.php';

		// Perform maintenance.
		$this->maybe_run_maintenance();

		// Initialise plugin extensions before admin UI (settings tabs need extension metadata).
		$this->extensions = Extension_Loader::instance();

		// Must run on every request: REST (`/wp-json/...`) is not `is_admin()`, but the
		// React settings app calls `clanspress/v1/admin/*` from the browser.
		Settings::instance();

		add_action( 'rest_api_init', array( Public_Rest::class, 'register_routes' ) );
		add_action( 'rest_api_init', array( Cross_Site_Match_Sync::class, 'register_routes' ) );

		add_action( 'init', array( Block_Patterns::class, 'register' ), 100 );

		Wordban::register_hooks();
	}

	/**
	 * Register plugin blocks that are not tied to a single extension bundle.
	 *
	 * @return void
	 */
	public function register_core_blocks(): void {
		if ( ! $this->check_requirements() ) {
			return;
		}

		require_once $this->path . 'inc/visibility-container.php';

		$path = $this->path . 'build/core/visibility-container';
		if ( is_dir( $path ) ) {
			register_block_type( $path );
		}
	}

	/**
	 * Pass role labels into the block editor for the visibility container token fields.
	 *
	 * @return void
	 */
	public function localize_visibility_container_block_editor(): void {
		if ( ! function_exists( 'wp_roles' ) ) {
			return;
		}

		$roles_out = array();
		foreach ( wp_roles()->roles as $slug => $details ) {
			$roles_out[] = array(
				'slug'  => $slug,
				'label' => translate_user_role( $details['name'] ),
			);
		}

		wp_localize_script(
			'wp-block-editor',
			'clanspressVisibilityContainer',
			array( 'roles' => $roles_out )
		);
	}

	/**
	 * Enqueue admin CSS.
	 */
	public function enqueue_admin_styles(): void {
	}

	/**
	 * Check if any necessary maintenance tasks need to be run and execute them.
	 */
	public function maybe_run_maintenance(): void {
		$maintenance_version = (int) get_option(
			$this->_token . '_maint_version'
		);

		// Pre-1.0.0 installs used higher integers; normalize so the 1.0 maintenance step can run once.
		if ( $maintenance_version > self::MAINTENANCE_VERSION ) {
			$maintenance_version = 0;
		}

		if ( $maintenance_version < self::MAINTENANCE_VERSION ) {
			for (
				$version = $maintenance_version + 1;
				$version <= self::MAINTENANCE_VERSION;
				$version++
			) {
				Maintenance::run( $version );
			}

			update_option(
				$this->_token . '_maint_version',
				self::MAINTENANCE_VERSION
			);
		}
	}

	/**
	 * Returns the current version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return self::VERSION;
	}
}

// Kick it off.
add_action( 'plugins_loaded', array( clanspress(), 'early_hooks' ), 0 );
add_action( 'plugins_loaded', array( clanspress(), 'hooks' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( clanspress(), '_activate' ) );
register_deactivation_hook( __FILE__, array( clanspress(), '_deactivate' ) );
