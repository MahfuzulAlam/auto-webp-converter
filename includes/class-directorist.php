<?php
/**
 * Directorist Integration Class
 *
 * Handles all Directorist-related functionality
 *
 * @package Auto_WebP_Converter
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Directorist Integration Class
 *
 * @since 1.0.0
 */
class WpXplore_AWC_Directorist {

	/**
	 * Plugin instance
	 *
	 * @var WpXplore_AWC_Directorist
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return WpXplore_AWC_Directorist
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Only initialize if Directorist is active
		if ( ! $this->is_directorist_active() ) {
			return;
		}

		$this->init();
	}

	/**
	 * Initialize Directorist integration
	 */
	private function init() {
		// Register settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add WebP support to Directorist file types
		add_filter( 'directorist_supported_file_types_groups', array( $this, 'add_webp_support' ) );
	}

	/**
	 * Check if Directorist plugin is active
	 *
	 * @return bool True if Directorist is active, false otherwise.
	 */
	public function is_directorist_active() {
		return $this->is_plugin_active( 'directorist/directorist-base.php' );
	}

	/**
	 * Check if a plugin is active
	 *
	 * @param string $plugin Plugin basename.
	 * @return bool True if plugin is active, false otherwise.
	 */
	private function is_plugin_active( $plugin ) {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || $this->is_plugin_active_for_network( $plugin );
	}

	/**
	 * Check if a plugin is active for network (multisite)
	 *
	 * @param string $plugin Plugin basename.
	 * @return bool True if plugin is network active, false otherwise.
	 */
	private function is_plugin_active_for_network( $plugin ) {
		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins[ $plugin ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Register Directorist settings
	 */
	public function register_settings() {
		// Only register settings if Directorist is active
		if ( ! $this->is_directorist_active() ) {
			return;
		}

		add_settings_section(
			'wpxplore_awc_directorist_section',
			__( 'Directorist Settings', 'wpxplore-webp-converter' ),
			array( $this, 'render_section_description' ),
			'auto-webp-converter'
		);

		add_settings_field(
			'directorist_post_types',
			__( 'Directorist post type (Admin Panel)', 'wpxplore-webp-converter' ),
			array( $this, 'render_post_type_field' ),
			'auto-webp-converter',
			'wpxplore_awc_directorist_section'
		);

		add_settings_field(
			'directorist_frontend_add_listing',
			__( 'Add listing page (Frontend)', 'wpxplore-webp-converter' ),
			array( $this, 'render_frontend_add_listing_field' ),
			'auto-webp-converter',
			'wpxplore_awc_directorist_section'
		);
	}

	/**
	 * Render Directorist section description
	 */
	public function render_section_description() {
		?>
		<p class="wpxplore-awc-section-description"><?php esc_html_e( 'Configure image conversion for Directorist listings. Enable conversion for admin panel featured images or frontend add listing page uploads.', 'wpxplore-webp-converter' ); ?></p>
		<?php
	}

	/**
	 * Render Directorist post type field
	 */
	public function render_post_type_field() {
		$settings = WpXplore_AWC_Settings::get_settings();
		$enabled  = isset( $settings['directorist_post_type'] ) ? (bool) $settings['directorist_post_type'] : false;
		?>
		<div class="wpxplore-awc-field-wrapper">
			<label for="wpxplore_awc_directorist_post_type">
				<input 
					type="checkbox" 
					name="<?php echo esc_attr( WpXplore_AWC_Settings::OPTION_NAME ); ?>[directorist_post_type]" 
					id="wpxplore_awc_directorist_post_type"
					value="enable"
					<?php checked( $enabled, true ); ?>
				/>
				<?php esc_html_e( 'Enable', 'wpxplore-webp-converter' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'When enabled for a Directorist post type, featured images uploaded from the admin panel edit page will be automatically converted to WebP format.', 'wpxplore-webp-converter' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Get Directorist post types
	 *
	 * @return array Array of Directorist post type slugs.
	 */
	public function get_post_types() {
		$directorist_post_types = array();

		// Check if Directorist is active and get its post types.
		if ( $this->is_directorist_active() ) {
			// Directorist typically uses 'at_biz_dir' as the main post type.
			if ( post_type_exists( 'at_biz_dir' ) ) {
				$directorist_post_types[] = 'at_biz_dir';
			}

			// Check for other Directorist post types if they exist.
			$all_post_types = get_post_types( array(), 'names' );
			foreach ( $all_post_types as $post_type ) {
				if ( strpos( $post_type, 'at_' ) === 0 && post_type_supports( $post_type, 'thumbnail' ) ) {
					if ( ! in_array( $post_type, $directorist_post_types, true ) ) {
						$directorist_post_types[] = $post_type;
					}
				}
			}
		}

		return $directorist_post_types;
	}

	/**
	 * Render Directorist frontend add listing field
	 */
	public function render_frontend_add_listing_field() {
		$settings = WpXplore_AWC_Settings::get_settings();
		$enabled  = isset( $settings['directorist_frontend_add_listing'] ) ? (bool) $settings['directorist_frontend_add_listing'] : false;
		?>
		<div class="wpxplore-awc-field-wrapper">
			<label for="wpxplore_awc_directorist_frontend_add_listing">
				<input 
					type="checkbox" 
					name="<?php echo esc_attr( WpXplore_AWC_Settings::OPTION_NAME ); ?>[directorist_frontend_add_listing]" 
					id="wpxplore_awc_directorist_frontend_add_listing"
					value="enable"
					<?php checked( $enabled, true ); ?>
				/>
				<?php esc_html_e( 'Enable', 'wpxplore-webp-converter' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'When enabled, images uploaded from the frontend Add Listing page will be automatically converted to WebP format.', 'wpxplore-webp-converter' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Check if Directorist conversion is enabled (Admin Panel)
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_conversion_enabled() {
		$settings = WpXplore_AWC_Settings::get_settings();
		return isset( $settings['directorist_post_type'] ) && (bool) $settings['directorist_post_type'];
	}

	/**
	 * Check if Directorist frontend add listing conversion is enabled
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_frontend_add_listing_enabled() {
		$settings = WpXplore_AWC_Settings::get_settings();
		return isset( $settings['directorist_frontend_add_listing'] ) && (bool) $settings['directorist_frontend_add_listing'];
	}

	/**
	 * Check if we're on the Directorist frontend add listing page
	 *
	 * @return bool True if on add listing page, false otherwise.
	 */
	public function is_frontend_add_listing_page() {
		if ( ! $this->is_directorist_active() ) {
			return false;
		}

		// get_directorist_option() is defined by Directorist; guard in case it loads later or not at all.
		if ( ! function_exists( 'get_directorist_option' ) ) {
			return false;
		}

		// Get the add listing page ID
		$add_listing_page_id = get_directorist_option( 'add_listing_page' );
		
		if ( ! $add_listing_page_id ) {
			return false;
		}

		// Check if we're on that page (for direct page loads)
		if ( is_page( $add_listing_page_id ) ) {
			return true;
		}

		// Check for AJAX uploads from the add listing page via referer.
		// Best-effort context detection only — never used for authorization.
		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			$page_url = get_permalink( $add_listing_page_id );
			
			if ( $page_url && false !== strpos( $referer, $page_url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add WebP support to Directorist file types
	 *
	 * @param array $groups Supported file type groups.
	 * @return array Modified file type groups.
	 */
	public function add_webp_support( $groups ) {
		if ( isset( $groups['image'] ) && is_array( $groups['image'] ) ) {
			$groups['image'][] = 'webp';
		}
		return $groups;
	}
}

