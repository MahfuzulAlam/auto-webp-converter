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
	$converter = wpxplore_awc_get_instance();
	return $converter->convert_to_webp( $file );
}
add_filter( 'wp_handle_upload_prefilter', 'wpxplore_awc_handle_upload' );

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

