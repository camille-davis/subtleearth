<?php
/**
 * Page Appearance meta box (hide page title) for Subtle And Earth theme
 *
 * @package Subtle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Appearance Meta Box Class
 */
class Subtle_Page_Appearance {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_page_appearance_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_page_appearance' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Add page appearance meta box to page edit screen.
	 *
	 * @return void
	 */
	public function add_page_appearance_meta_box() {
		add_meta_box(
			'subtle_page_appearance',
			__( 'Page Appearance', 'subtleearth' ),
			array( $this, 'render_page_appearance_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Render the page appearance meta box
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_page_appearance_meta_box( $post ) {
		wp_nonce_field( 'subtle_page_appearance_nonce', 'subtle_page_appearance_nonce' );
		?>
		<div class="subtle-page-appearance">
			<?php $this->render_hide_title_section( $post ); ?>
		</div>
		<?php
	}

	/**
	 * Render the hide title section
	 *
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function render_hide_title_section( $post ) {
		$hide_title = get_post_meta( $post->ID, '_subtle_hide_page_title', true );
		?>
		<div class="subtle-toggle-wrapper">
			<label class="subtle-toggle-label">
				<span class="subtle-toggle-label-text"><?php esc_html_e( 'Hide page title', 'subtleearth' ); ?></span>
				<span class="subtle-toggle-switch">
					<input type="checkbox" name="subtle_hide_page_title" value="1" class="subtle-toggle-input" <?php checked( $hide_title, '1' ); ?>>
					<span class="subtle-toggle-slider"></span>
				</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Save page appearance meta box data.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function save_page_appearance( $post_id ) {
		$nonce = $_POST['subtle_page_appearance_nonce'] ?? '';
		if ( ! wp_verify_nonce( $nonce, 'subtle_page_appearance_nonce' ) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			! current_user_can( 'edit_post', $post_id ) ||
			get_post_type( $post_id ) !== 'page' ) {
			return;
		}

		$this->save_hide_title( $post_id );
	}

	/**
	 * Save hide title feature
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function save_hide_title( $post_id ) {
		$hide_title = isset( $_POST['subtle_hide_page_title'] ) ? '1' : '0';
		update_post_meta( $post_id, '_subtle_hide_page_title', $hide_title );
	}

	/**
	 * Enqueue admin assets for page appearance meta box.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ( 'post.php' !== $hook && 'post-new.php' !== $hook ) || ( $GLOBALS['post_type'] ?? '' ) !== 'page' ) {
			return;
		}

		$theme_version = subtle_get_theme_version();

		wp_enqueue_style(
			'subtle-page-appearance-admin',
			get_template_directory_uri() . '/css/page-appearance-admin.css',
			array(),
			$theme_version
		);
	}

	/**
	 * Enqueue block editor assets for page appearance features.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		$post = get_post();
		if ( ! $post || 'page' !== $post->post_type ) {
			return;
		}

		$theme_version = subtle_get_theme_version();

		wp_enqueue_script(
			'subtle-page-appearance-editor',
			get_template_directory_uri() . '/js/page-appearance-editor.js',
			array(),
			$theme_version,
			true
		);

		$hide_title = get_post_meta( $post->ID, '_subtle_hide_page_title', true );
		wp_localize_script(
			'subtle-page-appearance-editor',
			'subtlePageAppearance',
			array(
				'hideTitle' => ( '1' === $hide_title ),
			)
		);
	}
}

new Subtle_Page_Appearance();
