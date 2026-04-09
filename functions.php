<?php
/**
 * Subtle Theme Functions
 *
 * @package Subtle
 */

// ============================================================================
// Theme Includes
// ============================================================================

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/page-appearance.php';
require_once get_template_directory() . '/inc/misc-block-customizations.php';

// ============================================================================
// Theme Setup
// ============================================================================

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * @return void
 */
function subtle_setup() {
	// Add support for custom logo.
	add_theme_support( 'custom-logo' );

	// Sitewide body background (Customizer: color, image, repeat, position, etc.).
	add_theme_support( 'custom-background' );

	// Featured images for posts only (not pages).
	add_theme_support( 'post-thumbnails', array( 'post' ) );

	// Add support for editor styles.
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );

	// Add support for wide and full-width block alignments.
	add_theme_support( 'align-wide' );
}
add_action( 'after_setup_theme', 'subtle_setup' );

// ============================================================================
// Navigation
// ============================================================================

/**
 * Registers navigation menu locations.
 *
 * @return void
 */
function subtle_register_menus() {
	register_nav_menus(
		array(
			'header' => __( 'Navigation', 'subtleearth' ),
		)
	);
}
add_action( 'init', 'subtle_register_menus' );

// ============================================================================
// Widget Areas
// ============================================================================

/**
 * Registers widget areas (sidebars) for the theme.
 *
 * @return void
 */
function subtle_widgets_init() {
	$widgets = array(
		'footer-column-1' => __( 'Footer Column 1', 'subtleearth' ),
		'footer-column-2' => __( 'Footer Column 2', 'subtleearth' ),
		'footer-column-3' => __( 'Footer Column 3', 'subtleearth' ),
		'footer-bottom'   => __( 'Footer Bottom', 'subtleearth' ),
	);

	foreach ( $widgets as $id => $name ) {
		register_sidebar(
			array(
				'name'          => $name,
				'id'            => $id,
				'before_widget' => '',
				'after_widget'  => '',
			)
		);
	}
}
add_action( 'widgets_init', 'subtle_widgets_init' );

// ============================================================================
// Theme Helpers
// ============================================================================

/**
 * Get the theme version.
 *
 * @return string Theme version.
 */
function subtle_get_theme_version() {
	static $version = null;
	if ( null === $version ) {
		$version = wp_get_theme()->get( 'Version' );
	}
	return $version;
}

// ============================================================================
// Assets (Styles & Scripts)
// ============================================================================

/**
 * Enqueues theme styles and scripts.
 *
 * @return void
 */
function subtle_enqueue_assets() {
	$theme_version = subtle_get_theme_version();

	// Main stylesheet.
	wp_enqueue_style(
		'subtle-style',
		get_stylesheet_uri(),
		array(),
		$theme_version
	);

	// Mobile menu functionality.
	wp_enqueue_script(
		'subtle-menu',
		get_template_directory_uri() . '/js/menu.js',
		array( 'jquery' ),
		$theme_version,
		true
	);

	// Details block animation.
	wp_enqueue_script(
		'subtle-details-block',
		get_template_directory_uri() . '/js/details-block.js',
		array( 'jquery' ),
		$theme_version,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'subtle_enqueue_assets' );

// ============================================================================
// Media & Images
// ============================================================================

/**
 * Allowed MIME types for PDF files.
 */
const ALLOWED_PDF_MIMES = array(
	'application/pdf',
	'application/x-pdf',
	'application/acrobat',
	'applications/vnd.pdf',
	'text/pdf',
	'text/x-pdf',
);

/**
 * Allows PDF file uploads for administrators only.
 *
 * @param array $mimes Array of allowed MIME types.
 * @return array Modified array of allowed MIME types.
 */
function subtle_allow_pdf_uploads( $mimes ) {
	if ( current_user_can( 'manage_options' ) ) {
		$mimes['pdf'] = 'application/pdf';
	}
	return $mimes;
}
add_filter( 'upload_mimes', 'subtle_allow_pdf_uploads' );

/**
 * Validates PDF file types during upload.
 *
 * @param array  $wp_check_filetype_and_ext File data array containing 'ext', 'type', and 'proper_filename'.
 * @param string $file                      Full path to the file.
 * @param string $filename                  The name of the file.
 * @param array  $mimes                     Array of mime types keyed by their file extension regex.
 * @param string|false $real_mime           The actual mime type or false if the type cannot be determined.
 * @return array Modified file data array.
 */
	function subtle_validate_pdf_upload( $wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime ) {
		if ( ! preg_match( '/\.pdf$/i', $filename ) ) {
			return $wp_check_filetype_and_ext;
		}

		if ( $real_mime && in_array( $real_mime, ALLOWED_PDF_MIMES, true ) ) {
			$wp_check_filetype_and_ext['ext']  = 'pdf';
			$wp_check_filetype_and_ext['type'] = 'application/pdf';
		} else {
			$wp_check_filetype_and_ext['ext']  = false;
			$wp_check_filetype_and_ext['type'] = false;
		}

		return $wp_check_filetype_and_ext;
	}
add_filter( 'wp_check_filetype_and_ext', 'subtle_validate_pdf_upload', 10, 5 );

/**
 * Disables WordPress automatic image resizing.
 * This prevents WordPress from creating multiple image sizes.
 *
 * @return void
 */
function subtle_disable_image_resizing() {
	add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
}
add_action( 'init', 'subtle_disable_image_resizing' );

// ============================================================================
// Development Helpers
// ============================================================================

/**
 * Removes version query strings from styles and scripts.
 * Only active when WP_DEBUG is enabled.
 *
 * @param string $src The source URL.
 * @return string Modified source URL.
 */
	function subtle_remove_version_scripts_styles( $src ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return $src;
		}

		return strpos( $src, 'ver=' ) ? remove_query_arg( 'ver', $src ) : $src;
	}
add_filter( 'style_loader_src', 'subtle_remove_version_scripts_styles', 9999 );
add_filter( 'script_loader_src', 'subtle_remove_version_scripts_styles', 9999 );

