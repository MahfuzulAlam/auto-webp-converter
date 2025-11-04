<?php
/**
 * Plugin Functions
 *
 * Hooks and support functions for Auto WebP Converter
 *
 * @package Auto_WebP_Converter
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin instance
 *
 * @return WpXplore_Auto_WebP_Converter
 */
function wpxplore_awc_get_instance() {
	return WpXplore_Auto_WebP_Converter::get_instance();
}

/**
 * Handle WordPress upload process
 *
 * @param array $file File array from WordPress upload.
 * @return array Modified file array.
 */
function wpxplore_awc_handle_upload( $file ) {
	// Frontend uploads
	if ( ! is_admin() ) {
		return wpxplore_awc_handle_frontend_upload( $file );
	}

	// Admin uploads
	return wpxplore_awc_handle_admin_upload( $file );
}
add_filter( 'wp_handle_upload_prefilter', 'wpxplore_awc_handle_upload' );

/**
 * Handle frontend uploads
 *
 * @param array $file File array from WordPress upload.
 * @return array Modified file array.
 */
function wpxplore_awc_handle_frontend_upload( $file ) {
	$directorist = WpXplore_AWC_Directorist::get_instance();

	// Check if this is a Directorist frontend add listing page upload
	if ( $directorist->is_frontend_add_listing_page() && $directorist->is_frontend_add_listing_enabled() ) {
		return wpxplore_awc_convert_image( $file );
	}

	// Frontend uploads not from Directorist add listing page - return as is
	return $file;
}

/**
 * Handle admin uploads
 *
 * @param array $file File array from WordPress upload.
 * @return array Modified file array.
 */
function wpxplore_awc_handle_admin_upload( $file ) {
	$settings = WpXplore_AWC_Settings::get_settings();
	$post_type = wpxplore_awc_get_upload_context_post_type();

	// Featured image upload context (post edit screen)
	if ( $post_type ) {
		return wpxplore_awc_handle_featured_image_upload( $file, $post_type, $settings );
	}

	// Regular media library upload
	return wpxplore_awc_handle_media_library_upload( $file, $settings );
}

/**
 * Handle featured image uploads from post edit screens
 *
 * @param array $file File array from WordPress upload.
 * @param string $post_type Post type slug.
 * @param array $settings Plugin settings.
 * @return array Modified file array.
 */
function wpxplore_awc_handle_featured_image_upload( $file, $post_type, $settings ) {
	// Get enabled post types from settings
	$enabled_post_types = isset( $settings['featured_image_post_types'] ) && is_array( $settings['featured_image_post_types'] ) 
		? $settings['featured_image_post_types'] 
		: array();

	// Add Directorist post types if enabled
	$directorist = WpXplore_AWC_Directorist::get_instance();
	if ( $directorist->is_conversion_enabled() ) {
		$directorist_post_types = $directorist->get_post_types();
		if ( ! empty( $directorist_post_types ) ) {
			$enabled_post_types = array_merge( $enabled_post_types, $directorist_post_types );
		}
	}

	// Only convert if this post type is enabled
	if ( ! in_array( $post_type, $enabled_post_types, true ) ) {
		return $file;
	}

	return wpxplore_awc_convert_image( $file );
}

/**
 * Handle media library uploads
 *
 * @param array $file File array from WordPress upload.
 * @param array $settings Plugin settings.
 * @return array Modified file array.
 */
function wpxplore_awc_handle_media_library_upload( $file, $settings ) {
	// Check if media library conversion is enabled
	$convert_media = isset( $settings['convert_media_uploads'] ) ? (bool) $settings['convert_media_uploads'] : true;
	
	if ( ! $convert_media ) {
		return $file;
	}

	return wpxplore_awc_convert_image( $file );
}

/**
 * Convert image to WebP format
 *
 * @param array $file File array from WordPress upload.
 * @return array Modified file array.
 */
function wpxplore_awc_convert_image( $file ) {
	$converter = wpxplore_awc_get_instance();
	return $converter->convert_to_webp( $file );
}

/**
 * Get post type from upload context
 *
 * Tries to detect if upload is happening from a post edit screen.
 *
 * @return string|false Post type slug or false if not in post edit context.
 */
function wpxplore_awc_get_upload_context_post_type() {
	// Check if we have a post_id in POST data (from media uploader on post edit screen).
	if ( isset( $_POST['post_id'] ) && ! empty( $_POST['post_id'] ) && 0 !== absint( $_POST['post_id'] ) ) {
		$post_id = absint( $_POST['post_id'] );
		$post = get_post( $post_id );
		if ( $post && isset( $post->post_type ) && ! empty( $post->post_type ) ) {
			return $post->post_type;
		}
	}
	
	// Check current screen if available (for direct uploads from post edit screen).
	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( $screen && isset( $screen->post_type ) && ! empty( $screen->post_type ) && 'attachment' !== $screen->post_type ) {
			return $screen->post_type;
		}
	}
	
	// Check HTTP_REFERER for post edit page (most reliable for AJAX uploads).
	if ( isset( $_SERVER['HTTP_REFERER'] ) && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		// Parse referer to check if it's a post edit page.
		$parsed_url = parse_url( $referer );
		if ( isset( $parsed_url['query'] ) && ! empty( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $query_params );
			
			// Check for post ID in referer.
			if ( isset( $query_params['post'] ) && ! empty( $query_params['post'] ) ) {
				$post_id = absint( $query_params['post'] );
				$post = get_post( $post_id );
				if ( $post && isset( $post->post_type ) && ! empty( $post->post_type ) ) {
					return $post->post_type;
				}
			}
			
			// Check for post_type parameter in referer.
			if ( isset( $query_params['post_type'] ) && ! empty( $query_params['post_type'] ) ) {
				$post_type = sanitize_text_field( $query_params['post_type'] );
				if ( post_type_exists( $post_type ) && 'attachment' !== $post_type ) {
					return $post_type;
				}
			}
		}
	}
	
	return false;
}

