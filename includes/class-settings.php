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
			'quality'            => 80,
			'allowed_types'      => array( 'jpeg', 'png' ),
			'convert_media_uploads' => true,
			'featured_image_post_types' => array(),
			'directorist_post_type' => false,
			'directorist_frontend_add_listing' => false,
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

		add_settings_field(
			'convert_media_uploads',
			__( 'Convert Media Library Uploads', 'wpxplore-webp-converter' ),
			array( $this, 'render_convert_media_uploads_field' ),
			'auto-webp-converter',
			'wpxplore_awc_conversion_section'
		);

		add_settings_section(
			'wpxplore_awc_featured_image_section',
			__( 'Featured Image Settings', 'wpxplore-webp-converter' ),
			array( $this, 'render_featured_image_section_description' ),
			'auto-webp-converter'
		);

		add_settings_field(
			'featured_image_post_types',
			__( 'Post Types for Featured Images', 'wpxplore-webp-converter' ),
			array( $this, 'render_featured_image_post_types_field' ),
			'auto-webp-converter',
			'wpxplore_awc_featured_image_section'
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

		// Sanitize convert media uploads (checkbox).
		$sanitized['convert_media_uploads'] = isset( $input['convert_media_uploads'] ) && '1' === $input['convert_media_uploads'];

		// Sanitize featured image post types.
		if ( isset( $input['featured_image_post_types'] ) && is_array( $input['featured_image_post_types'] ) ) {
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			$sanitized['featured_image_post_types'] = array_intersect( $input['featured_image_post_types'], $post_types );
		} else {
			$sanitized['featured_image_post_types'] = array();
		}

		// Sanitize directorist post type (checkbox).
		$sanitized['directorist_post_type'] = isset( $input['directorist_post_type'] ) && 'enable' === $input['directorist_post_type'];

		// Sanitize directorist frontend add listing (checkbox).
		$sanitized['directorist_frontend_add_listing'] = isset( $input['directorist_frontend_add_listing'] ) && 'enable' === $input['directorist_frontend_add_listing'];

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
	 * Render convert media uploads field
	 */
	public function render_convert_media_uploads_field() {
		$settings = self::get_settings();
		$enabled  = isset( $settings['convert_media_uploads'] ) ? (bool) $settings['convert_media_uploads'] : true;
		?>
		<label>
			<input 
				type="checkbox" 
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[convert_media_uploads]" 
				id="wpxplore_awc_convert_media_uploads" 
				value="1"
				<?php checked( $enabled, true ); ?>
			/>
			<?php esc_html_e( 'Enable image conversion for direct uploads on Media Library page', 'wpxplore-webp-converter' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, images uploaded directly through the Media Library page will be automatically converted to WebP format.', 'wpxplore-webp-converter' ); ?>
		</p>
		<?php
	}

	/**
	 * Render featured image section description
	 */
	public function render_featured_image_section_description() {
		?>
		<p><?php esc_html_e( 'Select which post types should have their featured images converted to WebP format when uploaded from the admin panel.', 'wpxplore-webp-converter' ); ?></p>
		<?php
	}

	/**
	 * Render featured image post types field
	 */
	public function render_featured_image_post_types_field() {
		$settings = self::get_settings();
		$enabled_post_types = isset( $settings['featured_image_post_types'] ) && is_array( $settings['featured_image_post_types'] ) ? $settings['featured_image_post_types'] : array();
		
		// Get all public post types that support featured images.
		$post_types = get_post_types( array(), 'objects' );
		$post_types_with_thumbnails = array();
		
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'thumbnail' ) ) {
				$post_types_with_thumbnails[ $post_type->name ] = $post_type->label;
			}
		}
		
		if ( empty( $post_types_with_thumbnails ) ) {
			?>
			<p><?php esc_html_e( 'No post types with featured image support found.', 'wpxplore-webp-converter' ); ?></p>
			<?php
			return;
		}
		?>
		<fieldset style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
			<?php foreach ( $post_types_with_thumbnails as $post_type_name => $post_type_label ) : ?>
				<label style="display: block; margin-bottom: 8px;">
					<input 
						type="checkbox" 
						name="<?php echo esc_attr( self::OPTION_NAME ); ?>[featured_image_post_types][]" 
						value="<?php echo esc_attr( $post_type_name ); ?>"
						<?php checked( in_array( $post_type_name, $enabled_post_types, true ) ); ?>
					/>
					<strong><?php echo esc_html( $post_type_label ); ?></strong>
					<code style="margin-left: 5px; color: #666;"><?php echo esc_html( $post_type_name ); ?></code>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<p class="description">
			<?php esc_html_e( 'When enabled for a post type, featured images uploaded from the admin panel edit page will be automatically converted to WebP format.', 'wpxplore-webp-converter' ); ?>
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

