<?php
/**
 * Customizer functionality for Subtle And Earth theme.
 *
 * @package Subtle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load color utility functions and classes.
require_once get_template_directory() . '/inc/color-converter.php';

/**
 * Customizer class.
 */
class Subtle_Customizer {

	/**
	 * Default fonts URL.
	 *
	 * @var string
	 */
	private const DEFAULT_FONTS_URL = 'https://fonts.googleapis.com/css2?family=Bungee&family=Oswald:wght@300&family=Noto+Sans:ital,wght@0,400;0,700;1,400;1,700&display=block';

	/**
	 * Allowed font hosts.
	 *
	 * @var array
	 */
	private const ALLOWED_FONT_HOSTS = array( 'fonts.googleapis.com', 'fonts.gstatic.com' );

	/**
	 * Default color values.
	 *
	 * @var array
	 */
	public const DEFAULT_COLORS = array(
		'nav_background_color'  => '#111111',
		'nav_link_color'        => '#FFFFFF',
		'nav_link_hover_color'  => '#FFC066',
		'background_color'      => '#FFFFFF',
		'text_color'            => '#222222',
		'link_color'            => '#008888',
		'title_color'           => '#222222',
		'accent_color_1'        => '#FFFFFF',
		'accent_color_2'        => '#FFC066',
		'accent_color_3'        => '#BDE06F',
	);

	/**
	 * Default typography values.
	 *
	 * @var array
	 */
	private const DEFAULT_TYPOGRAPHY = array(
		'title_font'            => 'Bungee',
		'title_letter_spacing'  => '0',
		'title_line_height'     => '.8',
		'heading_font'          => 'Oswald',
		'heading_letter_spacing'=> '.025em',
		'heading_line_height'   => '1',
		'body_font'             => 'Noto Sans',
		'body_line_height'      => '1.75',
		'body_font_size' => '1rem',
		'h1_font_size'   => '5rem',
		'h2_font_size'   => '3rem',
		'h3_font_size'   => '2.5rem',
		'h4_font_size'   => '2rem',
		'h5_font_size'   => '1.75rem',
		'h6_font_size'   => '1rem',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'register_controls' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'add_reset_buttons' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'add_customizer_style' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_inline_css' ), 20 );
		add_action( 'after_setup_theme', array( $this, 'register_editor_color_palette' ) );
		add_filter( 'block_editor_settings_all', array( $this, 'add_editor_inline_css' ), 10, 1 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_editor_custom_background_class' ), 20 );
	}

	/**
	 * Pass whether to mirror body.custom-background on .editor-styles-wrapper (same condition as body_class).
	 *
	 * @return void
	 */
	public function localize_editor_custom_background_class() {
		wp_localize_script(
			'subtle-add-editor-classes',
			'subtleEditorCustomBackground',
			array(
				'shouldAddClass' => self::should_add_editor_custom_background_class(),
			)
		);
	}

	/**
	 * Whether the front end would add the custom-background body class (see wp-includes/post-template.php).
	 *
	 * @return bool
	 */
	private static function should_add_editor_custom_background_class() {
		if ( ! current_theme_supports( 'custom-background' ) ) {
			return false;
		}

		$default_color = get_theme_support( 'custom-background', 'default-color' );

		return get_background_color() !== $default_color || (bool) get_background_image();
	}

	/**
	 * Register customizer controls and settings.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function register_controls( $wp_customize ) {
		$this->register_color_controls( $wp_customize );
		$this->register_typography_controls( $wp_customize );
	}

	/**
	 * Register color controls.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	private function register_color_controls( $wp_customize ) {

		// Colors Section.
		$wp_customize->add_section(
			'subtle_colors',
			array(
				'title'    => __( 'Theme Colors', 'subtleearth' ),
				'priority' => 30,
			)
		);

		// Color settings.
		$colors = array(
			'nav_background_color' => __( 'Nav Background Color', 'subtleearth' ),
			'nav_link_color'       => __( 'Nav Link Color', 'subtleearth' ),
			'nav_link_hover_color' => __( 'Nav Link Hover Color', 'subtleearth' ),
			'background_color'     => __( 'Background Color', 'subtleearth' ),
			'text_color'           => __( 'Text Color', 'subtleearth' ),
			'link_color'           => __( 'Link Color', 'subtleearth' ),
			'title_color'          => __( 'Title Color', 'subtleearth' ),
			'accent_color_1'       => __( 'Accent Color 1', 'subtleearth' ),
			'accent_color_2'       => __( 'Accent Color 2', 'subtleearth' ),
			'accent_color_3'       => __( 'Accent Color 3', 'subtleearth' ),
		);

		foreach ( $colors as $setting_id => $label ) {
			$wp_customize->add_setting(
				$setting_id,
				array(
					'default'           => self::DEFAULT_COLORS[ $setting_id ],
					'sanitize_callback' => 'sanitize_hex_color',
				)
			);

			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					$setting_id,
					array(
						'label'   => $label,
						'section' => 'subtle_colors',
					)
				)
			);
		}
	}

	/**
	 * Register typography controls.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	private function register_typography_controls( $wp_customize ) {

		// Typography Section.
		$wp_customize->add_section(
			'subtle_typography',
			array(
				'title'    => __( 'Typography', 'subtleearth' ),
				'priority' => 35,
			)
		);

		// Fonts URL.
		$wp_customize->add_setting(
			'fonts_url',
			array(
				'default'           => self::DEFAULT_FONTS_URL,
				'sanitize_callback' => array( $this, 'sanitize_font_url' ),
			)
		);

		$wp_customize->add_control(
			'fonts_url',
			array(
				'label'   => __( 'Fonts URL', 'subtleearth' ),
				'section' => 'subtle_typography',
				'type'    => 'text',
			)
		);

		// Typography text controls.
		$typography_controls = array(
			'title_font'            => __( 'Title Font', 'subtleearth' ),
			'title_letter_spacing'  => __( 'Title Letter Spacing', 'subtleearth' ),
			'title_line_height'     => __( 'Title Line Height', 'subtleearth' ),
			'heading_font'          => __( 'Heading Font', 'subtleearth' ),
			'heading_letter_spacing'=> __( 'Heading Letter Spacing', 'subtleearth' ),
			'heading_line_height'   => __( 'Heading Line Height', 'subtleearth' ),
			'body_font'             => __( 'Body Font', 'subtleearth' ),
			'body_line_height'      => __( 'Body Line Height', 'subtleearth' ),
			'body_font_size'        => __( 'Body Font Size', 'subtleearth' ),
		);

		foreach ( $typography_controls as $setting_id => $label ) {
			$this->register_text_control( $wp_customize, $setting_id, $label, 'subtle_typography' );
		}

		// Heading Font Sizes.
		$headings = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
		foreach ( $headings as $heading ) {
			$setting_id = "{$heading}_font_size";
			$label      = sprintf(
				// translators: %s is the heading name.
				__( '%s Size', 'subtleearth' ),
				ucfirst( $heading )
			);
			$this->register_text_control( $wp_customize, $setting_id, $label, 'subtle_typography' );
		}
	}

	/**
	 * Register a text control in the customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 * @param string               $setting_id   Setting ID.
	 * @param string               $label        Control label.
	 * @param string               $section      Section ID.
	 */
	private function register_text_control( $wp_customize, $setting_id, $label, $section ) {
		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => self::DEFAULT_TYPOGRAPHY[ $setting_id ] ?? '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			$setting_id,
			array(
				'label'   => $label,
				'section' => $section,
				'type'    => 'text',
			)
		);
	}

	/**
	 * Sanitize font URL to only allow trusted sources.
	 *
	 * @param string $url The font URL to sanitize.
	 * @return string Sanitized URL or default if invalid.
	 */
	public function sanitize_font_url( $url ) {
		$url = esc_url_raw( $url );

		// If empty, return default.
		if ( empty( $url ) ) {
			return self::DEFAULT_FONTS_URL;
		}

		$parsed = wp_parse_url( $url );

		// If URL cannot be parsed or has no host, return default.
		if ( ! $parsed || empty( $parsed['host'] ) ) {
			return self::DEFAULT_FONTS_URL;
		}

		$host = strtolower( $parsed['host'] );

		// Allow same domain or subdomain.
		if ( isset( $_SERVER['HTTP_HOST'] ) && strpos( $host, strtolower( $_SERVER['HTTP_HOST'] ) ) !== false ) {
			return $url;
		}

		// Check against allowed hosts.
		foreach ( self::ALLOWED_FONT_HOSTS as $allowed ) {
			if ( $host === $allowed || strpos( $host, '.' . $allowed ) !== false ) {
				return $url;
			}
		}

		// If not allowed, return default.
		return self::DEFAULT_FONTS_URL;
	}

	/**
	 * Add reset buttons to customizer controls.
	 */
	public function add_reset_buttons() {
		global $wp_customize;

		// Get typography setting defaults and build inline script in a single loop.
		$inline_script = '';
		$controls      = $wp_customize->controls();
		foreach ( $controls as $control ) {
			if ( 'subtle_typography' !== $control->section ) {
				continue;
			}
			$setting = $wp_customize->get_setting( $control->id );
			if ( $setting ) {
				$inline_script .= sprintf(
					'wp.customize("%s", function(setting) { setting.default = %s; });',
					$control->id,
					wp_json_encode( $setting->default )
				);
			}
		}
		wp_add_inline_script( 'customize-controls', $inline_script );

		// Add 'Reset' buttons.
		$theme_version = subtle_get_theme_version();
		wp_enqueue_script(
			'subtle-customizer',
			get_template_directory_uri() . '/js/customizer.js',
			array( 'jquery', 'customize-controls' ),
			$theme_version,
			true
		);

		wp_localize_script(
			'subtle-customizer',
			'subtleCustomizer',
			array(
				'resetText' => __( 'Reset', 'subtleearth' ),
			)
		);
	}

	/**
	 * Add customizer styles.
	 */
	public function add_customizer_style() {
		$theme_version = subtle_get_theme_version();
		wp_enqueue_style(
			'subtle-customizer',
			get_template_directory_uri() . '/css/customizer.css',
			array(),
			$theme_version
		);
	}

	/**
	 * Add CSS variables to editor canvas only (not sidebar).
	 *
	 * @param array $editor_settings Editor settings array.
	 * @return array Modified editor settings.
	 */
	public function add_editor_inline_css( $editor_settings ) {

		$inline_css = self::get_inline_css() . self::get_editor_canvas_custom_background_css();

		if ( '' === trim( $inline_css ) ) {
			return $editor_settings;
		}

		// Ensure styles array exists.
		if ( ! isset( $editor_settings['styles'] ) ) {
			$editor_settings['styles'] = array();
		}

		// Prepend CSS variables so they're available before other styles.
		// Must set isGlobalStyles to false or it will be stripped out.
		array_unshift(
			$editor_settings['styles'],
			array(
				'css'            => $inline_css,
				'__unstableType' => 'theme',
				'isGlobalStyles' => false,
			)
		);

		return $editor_settings;
	}

	/**
	 * Editor canvas: only the custom background image URL (no color, repeat, position, etc.).
	 *
	 * @return string CSS rule for .editor-styles-wrapper, or empty string.
	 */
	private static function get_editor_canvas_custom_background_css() {
		if ( ! current_theme_supports( 'custom-background' ) ) {
			return '';
		}

		$background = set_url_scheme( get_background_image() );
		if ( ! $background ) {
			return '';
		}

		return '.editor-styles-wrapper { background-image: url("' . sanitize_url( $background ) . '"); }';
	}

	/**
	 * Register editor color palette with theme colors from customizer.
	 */
	public function register_editor_color_palette() {
		$color_map = array(
			'nav_background_color' => array( 'name' => __( 'Nav Background Color', 'subtleearth' ), 'slug' => 'nav-background-color' ),
			'nav_link_color'       => array( 'name' => __( 'Nav Link Color', 'subtleearth' ), 'slug' => 'nav-link-color' ),
			'nav_link_hover_color' => array( 'name' => __( 'Nav Link Hover Color', 'subtleearth' ), 'slug' => 'nav-link-hover-color' ),
			'background_color'     => array( 'name' => __( 'Background Color', 'subtleearth' ), 'slug' => 'background-color' ),
			'text_color'           => array( 'name' => __( 'Text Color', 'subtleearth' ), 'slug' => 'text-color' ),
			'link_color'           => array( 'name' => __( 'Link Color', 'subtleearth' ), 'slug' => 'link-color' ),
			'title_color'          => array( 'name' => __( 'Title Color', 'subtleearth' ), 'slug' => 'title-color' ),
			'accent_color_1'       => array( 'name' => __( 'Accent Color 1', 'subtleearth' ), 'slug' => 'accent-color-1' ),
			'accent_color_2'       => array( 'name' => __( 'Accent Color 2', 'subtleearth' ), 'slug' => 'accent-color-2' ),
			'accent_color_3'       => array( 'name' => __( 'Accent Color 3', 'subtleearth' ), 'slug' => 'accent-color-3' ),
		);

		$editor_color_palette = array();
		$seen_hex             = array();
		foreach ( $color_map as $setting_id => $args ) {
			$color = get_theme_mod( $setting_id, self::DEFAULT_COLORS[ $setting_id ] );
			$key   = strtolower( $color );
			if ( isset( $seen_hex[ $key ] ) ) {
				continue;
			}
			$seen_hex[ $key ] = true;
			$editor_color_palette[] = array(
				'name'  => $args['name'],
				'slug'  => $args['slug'],
				'color' => $color,
			);
		}

		add_theme_support( 'editor-color-palette', $editor_color_palette );
	}

	/**
	 * Get inline CSS with CSS variables.
	 *
	 * @return string CSS string with variables.
	 */
	public static function get_inline_css() {
		$css = '';

		// Output font import.
		$fonts_url = get_theme_mod( 'fonts_url', self::DEFAULT_FONTS_URL );
		if ( $fonts_url ) {
			$css .= '@import url("' . esc_url_raw( $fonts_url ) . '");';
		}

		// Cache theme mod values to avoid repeated calls.
		$color_settings = array(
			'nav_background_color' => get_theme_mod( 'nav_background_color', self::DEFAULT_COLORS['nav_background_color'] ),
			'nav_link_color'       => get_theme_mod( 'nav_link_color', self::DEFAULT_COLORS['nav_link_color'] ),
			'nav_link_hover_color' => get_theme_mod( 'nav_link_hover_color', self::DEFAULT_COLORS['nav_link_hover_color'] ),
			'background_color'     => get_theme_mod( 'background_color', self::DEFAULT_COLORS['background_color'] ),
			'text_color'           => get_theme_mod( 'text_color', self::DEFAULT_COLORS['text_color'] ),
			'link_color'           => get_theme_mod( 'link_color', self::DEFAULT_COLORS['link_color'] ),
			'title_color'          => get_theme_mod( 'title_color', self::DEFAULT_COLORS['title_color'] ),
			'accent_color_1'       => get_theme_mod( 'accent_color_1', self::DEFAULT_COLORS['accent_color_1'] ),
			'accent_color_2'       => get_theme_mod( 'accent_color_2', self::DEFAULT_COLORS['accent_color_2'] ),
			'accent_color_3'       => get_theme_mod( 'accent_color_3', self::DEFAULT_COLORS['accent_color_3'] ),
		);

		// Build color CSS variables.
		$color_variables = array();
		$color_map = array(
			'nav_background_color' => 'nav-background',
			'nav_link_color'       => 'nav-link-color',
			'nav_link_hover_color' => 'nav-link-hover-color',
			'background_color'     => 'background-color',
			'text_color'           => 'text',
			'link_color'           => 'link-color',
			'title_color'          => 'title-color',
			'accent_color_1'       => 'accent-color-1',
			'accent_color_2'       => 'accent-color-2',
			'accent_color_3'       => 'accent-color-3',
		);
		foreach ( $color_map as $setting_id => $css_var ) {
			$color = $color_settings[ $setting_id ];
			$color_variables[ $css_var ] = $color;
			$color_variables[ $css_var . '-filter' ] = Subtle_Color_Converter::hex_to_css_filter( $color );
			$color_variables[ $css_var . '-rgb' ] = implode( ',', Subtle_Color_Converter::hex_to_rgb( $color ) );
		}

		// Build typography CSS variables.
		$typography_variables = array(
			'title-font'             => get_theme_mod( 'title_font', self::DEFAULT_TYPOGRAPHY['title_font'] ) . ', sans-serif',
			'title-letter-spacing'   => get_theme_mod( 'title_letter_spacing', self::DEFAULT_TYPOGRAPHY['title_letter_spacing'] ),
			'title-line-height'      => get_theme_mod( 'title_line_height', self::DEFAULT_TYPOGRAPHY['title_line_height'] ),
			'heading-font'           => get_theme_mod( 'heading_font', self::DEFAULT_TYPOGRAPHY['heading_font'] ) . ', sans-serif',
			'heading-letter-spacing' => get_theme_mod( 'heading_letter_spacing', self::DEFAULT_TYPOGRAPHY['heading_letter_spacing'] ),
			'heading-line-height'    => get_theme_mod( 'heading_line_height', self::DEFAULT_TYPOGRAPHY['heading_line_height'] ),
			'body-font'              => get_theme_mod( 'body_font', self::DEFAULT_TYPOGRAPHY['body_font'] ) . ', sans-serif',
			'body-line-height'       => get_theme_mod( 'body_line_height', self::DEFAULT_TYPOGRAPHY['body_line_height'] ),
			'body-font-size'         => get_theme_mod( 'body_font_size', self::DEFAULT_TYPOGRAPHY['body_font_size'] ),
		);
		$headings = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
		foreach ( $headings as $heading ) {
			$setting_id = "{$heading}_font_size";
			$typography_variables[ "{$heading}-font-size" ] = get_theme_mod( $setting_id, self::DEFAULT_TYPOGRAPHY[ $setting_id ] );
		}

		// Combine all CSS variables.
		$custom_css_variables = array_merge( $color_variables, $typography_variables );

		$css .= ':root {';
		foreach ( $custom_css_variables as $name => $value ) {
			$css .= "--{$name}: {$value};";
		}
		$css .= '}';

		return $css;
	}

	/**
	 * Attach inline CSS to the main theme stylesheet on the frontend.
	 */
	public function enqueue_frontend_inline_css() {
		$inline_css = self::get_inline_css();

		if ( empty( $inline_css ) ) {
			return;
		}

		// Attach the CSS variables to the main theme stylesheet.
		wp_add_inline_style( 'subtle-style', $inline_css );
	}
}

new Subtle_Customizer();
