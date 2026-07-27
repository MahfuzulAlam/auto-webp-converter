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
	const VERSION = '1.1.0';

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

		// Place kept originals next to the WebP file after WordPress moves the upload.
		add_filter( 'wp_handle_upload', array( $this, 'restore_original_upload' ) );

		// Register the kept original as its own Media Library attachment.
		add_action( 'add_attachment', array( $this, 'register_original_attachment' ) );

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

		// Ensure Imagick was compiled with the WebP delegate.
		if ( $imagick_available ) {
			try {
				$imagick_available = ! empty( Imagick::queryFormats( 'WEBP' ) );
			} catch ( Throwable $e ) {
				$imagick_available = false;
			}
		}

		// GD must expose imagewebp(); some builds are compiled without WebP support.
		$gd_available = extension_loaded( 'gd' ) && function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagewebp' );

		return array(
			'imagick'   => $imagick_available,
			'gd'        => $gd_available,
			'available' => $imagick_available || $gd_available,
		);
	}

	/**
	 * Log a conversion error when debugging is enabled
	 *
	 * @param string $message Log message.
	 */
	private function log_error( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Auto WebP Converter: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated behind WP_DEBUG.
		}
	}

	/**
	 * Display admin notice if neither Imagick nor GD is available
	 */
	public function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

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

		// MIME types and extensions for every convertible type.
		$type_map = array(
			'jpeg' => array(
				'mimes' => array( 'image/jpeg', 'image/jpg' ),
				'exts'  => array( 'jpg', 'jpeg' ),
			),
			'png'  => array(
				'mimes' => array( 'image/png' ),
				'exts'  => array( 'png' ),
			),
			'gif'  => array(
				'mimes' => array( 'image/gif' ),
				'exts'  => array( 'gif' ),
			),
			'bmp'  => array(
				'mimes' => array( 'image/bmp', 'image/x-ms-bmp' ),
				'exts'  => array( 'bmp' ),
			),
			'tiff' => array(
				'mimes' => array( 'image/tiff' ),
				'exts'  => array( 'tif', 'tiff' ),
			),
		);

		// Build supported MIME types based on settings.
		$supported_mimes = array();
		$supported_exts  = array();

		foreach ( $type_map as $type_key => $type_data ) {
			if ( in_array( $type_key, $allowed_types, true ) ) {
				$supported_mimes = array_merge( $supported_mimes, $type_data['mimes'] );
				$supported_exts  = array_merge( $supported_exts, $type_data['exts'] );
			}
		}

		if ( empty( $supported_mimes ) ) {
			return false;
		}
		
		// Validate file array structure
		if ( ! isset( $file['name'] ) || empty( $file['name'] ) ) {
			return false;
		}

		$file_type = wp_check_filetype( $file['name'] );

		// Check MIME type.
		if ( isset( $file['type'] ) && in_array( $file['type'], $supported_mimes, true ) ) {
			return true;
		}

		// Fallback: check file extension.
		if ( isset( $file_type['ext'] ) && in_array( strtolower( $file_type['ext'] ), $supported_exts, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether a GIF file is animated
	 *
	 * Counts GIF graphic control extension frames; two or more means animation.
	 *
	 * @param string $path Path to the GIF file.
	 * @return bool True if animated, false otherwise.
	 */
	public function is_animated_gif( $path ) {
		if ( ! is_readable( $path ) ) {
			return false;
		}

		$contents = file_get_contents( $path );

		if ( false === $contents ) {
			return false;
		}

		return preg_match_all( '#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $contents ) >= 2;
	}

	/**
	 * Stashed original for the upload currently being processed
	 *
	 * @var array|null
	 */
	private $pending_original = null;

	/**
	 * Restored original waiting to be registered as an attachment
	 *
	 * @var array|null
	 */
	private $pending_attachment = null;

	/**
	 * Stash a copy of the original temp file before it is overwritten with WebP
	 *
	 * @param string $source_path   Path to the original temp file.
	 * @param string $original_name Original client file name.
	 * @return bool True if stashed, false otherwise.
	 */
	private function stash_original( $source_path, $original_name ) {
		$this->discard_stash();

		$ext = strtolower( (string) pathinfo( $original_name, PATHINFO_EXTENSION ) );

		if ( '' === $ext ) {
			return false;
		}

		$stash_path = $source_path . '.awc-orig';

		if ( ! copy( $source_path, $stash_path ) ) {
			$this->log_error( 'Could not stash original: ' . $original_name );
			return false;
		}

		$this->pending_original = array(
			'path' => $stash_path,
			'ext'  => $ext,
		);

		return true;
	}

	/**
	 * Discard any stashed original
	 */
	private function discard_stash() {
		if ( ! empty( $this->pending_original['path'] ) && file_exists( $this->pending_original['path'] ) ) {
			wp_delete_file( $this->pending_original['path'] );
		}
		$this->pending_original = null;
	}

	/**
	 * Save the stashed original next to the converted WebP file
	 *
	 * Runs on the wp_handle_upload filter, after WordPress has moved the
	 * converted upload into the uploads directory. Only acts when "Keep
	 * Original Images" stashed a file during conversion.
	 *
	 * @param array $upload Upload data (file, url, type).
	 * @return array Unmodified upload data.
	 */
	public function restore_original_upload( $upload ) {
		if ( empty( $this->pending_original ) ) {
			return $upload;
		}

		$pending                = $this->pending_original;
		$this->pending_original = null;

		if ( ! is_array( $upload ) || empty( $upload['file'] ) || ! file_exists( $pending['path'] ) ) {
			if ( file_exists( $pending['path'] ) ) {
				wp_delete_file( $pending['path'] );
			}
			return $upload;
		}

		// Name the original after the final WebP file so the pair stays together.
		$dir      = dirname( $upload['file'] );
		$basename = pathinfo( $upload['file'], PATHINFO_FILENAME );
		$filename = wp_unique_filename( $dir, $basename . '.' . $pending['ext'] );
		$target   = trailingslashit( $dir ) . $filename;

		if ( copy( $pending['path'], $target ) ) {
			// Queue the original for Media Library registration once the
			// WebP attachment is created (see register_original_attachment()).
			$file_type                = wp_check_filetype( $filename );
			$this->pending_attachment = array(
				'path' => $target,
				'type' => ! empty( $file_type['type'] ) ? $file_type['type'] : 'image/' . $pending['ext'],
			);
		} else {
			$this->log_error( 'Could not keep original alongside: ' . $upload['file'] );
		}

		wp_delete_file( $pending['path'] );

		return $upload;
	}

	/**
	 * Register the kept original as its own Media Library attachment
	 *
	 * Runs on add_attachment: when WordPress registers the converted WebP
	 * upload, this inserts a second attachment for the original file kept
	 * alongside it, so both appear in the Media Library.
	 *
	 * @param int $attachment_id ID of the attachment WordPress just created.
	 */
	public function register_original_attachment( $attachment_id ) {
		if ( empty( $this->pending_attachment ) ) {
			return;
		}

		// Clear before inserting: wp_insert_attachment() fires add_attachment
		// again and this guard stops the recursion.
		$pending                  = $this->pending_attachment;
		$this->pending_attachment = null;

		if ( ! file_exists( $pending['path'] ) ) {
			return;
		}

		$parent = get_post( $attachment_id );

		$original_id = wp_insert_attachment(
			array(
				'post_mime_type' => $pending['type'],
				'post_title'     => pathinfo( $pending['path'], PATHINFO_FILENAME ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$pending['path'],
			$parent instanceof WP_Post ? (int) $parent->post_parent : 0
		);

		if ( is_wp_error( $original_id ) || ! $original_id ) {
			$this->log_error( 'Could not register kept original as attachment: ' . $pending['path'] );
			return;
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$metadata = wp_generate_attachment_metadata( $original_id, $pending['path'] );

		if ( ! empty( $metadata ) ) {
			wp_update_attachment_metadata( $original_id, $metadata );
		}
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
		// Validate file paths
		if ( empty( $source_path ) || empty( $destination_path ) || ! file_exists( $source_path ) ) {
			return false;
		}

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
		} catch ( Throwable $e ) {
			$this->log_error( '(Imagick) ' . $e->getMessage() );
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
		// Validate file paths
		if ( empty( $source_path ) || empty( $destination_path ) || ! file_exists( $source_path ) ) {
			return false;
		}

		try {
			// Get image info.
			$image_info = getimagesize( $source_path );

			if ( ! $image_info ) {
				return false;
			}

			$mime_type = $image_info['mime'];

			// Create image resource based on type.
			switch ( $mime_type ) {
				case 'image/jpeg':
				case 'image/jpg':
					$source_image = imagecreatefromjpeg( $source_path );
					break;
				case 'image/png':
					$source_image = imagecreatefrompng( $source_path );
					break;
				case 'image/gif':
					$source_image = imagecreatefromgif( $source_path );
					break;
				case 'image/bmp':
				case 'image/x-ms-bmp':
					if ( ! function_exists( 'imagecreatefrombmp' ) ) {
						return false;
					}
					$source_image = imagecreatefrombmp( $source_path );
					break;
				default:
					// TIFF and anything else GD cannot decode.
					return false;
			}

			if ( ! $source_image ) {
				return false;
			}

			// Preserve transparency for PNG/GIF.
			if ( in_array( $mime_type, array( 'image/png', 'image/gif' ), true ) ) {
				if ( 'image/gif' === $mime_type && ! imageistruecolor( $source_image ) ) {
					imagepalettetotruecolor( $source_image );
				}
				imagealphablending( $source_image, false );
				imagesavealpha( $source_image, true );
			}

			// Convert to WebP.
			$result = imagewebp( $source_image, $destination_path, $quality );

			// Clean up memory.
			imagedestroy( $source_image );

			return false !== $result;
		} catch ( Throwable $e ) {
			$this->log_error( '(GD) ' . $e->getMessage() );
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
		// Validate file array structure
		if ( ! isset( $file['tmp_name'] ) || ! isset( $file['name'] ) || empty( $file['tmp_name'] ) || empty( $file['name'] ) ) {
			return $file;
		}

		// Check if library is available.
		$libraries = $this->check_image_libraries();

		if ( ! $libraries['available'] ) {
			return $file;
		}

		// Check if file is a supported image type.
		if ( ! $this->is_supported_image( $file ) ) {
			return $file;
		}

		// Validate file exists and is readable
		if ( ! file_exists( $file['tmp_name'] ) || ! is_readable( $file['tmp_name'] ) ) {
			return $file;
		}

		// Never convert animated GIFs — the animation would be lost.
		$file_ext = strtolower( (string) pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$is_gif   = 'gif' === $file_ext || ( isset( $file['type'] ) && 'image/gif' === $file['type'] );

		if ( $is_gif && $this->is_animated_gif( $file['tmp_name'] ) ) {
			return $file;
		}

		$source_path      = $file['tmp_name'];
		$file_info        = pathinfo( $file['name'] );
		$destination_path = $file['tmp_name'] . '.webp';

		$converted = false;
		
		// Get quality from settings, clamped to a valid range.
		$settings = WpXplore_AWC_Settings::get_settings();
		$quality  = isset( $settings['quality'] ) ? absint( $settings['quality'] ) : 80;
		$quality  = min( 100, max( 0, $quality ) );

		// Try Imagick first, then fall back to GD.
		if ( $libraries['imagick'] ) {
			$converted = $this->convert_with_imagick( $source_path, $destination_path, $quality );
		}

		if ( ! $converted && $libraries['gd'] ) {
			$converted = $this->convert_with_gd( $source_path, $destination_path, $quality );
		}

		if ( $converted && file_exists( $destination_path ) ) {
			$original_size = (int) filesize( $source_path );
			$webp_size     = (int) filesize( $destination_path );

			// Only replace if the WebP output is valid and smaller or similar size (10% tolerance).
			$keep_webp = $webp_size > 0 && $webp_size <= $original_size * 1.1;

			// Stash the original before overwriting it, so it can be restored
			// next to the WebP file after the upload is moved (keep_original).
			$stashed = false;
			if ( $keep_webp && ! empty( $settings['keep_original'] ) ) {
				$stashed = $this->stash_original( $source_path, $file['name'] );
			}

			if ( $keep_webp && copy( $destination_path, $source_path ) ) {
				// Update file name and type.
				$file['name'] = isset( $file_info['filename'] ) && '' !== $file_info['filename']
					? sanitize_file_name( $file_info['filename'] . '.webp' )
					: $file['name'];
				$file['type'] = 'image/webp';
			} elseif ( $stashed ) {
				// Conversion was not applied — drop the stash so the original
				// upload is not duplicated.
				$this->discard_stash();
			}

			// Always clean up the temporary WebP file.
			wp_delete_file( $destination_path );
		}

		return $file;
	}
}

