<?php
/**
 * Admin settings for Soustack publisher.
 */

if ( ! class_exists( 'Soustack_Settings' ) ) {
	/**
	 * Registers the Soustack settings screen and options.
	 */
	class Soustack_Settings {
		const OPTION_STRICT_MODE = 'soustack_strict_mode';

		/**
		 * Boot settings hooks.
		 */
		public function register() {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
		}

		/**
		 * Register settings and fields.
		 */
		public function register_settings() {
			register_setting(
				'soustack',
				self::OPTION_STRICT_MODE,
				array(
					'type'              => 'boolean',
					'sanitize_callback' => array( $this, 'sanitize_boolean' ),
					'default'           => false,
				)
			);

			add_settings_section(
				'soustack_general',
				__( 'Soustack publishing', 'soustack' ),
				'__return_false',
				'soustack'
			);

			add_settings_field(
				self::OPTION_STRICT_MODE,
				__( 'Strict validation', 'soustack' ),
				array( $this, 'render_strict_mode_field' ),
				'soustack',
				'soustack_general',
				array( 'label_for' => self::OPTION_STRICT_MODE )
			);
		}

		/**
		 * Add menu entry.
		 */
		public function register_menu() {
			add_options_page(
				__( 'Soustack', 'soustack' ),
				__( 'Soustack', 'soustack' ),
				'manage_options',
				'soustack',
				array( $this, 'render_settings_page' )
			);
		}

		/**
		 * Ensure boolean shape.
		 *
		 * @param mixed $value Raw value.
		 * @return bool
		 */
		public function sanitize_boolean( $value ) {
			return (bool) $value;
		}

		/**
		 * Render strict mode checkbox.
		 */
		public function render_strict_mode_field() {
			$strict = get_option( self::OPTION_STRICT_MODE, false );
			?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( self::OPTION_STRICT_MODE ); ?>" id="<?php echo esc_attr( self::OPTION_STRICT_MODE ); ?>" value="1" <?php checked( $strict ); ?> />
				<?php esc_html_e( 'Block publishing if Soustack validation fails.', 'soustack' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'When enabled, posts cannot be published if the sidecar payload is invalid.', 'soustack' ); ?>
			</p>
			<?php
		}

		/**
		 * Render full settings page.
		 */
		public function render_settings_page() {
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Soustack', 'soustack' ); ?></h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'soustack' );
					do_settings_sections( 'soustack' );
					submit_button();
					?>
				</form>
			</div>
			<?php
		}
	}
}
