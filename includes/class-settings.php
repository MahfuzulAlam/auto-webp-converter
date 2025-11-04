<?php
/**
 * Settings Class
 *
 * Handles plugin settings page and options
 *
 * @package Auto_WebP_Converter
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Class
 *
 * @since 1.0.0
 */
class WpXplore_AWC_Settings {

	/**
	 * Settings option name
	 *
	 * @var string
	 */
	const OPTION_NAME = 'wpxplore_awc_settings';

	/**
	 * Get default settings
	 *
	 * @return array Default settings
	 */
	public static function get_defaults() {
		return array(
			'quality'      => 80,
			'allowed_types' => array( 'jpeg', 'png' ),
		);
	}

	/**
	 * Get settings
	 *
	 * @return array Settings array
	 */
	public static function get_settings() {
		$defaults = self::get_defaults();
		$settings = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get a specific setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value if not set.
	 * @return mixed Setting value
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = self::get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Initialize settings
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to Settings menu
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Auto WebP Converter', 'wpxplore-webp-converter' ),
			__( 'Auto WebP Converter', 'wpxplore-webp-converter' ),
			'manage_options',
			'auto-webp-converter',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'wpxplore_awc_settings_group',
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_defaults(),
			)
		);

		add_settings_section(
			'wpxplore_awc_conversion_section',
			__( 'Conversion Settings', 'wpxplore-webp-converter' ),
			array( $this, 'render_section_description' ),
			'auto-webp-converter'
		);

		add_settings_field(
			'quality',
			__( 'Convert Quality', 'wpxplore-webp-converter' ),
			array( $this, 'render_quality_field' ),
			'auto-webp-converter',
			'wpxplore_awc_conversion_section'
		);

		add_settings_field(
			'allowed_types',
			__( 'Allow Image Type', 'wpxplore-webp-converter' ),
			array( $this, 'render_allowed_types_field' ),
			'auto-webp-converter',
			'wpxplore_awc_conversion_section'
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Settings input.
	 * @return array Sanitized settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize quality (0-100).
		if ( isset( $input['quality'] ) ) {
			$quality = absint( $input['quality'] );
			$sanitized['quality'] = min( 100, max( 0, $quality ) );
		} else {
			$sanitized['quality'] = self::get_defaults()['quality'];
		}

		// Sanitize allowed types.
		if ( isset( $input['allowed_types'] ) && is_array( $input['allowed_types'] ) ) {
			$allowed = array( 'jpeg', 'png' );
			$sanitized['allowed_types'] = array_intersect( $input['allowed_types'], $allowed );
			
			// Ensure at least one type is selected.
			if ( empty( $sanitized['allowed_types'] ) ) {
				$sanitized['allowed_types'] = self::get_defaults()['allowed_types'];
			}
		} else {
			$sanitized['allowed_types'] = self::get_defaults()['allowed_types'];
		}

		return $sanitized;
	}

	/**
	 * Render section description
	 */
	public function render_section_description() {
		?>
		<p><?php esc_html_e( 'Configure how images are converted to WebP format.', 'wpxplore-webp-converter' ); ?></p>
		<?php
	}

	/**
	 * Render quality field
	 */
	public function render_quality_field() {
		$settings = self::get_settings();
		$quality  = isset( $settings['quality'] ) ? absint( $settings['quality'] ) : 80;
		?>
		<input 
			type="number" 
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[quality]" 
			id="wpxplore_awc_quality" 
			value="<?php echo esc_attr( $quality ); ?>" 
			min="0" 
			max="100" 
			step="1"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Image quality for WebP conversion (0-100). Higher values mean better quality but larger file sizes. Recommended: 80.', 'wpxplore-webp-converter' ); ?>
		</p>
		<?php
	}

	/**
	 * Render allowed types field
	 */
	public function render_allowed_types_field() {
		$settings      = self::get_settings();
		$allowed_types = isset( $settings['allowed_types'] ) && is_array( $settings['allowed_types'] ) ? $settings['allowed_types'] : array( 'jpeg', 'png' );
		
		$image_types = array(
			'jpeg' => __( 'JPEG', 'wpxplore-webp-converter' ),
			'png'  => __( 'PNG', 'wpxplore-webp-converter' ),
		);
		?>
		<fieldset>
			<?php foreach ( $image_types as $key => $label ) : ?>
				<label>
					<input 
						type="checkbox" 
						name="<?php echo esc_attr( self::OPTION_NAME ); ?>[allowed_types][]" 
						value="<?php echo esc_attr( $key ); ?>"
						<?php checked( in_array( $key, $allowed_types, true ) ); ?>
					/>
					<?php echo esc_html( $label ); ?>
				</label>
				<br />
			<?php endforeach; ?>
		</fieldset>
		<p class="description">
			<?php esc_html_e( 'Select which image types should be converted to WebP format.', 'wpxplore-webp-converter' ); ?>
		</p>
		<?php
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Show success message if settings were saved.
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'wpxplore_awc_messages',
				'wpxplore_awc_message',
				__( 'Settings saved successfully.', 'wpxplore-webp-converter' ),
				'success'
			);
		}

		settings_errors( 'wpxplore_awc_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wpxplore_awc_settings_group' );
				do_settings_sections( 'auto-webp-converter' );
				submit_button( __( 'Save Settings', 'wpxplore-webp-converter' ) );
				?>
			</form>
		</div>
		<?php
	}
}

