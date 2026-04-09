<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package Subtle
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="error-404 not-found">
    <div class="entry-title-outer">
      <h1 class="entry-title"><?php esc_html_e( 'Page not found', 'subtleearth' ); ?></h1>
    </div>
		<div class="entry-content">
			<p>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">
					<?php esc_html_e( 'Return to Homepage', 'subtleearth' ); ?>
				</a>
			</p>
		</div>
	</div>
</main>

<?php
get_sidebar();
get_footer();

