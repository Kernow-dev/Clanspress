<?php

namespace Kernowdev\Clanspress\Extensions;

abstract class Abstract_Settings {

	protected string $option_key;
	protected string $page_slug;
	protected string $settings_group;
	protected string $capability = 'manage_options';

	public function __construct() {
		$this->hooks();
	}

	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * When true, registers a classic submenu under Clanspress. The unified React
	 * admin keeps this false; use filter `clanspress_extension_settings_register_submenu`.
	 *
	 * @var bool
	 */
	protected bool $register_standalone_submenu = false;

	public function register_page(): void {
		$register = (bool) apply_filters( 'clanspress_extension_settings_register_submenu', $this->register_standalone_submenu, $this );

		if ( ! $register ) {
			return;
		}

		$parent_slug = (string) apply_filters( "{$this->option_key}_parent_menu_slug", 'clanspress', $this );

		add_submenu_page(
			$parent_slug,
			$this->get_page_title(),
			$this->get_menu_title(),
			$this->capability,
			$this->page_slug,
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		$default_settings = $this->get_defaults();

		/**
		 * Filter extension settings defaults before registration.
		 *
		 * @param array             $default_settings Default settings map.
		 * @param Abstract_Settings $settings         Settings class instance.
		 */
		$default_settings = (array) apply_filters( "{$this->option_key}_defaults", $default_settings, $this );

		register_setting(
			$this->settings_group,
			$this->option_key,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $default_settings,
			)
		);

		$sections = $this->get_sections();

		/**
		 * Filter all sections before they are registered.
		 *
		 * @param array             $sections Sections array.
		 * @param Abstract_Settings $settings Settings class instance.
		 */
		$sections = (array) apply_filters( "{$this->option_key}_sections", $sections, $this );

		foreach ( $sections as $section_id => $section ) {
			add_settings_section(
				$section_id,
				$section['title'] ?? '',
				$section['callback'] ?? null,
				$this->page_slug
			);

			$fields = isset( $section['fields'] ) && is_array( $section['fields'] ) ? $section['fields'] : array();

			/**
			 * Filter section fields before they are registered.
			 *
			 * @param array             $fields     Field config map.
			 * @param string            $section_id Section id.
			 * @param array             $section    Section config.
			 * @param Abstract_Settings $settings   Settings class instance.
			 */
			$fields = (array) apply_filters( "{$this->option_key}_section_fields", $fields, $section_id, $section, $this );

			foreach ( $fields as $field_id => $field ) {
				/**
				 * Filter a single field config before registration.
				 *
				 * @param array             $field      Field config.
				 * @param string            $field_id   Field id.
				 * @param string            $section_id Section id.
				 * @param Abstract_Settings $settings   Settings class instance.
				 */
				$field = (array) apply_filters( "{$this->option_key}_field", $field, $field_id, $section_id, $this );

				add_settings_field(
					$field_id,
					$field['label'] ?? '',
					array( $this, 'render_field' ),
					$this->page_slug,
					$section_id,
					array(
						'id'    => $field_id,
						'field' => $field,
					)
				);
			}
		}
	}

	public function render_field( array $args ): void {
		$field   = $args['field'];
		$id      = $args['id'];
		$options = $this->get_all();
		$value   = $options[ $id ] ?? $field['default'] ?? '';

		/**
		 * Allow full field rendering override.
		 *
		 * Return true to signal rendering handled by a custom callback.
		 *
		 * @param bool              $handled  Whether rendering has already been handled.
		 * @param string            $id       Field id.
		 * @param array             $field    Field config.
		 * @param mixed             $value    Current field value.
		 * @param Abstract_Settings $settings Settings class instance.
		 */
		$handled = (bool) apply_filters( "{$this->option_key}_render_field", false, $id, $field, $value, $this );
		if ( $handled ) {
			return;
		}

		$type = $field['type'] ?? 'text';

		switch ( $type ) {
			case 'checkbox':
				printf(
					'<input type="checkbox" name="%1$s[%2$s]" value="1" %3$s />',
					esc_attr( $this->option_key ),
					esc_attr( $id ),
					checked( $value, true, false )
				);
				break;
			case 'textarea':
				printf(
					'<textarea class="large-text" rows="4" name="%1$s[%2$s]">%3$s</textarea>',
					esc_attr( $this->option_key ),
					esc_attr( $id ),
					esc_textarea( (string) $value )
				);
				break;
			case 'select':
				printf(
					'<select name="%1$s[%2$s]">',
					esc_attr( $this->option_key ),
					esc_attr( $id )
				);

				$options = isset( $field['options'] ) && is_array( $field['options'] )
					? $field['options']
					: array();

				foreach ( $options as $option_value => $option_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( (string) $option_value ),
						selected( (string) $value, (string) $option_value, false ),
						esc_html( (string) $option_label )
					);
				}

				echo '</select>';
				break;

			default:
				printf(
					'<input type="%1$s" name="%2$s[%3$s]" value="%4$s" class="regular-text" />',
					esc_attr( $type ),
					esc_attr( $this->option_key ),
					esc_attr( $id ),
					esc_attr( $value )
				);
		}

		if ( ! empty( $field['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $field['description'] ) );
		}
	}

	public function sanitize( array $input ): array {
		$output   = array();
		$defaults = $this->get_defaults();
		$fields   = $this->get_flat_fields();

		/**
		 * Filter raw settings payload before field-level sanitization.
		 *
		 * @param array             $input    Raw input.
		 * @param array             $fields   Flat field config map.
		 * @param Abstract_Settings $settings Settings class instance.
		 */
		$input = (array) apply_filters( "{$this->option_key}_sanitize_input", $input, $fields, $this );

		foreach ( $fields as $key => $field ) {
			$value = $input[ $key ] ?? null;

			if ( isset( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {
				$output[ $key ] = call_user_func( $field['sanitize'], $value );
			} else {
				// Default sanitization
				if ( ( $field['type'] ?? '' ) === 'checkbox' ) {
					$output[ $key ] = ! empty( $value );
				} elseif ( ( $field['type'] ?? '' ) === 'textarea' ) {
					$output[ $key ] = sanitize_textarea_field( (string) ( $value ?? '' ) );
				} else {
					$output[ $key ] = sanitize_text_field( $value ?? '' );
				}
			}
		}

		// Merge defaults to ensure no missing keys
		$output = wp_parse_args( $output, $defaults );

		return apply_filters( $this->option_key . '_sanitize', $output, $input );
	}

	public function get_all(): array {
		return wp_parse_args(
			get_option( $this->option_key, array() ),
			$this->get_defaults()
		);
	}

	public function get( string $key, $default = null ) {
		$settings = $this->get_all();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Render a standard settings page shell.
	 *
	 * @param string $title Settings page heading.
	 * @return void
	 */
	protected function render_settings_page( string $title ): void {
		do_action( "{$this->option_key}_before_page", $this );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $title ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( $this->settings_group );
				do_settings_sections( $this->page_slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
		do_action( "{$this->option_key}_after_page", $this );
	}

	protected function get_flat_fields(): array {
		$flat = array();
		foreach ( $this->get_sections() as $section ) {
			foreach ( $section['fields'] as $id => $field ) {
				$flat[ $id ] = $field;
			}
		}
		return $flat;
	}

	/**
	 * Option name used in the database.
	 */
	public function get_option_key(): string {
		return $this->option_key;
	}

	/**
	 * Schema for the React settings UI (sections + fields).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function export_rest_schema(): array {
		$out = array();

		foreach ( $this->get_sections() as $section_id => $section ) {
			$fields_out = array();
			$fields     = isset( $section['fields'] ) && is_array( $section['fields'] ) ? $section['fields'] : array();

			foreach ( $fields as $field_id => $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				$row = array(
					'id'          => (string) $field_id,
					'label'       => (string) ( $field['label'] ?? '' ),
					'type'        => (string) ( $field['type'] ?? 'text' ),
					'description' => (string) ( $field['description'] ?? '' ),
					'default'     => $field['default'] ?? null,
				);

				if ( 'select' === $row['type'] && ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
					$opts = array();
					foreach ( $field['options'] as $opt_val => $opt_label ) {
						$opts[] = array(
							'value' => (string) $opt_val,
							'label' => (string) $opt_label,
						);
					}
					$row['options'] = $opts;
				}

				$fields_out[] = $row;
			}

			$out[] = array(
				'id'     => (string) $section_id,
				'title'  => (string) ( $section['title'] ?? '' ),
				'fields' => $fields_out,
			);
		}

		return $out;
	}

	/**
	 * Validate and persist settings from a REST request body.
	 *
	 * @param array<string, mixed> $input Raw keyed field values.
	 * @return array<string, mixed>
	 */
	public function save_from_input( array $input ): array {
		$sanitized = $this->sanitize( $input );

		if ( is_multisite() && is_network_admin() ) {
			update_site_option( $this->option_key, $sanitized );
		} else {
			update_option( $this->option_key, $sanitized );
		}

		return $sanitized;
	}

	abstract protected function get_page_title(): string;
	abstract protected function get_menu_title(): string;
	abstract protected function get_defaults(): array;
	abstract protected function get_sections(): array;
	abstract public function render_page(): void;
}
