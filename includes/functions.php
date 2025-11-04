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
	// Check if we're in admin (Media Library uploads happen in admin).
	if ( is_admin() ) {
		$settings = WpXplore_AWC_Settings::get_settings();
		
		// Check if this is a featured image upload for a post type.
		$post_type = wpxplore_awc_get_upload_context_post_type();
		
		if ( $post_type ) {
			// This is a featured image upload context.
			$enabled_post_types = isset( $settings['featured_image_post_types'] ) && is_array( $settings['featured_image_post_types'] ) ? $settings['featured_image_post_types'] : array();

			// Here need to add Directorist post types to the enabled post types.
			
			// Only convert if this post type is enabled.
			if ( ! in_array( $post_type, $enabled_post_types, true ) ) {
				return $file;
			}
		} else {
			// Regular media library upload - check if media uploads conversion is disabled.
			$convert_media = isset( $settings['convert_media_uploads'] ) ? (bool) $settings['convert_media_uploads'] : true;
			if ( ! $convert_media ) {
				return $file;
			}
		}
	}
	
	$converter = wpxplore_awc_get_instance();
	return $converter->convert_to_webp( $file );
}
add_filter( 'wp_handle_upload_prefilter', 'wpxplore_awc_handle_upload' );

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

/**
 * Add WebP support to Directorist file types
 *
 * @param array $groups Supported file type groups.
 * @return array Modified file type groups.
 */
function wpxplore_awc_directorist_supported_file_types( $groups ) {
	if ( isset( $groups['image'] ) && is_array( $groups['image'] ) ) {
		$groups['image'][] = 'webp';
	}
	return $groups;
}
add_filter( 'directorist_supported_file_types_groups', 'wpxplore_awc_directorist_supported_file_types' );

