<?php
/**
 * Misc block customizations for Subtle And Earth theme:
 * - Customize details block
 * - Add is-fullwidth toggle to group block
 * - Add is-fullwidth-image and is-fullheight-image toggles to image block
 * - Add columns per row controls to columns block
 * - Add link URL option to cover block
 * - Change 'Dimensions' panel title to 'Spacing'
 *
 * @package Subtle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Customizations Class
 */
class Subtle_Blocks {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'render_block', array( $this, 'customize_details_block' ), 10, 2 );
		add_filter( 'render_block_core/group', array( $this, 'group_block_fullwidth' ), 10, 2 );
		add_filter( 'render_block_core/columns', array( $this, 'columns_block_columns_per_row' ), 10, 2 );
		add_filter( 'render_block_core/cover', array( $this, 'cover_block_link' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Register image block filters for fullwidth and fullheight.
		$image_block_attributes = array(
			array( 'fullwidth', 'is-fullwidth-image' ),
			array( 'fullheight', 'is-fullheight-image' ),
		);
		foreach ( $image_block_attributes as $attr_config ) {
			list( $attr_key, $css_class ) = $attr_config;
			add_filter(
				'render_block_core/image',
				function( $block_content, $block ) use ( $attr_key, $css_class ) {
					return $this->add_block_class( $block_content, $block, 'wp-block-image', $css_class, $attr_key );
				},
				10,
				2
			);
		}
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		$theme_version = subtle_get_theme_version();
		$block_editor_deps = array(
			'wp-blocks',
			'wp-block-editor',
			'wp-components',
			'wp-compose',
			'wp-element',
			'wp-hooks',
		);

		$block_scripts = array(
			'subtle-group-block-fullwidth'      => 'group-block-fullwidth.js',
			'subtle-image-block-options'         => 'image-block-options.js',
			'subtle-columns-block-columns-per-row' => 'columns-block-columns-per-row.js',
			'subtle-cover-block-link'           => 'cover-block-link.js',
		);

		foreach ( $block_scripts as $handle => $file ) {
			wp_enqueue_script(
				$handle,
				get_template_directory_uri() . '/js/' . $file,
				$block_editor_deps,
				$theme_version,
				true
			);
		}

		wp_enqueue_script(
			'subtle-dimensions-panel-title',
			get_template_directory_uri() . '/js/dimensions-panel-title.js',
			array( 'wp-i18n' ),
			$theme_version,
			true
		);

		wp_enqueue_script(
			'subtle-add-editor-classes',
			get_template_directory_uri() . '/js/add-editor-classes.js',
			array(),
			$theme_version,
			true
		);
	}

	/**
	 * Customize Details Gutenberg block.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @return string Modified block content.
	 */
	public function customize_details_block( $block_content, $block ) {
		if ( 'core/details' === $block['blockName'] ) {
			$block_content = preg_replace(
				array( '/<summary>/', '/<\/summary>/', '/<\/details>/' ),
				array( '<summary><h3>', '</h3></summary><div class="details-content">', '</div></details>' ),
				$block_content
			);
		}

		return $block_content;
	}

	/**
	 * Add is-fullwidth class to Group block when fullwidth attribute is set.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @return string Modified block content.
	 */
	public function group_block_fullwidth( $block_content, $block ) {
		return $this->add_block_class( $block_content, $block, 'wp-block-group', 'is-fullwidth', 'fullwidth' );
	}

	/**
	 * Add columns per row data attributes to Columns block.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @return string Modified block content.
	 */
	public function columns_block_columns_per_row( $block_content, $block ) {
		$attrs = $block['attrs'] ?? array();
		$breakpoints = array( 'desktop', 'medium', 'tablet', 'mobile' );

		$values = array();
		$prev_value = '';
		foreach ( $breakpoints as $breakpoint ) {
			$attr_key = $breakpoint . 'ColumnsPerRow';
			$values[ $breakpoint ] = $attrs[ $attr_key ] ?? $prev_value;
			$prev_value = $values[ $breakpoint ];
		}

		if ( ! array_filter( $values ) ) {
			return $block_content;
		}

		$data_attrs = array();
		foreach ( $breakpoints as $breakpoint ) {
			if ( $values[ $breakpoint ] ) {
				$data_attrs[] = 'data-columns-per-row-' . $breakpoint . '="' . esc_attr( $values[ $breakpoint ] ) . '"';
			}
		}

		return preg_replace(
			'/(<[^>]*\bclass="[^"]*wp-block-columns[^"]*")/',
			'$1 ' . implode( ' ', $data_attrs ),
			$block_content,
			1
		);
	}

	/**
	 * Wrap Cover block in link tag when linkUrl attribute is set.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @return string Modified block content.
	 */
	public function cover_block_link( $block_content, $block ) {
		$link_url = $block['attrs']['linkUrl'] ?? '';
		if ( empty( $link_url ) ) {
			return $block_content;
		}
		return '<a class="cover-block-link" href="' . esc_url( $link_url ) . '"' . ( ( $block['attrs']['linkOpenInNewTab'] ?? false ) ? ' target="_blank" rel="noopener noreferrer"' : '' ) . '>' . $block_content . '</a>';
	}

	/**
	 * Add class to block content when attribute is set.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @param string $block_class The block class to match (e.g., 'wp-block-group').
	 * @param string $css_class The CSS class to add (e.g., 'is-fullwidth').
	 * @param string $attr_key The attribute key to check (e.g., 'fullwidth').
	 * @return string Modified block content.
	 */
	private function add_block_class( $block_content, $block, $block_class, $css_class, $attr_key ) {
		$attr_value = $block['attrs'][ $attr_key ] ?? false;

		if ( ! $attr_value ) {
			return $block_content;
		}

		// Check if class already exists in the target element's class attribute (not just anywhere in content).
		// This prevents false positives when the class exists in nested blocks.
		$pattern_check = '/(<[^>]*\bclass="[^"]*' . preg_quote( $block_class, '/' ) . '[^"]*' . preg_quote( $css_class, '/' ) . '[^"]*")/';
		if ( preg_match( $pattern_check, $block_content ) ) {
			return $block_content;
		}

		$pattern = '/(<[^>]*\bclass="[^"]*' . preg_quote( $block_class, '/' ) . '[^"]*)(")/';
		return preg_replace(
			$pattern,
			'$1 ' . $css_class . '$2',
			$block_content,
			1
		);
	}
}

new Subtle_Blocks();
