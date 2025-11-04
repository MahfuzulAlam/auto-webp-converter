<?php
/**
 * Main Plugin Class
 *
 * @package Auto_WebP_Converter
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auto WebP Converter Main Class
 *
 * @since 1.0.0
 */
class WpXplore_Auto_WebP_Converter {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin instance
	 *
	 * @var WpXplore_Auto_WebP_Converter
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return WpXplore_Auto_WebP_Converter
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
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	private function init() {
		// Check if required image libraries are available
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		
		// Initialize settings
		if ( is_admin() ) {
			new WpXplore_AWC_Settings();
		}
	}

	/**
	 * Check if required image libraries are available
	 *
	 * @return array Array with library availability status
	 */
	public function check_image_libraries() {
		$imagick_available = extension_loaded( 'imagick' ) && class_exists( 'Imagick' );
		$gd_available      = extension_loaded( 'gd' ) && function_exists( 'imagecreatetruecolor' );

		return array(
			'imagick'  => $imagick_available,
			'gd'       => $gd_available,
			'available' => $imagick_available || $gd_available,
		);
	}

	/**
	 * Display admin notice if neither Imagick nor GD is available
	 */
	public function admin_notice() {
		$libraries = $this->check_image_libraries();

		if ( ! $libraries['available'] ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Auto WebP Converter:', 'wpxplore-webp-converter' ); ?></strong>
					<?php esc_html_e( 'Neither Imagick nor GD library is available. Please install one of these PHP extensions for image conversion to work.', 'wpxplore-webp-converter' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Check if file is a supported image type
	 *
	 * @param array $file File array from WordPress upload.
	 * @return bool True if supported, false otherwise.
	 */
	public function is_supported_image( $file ) {
		$settings = WpXplore_AWC_Settings::get_settings();
		$allowed_types = isset( $settings['allowed_types'] ) && is_array( $settings['allowed_types'] ) ? $settings['allowed_types'] : array( 'jpeg', 'png' );
		
		// Build supported MIME types based on settings.
		$supported_mimes = array();
		$supported_exts  = array();
		
		if ( in_array( 'jpeg', $allowed_types, true ) ) {
			$supported_mimes[] = 'image/jpeg';
			$supported_mimes[] = 'image/jpg';
			$supported_exts[]  = 'jpg';
			$supported_exts[]  = 'jpeg';
		}
		
		if ( in_array( 'png', $allowed_types, true ) ) {
			$supported_mimes[] = 'image/png';
			$supported_exts[]  = 'png';
		}
		
		if ( empty( $supported_mimes ) ) {
			return false;
		}
		
		$file_type = wp_check_filetype( $file['name'] );

		// Check MIME type.
		if ( isset( $file['type'] ) && in_array( $file['type'], $supported_mimes, true ) ) {
			return true;
		}

		// Fallback: check file extension.
		if ( in_array( strtolower( $file_type['ext'] ), $supported_exts, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert image to WebP using Imagick
	 *
	 * @param string $source_path      Source image path.
	 * @param string $destination_path Destination WebP path.
	 * @param int    $quality          Image quality (0-100).
	 * @return bool True on success, false on failure.
	 */
	public function convert_with_imagick( $source_path, $destination_path, $quality = 80 ) {
		try {
			$imagick = new Imagick( $source_path );

			// Set image format to WebP.
			$imagick->setImageFormat( 'webp' );

			// Set image quality.
			$imagick->setImageCompressionQuality( $quality );

			// Strip image metadata for smaller file size.
			$imagick->stripImage();

			// Write the WebP image.
			$imagick->writeImage( $destination_path );

			// Clear memory.
			$imagick->clear();
			$imagick->destroy();

			return true;
		} catch ( Exception $e ) {
			error_log( 'Auto WebP Converter (Imagick): ' . esc_html( $e->getMessage() ) );
			return false;
		}
	}

	/**
	 * Convert image to WebP using GD
	 *
	 * @param string $source_path      Source image path.
	 * @param string $destination_path Destination WebP path.
	 * @param int    $quality          Image quality (0-100).
	 * @return bool True on success, false on failure.
	 */
	public function convert_with_gd( $source_path, $destination_path, $quality = 80 ) {
		try {
			// Get image info.
			$image_info = getimagesize( $source_path );

			if ( ! $image_info ) {
				return false;
			}

			$mime_type = $image_info['mime'];
			$width     = $image_info[0];
			$height    = $image_info[1];

			// Create image resource based on type.
			switch ( $mime_type ) {
				case 'image/jpeg':
				case 'image/jpg':
					$source_image = imagecreatefromjpeg( $source_path );
					break;
				case 'image/png':
					$source_image = imagecreatefrompng( $source_path );
					// Preserve transparency for PNG.
					imagealphablending( $source_image, false );
					imagesavealpha( $source_image, true );
					break;
				default:
					return false;
			}

			if ( ! $source_image ) {
				return false;
			}

			// Convert to WebP.
			$result = imagewebp( $source_image, $destination_path, $quality );

			// Clean up memory.
			imagedestroy( $source_image );

			return false !== $result;
		} catch ( Exception $e ) {
			error_log( 'Auto WebP Converter (GD): ' . esc_html( $e->getMessage() ) );
			return false;
		}
	}

	/**
	 * Convert uploaded image to WebP format
	 *
	 * @param array $file File array from WordPress upload.
	 * @return array Modified file array.
	 */
	public function convert_to_webp( $file ) {
		// Check if library is available.
		$libraries = $this->check_image_libraries();

		if ( ! $libraries['available'] ) {
			return $file;
		}

		// Check if file is a supported image type.
		if ( ! $this->is_supported_image( $file ) ) {
			return $file;
		}

		$source_path      = $file['tmp_name'];
		$file_info        = pathinfo( $file['name'] );
		$destination_path = $file['tmp_name'] . '.webp';

		$converted = false;
		
		// Get quality from settings.
		$settings = WpXplore_AWC_Settings::get_settings();
		$quality  = isset( $settings['quality'] ) ? absint( $settings['quality'] ) : 80;

		// Try Imagick first, then fall back to GD.
		if ( $libraries['imagick'] ) {
			$converted = $this->convert_with_imagick( $source_path, $destination_path, $quality );
		}

		if ( ! $converted && $libraries['gd'] ) {
			$converted = $this->convert_with_gd( $source_path, $destination_path, $quality );
		}

		if ( $converted && file_exists( $destination_path ) ) {
			// Replace original file with WebP version.
			$original_size = filesize( $source_path );
			$webp_size     = filesize( $destination_path );

			// Only replace if WebP is smaller or similar size.
			if ( $webp_size <= $original_size * 1.1 ) { // Allow 10% tolerance.
				// Copy WebP file over original.
				if ( copy( $destination_path, $source_path ) ) {
					// Update file name and type.
					$file['name'] = $file_info['filename'] . '.webp';
					$file['type'] = 'image/webp';

					// Clean up temporary WebP file.
					@unlink( $destination_path );
				}
			} else {
				// WebP is larger, keep original.
				@unlink( $destination_path );
			}
		}

		return $file;
	}
}

